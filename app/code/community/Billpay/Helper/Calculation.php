<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @package    Billpay
 * @author 	   Jan Wehrs <jan.wehrs@billpay.de>
 * @copyright  Copyright (c) 2009 Billpay GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Billpay_Helper_Calculation extends Mage_Payment_Helper_Data {

	/**
	 * Get store by store id
	 * 
	 * @param int $storeId
	 * @return Mage_Core_Model_Store
	 */
	public function getStore($storeId) {
		return Mage::app()->getStore($storeId);
	}
	
    /**
     * Get billpay logger
     *
     * @return Billpay_Helper_Log
     */
    public function getLog() {
    	return Mage::helper('billpay/log');
    }
	
   /**
     * Get billpay api
     *
     * @return Billpay_Helper_Api
     */
    public function getApi() {
    	return Mage::helper('billpay/api');
    }
    
    
	/**
	 * Get tax rate by tax class id (works only for countries with unique tax)
	 * 
	 * @param int $taxClassId
	 * @return float
	 */
	public function getTaxRateByTaxClassId($taxClassId) {
		$request = Mage::getSingleton('tax/calculation')->getRateRequest();
		$request->setProductClassId($taxClassId);
		$rate = Mage::getSingleton('tax/calculation')->getRate($request);
		return $rate;
	}
	
	/**
	 * Get net price according to magento calulation rules
	 * 
	 * @param float $priceGross
	 * @param float $rate
	 * @param int $storeId
	 * @return float
	 */
	public function getNetPrice($priceGross, $rate, $storeId) {
		$net = $priceGross - ($priceGross/(100+$rate)*$rate);
		$net = $this->getStore($storeId)->roundPrice($net);
    	return $net;
	}
	
	/**
	 * Get gross price according to magento calulation rules
	 * 
	 * @param float $priceNet
	 * @param float $rate
	 * @param int $storeId
	 * @return float
	 */
	public function getGrossPrice($priceNet, $rate, $storeId) {
		$gross = $priceNet + ($priceNet*$rate/100);
		$gross = $this->getStore($storeId)->roundPrice($gross);
    	return $gross;
	}
	
	/**
	 * Calculate amount of tax for a given price and tax class
	 * 
	 * @param float $price
	 * @param int $taxClassId
	 * @param int $storeId
	 * @param boolean $isGross
	 * @return float
	 */
	public function getTaxAmount($price, $taxClassId, $storeId, $isGross = true) {
		$rate = $this->getTaxRateByTaxClassId($taxClassId);
		if ($isGross == true) {
			$priceNet = $this->getNetPrice($price, $rate, $storeId);
			return $price - $priceNet;
		}
		else {
			$priceGross = $this->getGrossPrice($price, $rate, $storeId);
			return $priceGross - $price;
		}
	}
	
	
	/**
	 * Check whether the fee charge feature is enabled
	 * 
	 * @return boolean
	 */
	public function isFeeChargeEnabled($paymentMethod, $storeId, $b2b) {
		if ($paymentMethod == Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD) {
			return false;
		}
		
		$confKey = $b2b == true ? 'charge_fee_b2b_steps_enabled' : 'charge_fee_steps_enabled';
		return $this->getApi()->getConfigData($confKey, $storeId, $paymentMethod);
	}
	
	
	/**
	 * Get charged fee amount with tax (net and gross)
	 * 
	 * @param string $paymentMethod
	 * @param int $storeId
	 * @param float $grandTotal
	 * @return array
	 */
	public function getChargedFee($paymentMethod, $storeId, $grandTotal, $country, $b2b=false) {
		if ($this->isFeeChargeEnabled($paymentMethod, $storeId, $b2b)) {
			$stepValue = $this->getChargedFeeStepValue($storeId, $paymentMethod, $grandTotal, $country, $b2b);
			
			if ($stepValue > 0) {
				$taxClassId = $this->getApi()->getConfigData('charge_fee_steps_tax_class', $storeId, $paymentMethod);
				$rate = $this->getTaxRateByTaxClassId($taxClassId);
				
				if ($this->getApi()->getConfigData('fee/fee_contain_tax', $storeId)) {
					$gross =  $stepValue;
					$net = $this->getNetPrice($gross, $rate, $storeId);
				}
				else {
					$net = $stepValue;
					$gross = $this->getGrossPrice($net, $rate, $storeId);
					
				}

				return array($net, $gross);
			}
		}
    	
    	return false;
	}
	
	
	/**
	 * 
	 * Get charged fee amount that was entered by admin
	 * @param unknown_type $storeId
	 * @param unknown_type $paymentMethod
	 * @param unknown_type $grandTotal
	 * @param unknown_type $country
	 * @param unknown_type $b2b
	 */
    public function getChargedFeeStepValue($storeId, $paymentMethod, $grandTotal, $country, $b2b = false) {
		$confKey = $b2b == true ? 'charge_fee_b2b_steps' : 'charge_fee_steps';
    	$chargeFeeSteps = $this->getApi()->getConfigData($confKey, $storeId, $paymentMethod);
    	
    	$stepValue = 0;
    	if (!empty($chargeFeeSteps)) {
    		$chargeFeeSteps = preg_replace("/\s/", "", $chargeFeeSteps);
    		
    		$cartTotalPriceGross = $this->getApi()->currencyToSmallerUnit($grandTotal);
    		
    		$steps = explode(';', $chargeFeeSteps);
    		
    		$legacyMode = false;
    		if (count($steps) > 0 && count(explode(':', $steps[0])) < 2) {
    			$legacyMode = true;
    		}
    		
    		$startReached = false;
    		$stepValue = 0;
    		foreach ($steps as $step) {
    			$countryParts = explode(':', $step);
    			if (count($countryParts) == 2) {
    				if ($startReached == true) {
    					break;
    				}
    				
    				if (trim($countryParts[0]) == $country) {
    					$startReached = true;
    					$step = substr($step, 3);
    				}
    			}
    			
    			if ($startReached == true || $legacyMode == true) {
					$parts = explode('=', $step);
					
					if (count($parts) < 2) {
						$this->getLog()->logError('Fee step data has wrong format: ' . $chargeFeeSteps);
					}
					else {
						$thresholdValue = $this->getApi()->currencyToSmallerUnit($parts[0]);
						
						if ($cartTotalPriceGross < $thresholdValue) {
							$stepValue = $parts[1];
							break;
						}
					}
    			}
				$start = false;
    		}
   		}
   		return (float) $stepValue;
    }
    
    

    /**
     * Test if $data is valid integer value
     * @param $data
     */
	public function isIntVal($data) {
		if (is_int($data) === true) {
			return true;
		}
		elseif (is_string($data) === true && is_numeric($data) === true) {
			return (strpos($data, '.') === false);
		}
		return false;
	}
	
	public function getRateSurchargeFormula($baseAmount, $interestRate, $rateCount) {
		//$baseAmount = Mage::helper('core')->formatPrice($baseAmount, false); // TODO: use format method on store object
		$baseAmount = strip_tags($baseAmount);
		return "($baseAmount x $interestRate x $rateCount) / 100";
	}
	
	public function getSerializedDues($dues) {
    	$serializedDues = '';
   		foreach ($dues as $due) {
        	if (!empty($serializedDues)) {
        		$serializedDues .= ',';
        	}
        	
        	if (array_key_exists('date', $due)) {
	        	$date = trim($due['date']);
	        	if (!empty($date)) {
	        		$serializedDues .= $date;
	        	}
        	}
        	
        	$serializedDues .= ':'.$due['value'];
        }
        return $serializedDues;
    }
    
    public function getCalculationResidualAmount($quote) {
    	// return cart specific costs
    	$baseAmount = $this->getCalculationBaseAmount($quote);
    	$address = $quote->getShippingAddress();
    	$res = $address->getGrandTotal() - $baseAmount;
    	return $res;
    }
    
    public function getCalculationBaseAmount($quote) {
    	$address = $quote->getShippingAddress();
    	
    	// we use subtotal incl. tax as caculation base (see Mage_Tax_Model_Sales_Total_Quote_Tax::fetch)
    	if ($address->getSubtotalInclTax() > 0) {
            $baseAmount = $address->getSubtotalInclTax();
		}
		else {
        	$baseAmount = $address->getSubtotal()
				+ $address->getTaxAmount()
				- $address->getShippingTaxAmount();    
		}
    	
		$discount = $this->calculateDiscount($address, $quote, true);
		$baseAmount -= $discount;
		return $baseAmount;
    }
    
	/**
	 * Calculate discount amount
	 * 
	 * @param unknown_type $address
	 * @param unknown_type $store
	 * @param unknown_type $inclTax
	 * @return unknown_type
	 */
 	public function calculateDiscount($address, $quote, $inclTax, $toSmallerUnit = false) {
    	if (!$address->getDiscountAmount() || $address->getDiscountAmount() == 0) {
    		return 0;
    	}
    	
    	$store = $quote->getStore();
    	
    	$discount = $address->getDiscountAmount() < 0 ? 
    		-$address->getDiscountAmount() : 
    		$address->getDiscountAmount();

		if ($inclTax) {
			//if ($address->getHiddenTaxAmount() > 0) {
			//	$discount += $address->getHiddenTaxAmount();
			//}
			//else {
				if ($address->getSubtotalInclTax() > 0) {
					$subtotalInclTax = $address->getSubtotalInclTax();
					$shippingBase = $this->calculateShippingPrice($address, $quote, true);
					
					$total = $subtotalInclTax - $discount + $shippingBase;
					if (round($address->getGrandTotal(), 2) < round($total, 2)) {
						$discount += ($total - $address->getGrandTotal());
					}
				}
			//} 
		}
		
    	return $toSmallerUnit ? $this->currencyToSmallerUnit($discount) : $discount;
    }
    
	/**
	 * Calculate shipping price
	 * 
	 * @param unknown_type $address
	 * @param unknown_type $quote
	 * @param unknown_type $inclTax
	 * @param unknown_type $toSmallerUnit
	 */
    public function calculateShippingPrice($address, $quote, $inclTax, $toSmallerUnit = false) {
    	$shipping = $address->getShippingAmount();
    	if ($inclTax) {
    		$shipping += $address->getShippingTaxAmount()
    			+ $quote->getBillpayChargedFee();

    		// Mageworx MultiFee addition 			
    		//if ($address->getMultifees()) {
    		//	$shipping += $address->getMultifees();
    		//}
    	}
    	else {
    		$shipping += $quote->getBillpayChargedFeeNet();
    		
    		// Mageworx MultiFee addition
    		//if ($address->getMultifeesExclTax()) {
    		//	$shipping += $address->getMultifeesExclTax();
    		//}
    	}
    	
    	return $toSmallerUnit ? $this->currencyToSmallerUnit($shipping) : $shipping;
    }
    
 /**
     *  Calculate and round monetary value in cent 
     *
     * @param unknown_type $floatingValue
     * @return unknown
     */
    public function currencyToSmallerUnit($floatingValue) {
    	$small = $floatingValue * 100;
    	return round($small);
    }
    
   
    /**
     * @param string $s
     * @return mixed
     */
    public function obfuscateAddressPart($str) {
    	$result = '';
		for ($i=0; $i<strlen($str); ++$i) {
			if (is_numeric($str[$i])) {
				$result .= '0';
			}
			else if (ctype_alpha($str[$i])) {
				$result .= 'x';
			}
			else if (in_array($str[$i], array(' ', '.',',','@'))) {
				$result .= $str[$i];
			}
		}
    	return $result;
    }
    
    /**
     * 
     * @param $paymentMethod
     * @param $quote
     * @param $moduleConfig
     * @param $isB2B
     */
    public function showPaymentMethod($paymentMethod, $quote, $moduleConfig, $isB2B = false) {
    	$allowedKey = 'is_allowed_' . $paymentMethod;
    	if ($isB2B) {
    		$allowedKey .= '_b2b';
    	}
    	
    	$minValueKey = 'min_' . $paymentMethod;
    	if ($isB2B) {
    		$minValueKey .= '_b2b';
    	}
    	
    	$staticLimitKey = 'static_limit_' . $paymentMethod;
    	if ($isB2B) {
    		$staticLimitKey .= '_b2b';
    	}
    	
    	if($moduleConfig[$allowedKey] == 1) {
    		if($this->getApi()->currencyToSmallerUnit($quote->getGrandTotal()) >= $moduleConfig[$minValueKey] &&
    			$this->getApi()->currencyToSmallerUnit($quote->getGrandTotal()) <= $moduleConfig[$staticLimitKey]) {
    			return true;
    		}
    	}
    	
    	return false;
    }
}