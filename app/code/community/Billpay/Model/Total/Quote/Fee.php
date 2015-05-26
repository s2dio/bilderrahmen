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
class Billpay_Model_Total_Quote_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract {

	/**
	 * Get billpay api
	 *
	 * @return Billpay_Helper_Api
	 */
	private function getHelper() {
		return Mage::helper('billpay/api');
	}
	
	/**
	 * Get billpay helper
	 *
	 * @return Billpay_Helper_Api
	 */
	private function getHelper1() {
		return Mage::helper('billpay');
	}

	/**
	 * Get billpay calculation helper
	 *
	 * @return Billpay_Helper_Calculation
	 */
	private function getCalculation() {
		return Mage::helper('billpay/calculation');
	}
	
	/**
	 * @return Billpay_Model_Session
	 */
	public function getSession() {
		return Mage::getSingleton('billpay/session');
	}

    /**
     * Get billpay logger
     *
     * @return Billpay_Helper_Log
     */
    public function getLog() {
    	return Mage::helper('billpay/log');
    }

	public function collect(Mage_Sales_Model_Quote_Address $address) {
		if ($address->getAddressType() == 'shipping') {
			$paymentMethod = $this->getPaymentMethod($address);

			if ($paymentMethod && $this->getHelper()->isBillpayPayment($paymentMethod)) {
				$address->getQuote()->setBillpayChargedFeeNet(0);
				$address->getQuote()->setBillpayChargedFee(0);
				$address->getQuote()->setBaseBillpayChargedFeeNet(0);
				$address->getQuote()->setBaseBillpayChargedFee(0);
						
				$b2b = $this->isB2BRequest($address, $paymentMethod);
								
				if ($this->getHelper()->isFeeChargeEnabled($paymentMethod, $b2b)) {
					$amount = $this->getHelper1()->getFeeBaseAmount($address);
					
					$country = $address->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code();
					$fee = $this->getCalculation()->getChargedFee($paymentMethod, $address->getQuote()->getStoreId(), $amount, $country, $b2b);

					if ($fee) {
						$baseNet = $fee[0];
						$baseGross = $fee[1];
						
						$net = $this->convertPrice($baseNet, $address->getQuote()->getStoreId());
						$gross = $this->convertPrice($baseGross, $address->getQuote()->getStoreId());

						if (isset($gross) && $gross > 0) {
							$address->getQuote()->setBillpayChargedFeeNet($net);
							$address->getQuote()->setBillpayChargedFee($gross);
							$address->getQuote()->setBaseBillpayChargedFeeNet($baseNet);
							$address->getQuote()->setBaseBillpayChargedFee($baseGross);	
							
							$this->getHelper1()->setFeeOnShippingAddress($address, $baseNet, $baseGross, $net, $gross);
							$this->saveAppliedTaxes($address, $paymentMethod, $gross, $net, $baseGross, $baseNet);
						}
					}
				}
			}
		}

		return $this;
	}

	public function fetch(Mage_Sales_Model_Quote_Address $address) {
		if ($address->getAddressType() == 'shipping') {
			$paymentMethod = $this->getPaymentMethod($address);

			if ($paymentMethod && $this->getHelper()->isBillpayPayment($paymentMethod)) {
				$quote = $address->getQuote();
				if ($this->getHelper()->getConfigData('fee/display_incl_tax_frontend', $quote->getStoreId())) {
					$feeCharged = $address->getQuote()->getBillpayChargedFee();
				}
				else {
					$feeCharged = $address->getQuote()->getBillpayChargedFeeNet();
				}
				 
				if (isset($feeCharged) && $feeCharged > 0) {
					$address->addTotal(array(
		                'code'=>$this->getCode(),
		                'title'=>Mage::helper('billpay')->__($paymentMethod . '_step_fee_text'),
		                'value'=> $feeCharged
		            ));
	    		}
    		}
    	}
        return $this;
    }
    
    protected function saveAppliedTaxes($address, $paymentMethod, $gross, $net, $baseGross, $baseNet) {
    	$calculator = Mage::getSingleton('tax/calculation');
		$taxClassId = $this->getHelper()->getConfigData('charge_fee_steps_tax_class', $address->getQuote()->getStoreId(), $paymentMethod);
		$taxRateRequest = $calculator->getRateRequest();
		$taxRateRequest->setProductClassId($taxClassId);
		$taxRateRequest->setStore($address->getQuote()->getStore());
		$rate = $calculator->getRate($taxRateRequest);
		
		$applied = $calculator->getAppliedRates($taxRateRequest);
		
		$taxAmount = $gross - $net;
        $baseTaxAmount = $baseGross - $baseNet;
		
		$this->_saveAppliedTaxes($address, $applied, $taxAmount, $baseTaxAmount, $rate);
    }
    
	/**
     * Copy from Mage_Tax_Model_Sales_Total_Quote_Tax
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @param   array $applied
     * @param   float $amount
     * @param   float $baseAmount
     * @param   float $rate
     */
    protected function _saveAppliedTaxes(Mage_Sales_Model_Quote_Address $address, $applied, $amount, $baseAmount, $rate)
    {
        $previouslyAppliedTaxes = $address->getAppliedTaxes();
        $process = count($previouslyAppliedTaxes);

        foreach ($applied as $row) {
            if ($row['percent'] == 0) {
                continue;
            }
            if (!isset($previouslyAppliedTaxes[$row['id']])) {
                $row['process']     = $process;
                $row['amount']      = 0;
                $row['base_amount'] = 0;
                $previouslyAppliedTaxes[$row['id']] = $row;
            }

            if (!is_null($row['percent'])) {
                $row['percent'] = $row['percent'] ? $row['percent'] : 1;
                $rate = $rate ? $rate : 1;

                $appliedAmount      = $amount/$rate*$row['percent'];
                $baseAppliedAmount  = $baseAmount/$rate*$row['percent'];
            } else {
                $appliedAmount      = 0;
                $baseAppliedAmount  = 0;
                foreach ($row['rates'] as $rate) {
                    $appliedAmount      += $rate['amount'];
                    $baseAppliedAmount  += $rate['base_amount'];
                }
            }


            if ($appliedAmount || $previouslyAppliedTaxes[$row['id']]['amount']) {
                $previouslyAppliedTaxes[$row['id']]['amount']       += $appliedAmount;
                $previouslyAppliedTaxes[$row['id']]['base_amount']  += $baseAppliedAmount;
            } else {
                unset($previouslyAppliedTaxes[$row['id']]);
            }
        }
        $address->setAppliedTaxes($previouslyAppliedTaxes);
    }
    
    private function getPaymentMethod($address) {
    	$isOneStepCheckout = $this->getHelper()->isOneStepCheckout($address->getQuote()->getStoreId());
    	
    	if ($address->getQuote()->getIsActive()) {
    		return $address->getQuote()->getPayment()->getMethod();
    	}
    	else if ($isOneStepCheckout) {
    		$payment = Mage::app()->getRequest()->getPost('payment');
    		if (is_array($payment) && array_key_exists('method', $payment)) {
    			return $payment['method'];
    		}
    	}
    	
    	return '';
    }
    
    private function isB2BRequest($address, $paymentMethod) {
    	$b2b = false;
    	
    	$customerGroup = $this->getSession()->getCustomerGroup();
    	$postPayment = Mage::app()->getRequest()->getPost("payment");
    	
		if($paymentMethod == 'billpay_rec' && 
			$this->getHelper()->getConfigData('allowed_customer_group', $address->getQuote()->getStoreId(), $paymentMethod) == 'b2b') {
			$b2b = true;
		}
		else if(is_array($postPayment) && array_key_exists('billpay_rec_customer_group', $postPayment)) {
			if($postPayment['billpay_rec_customer_group'] == 'b2b') {
				$b2b = true;
			}
		}
		else if ($customerGroup && $customerGroup == 'b') {
			$b2b = true;
		}
		
		return $b2b;
    }

    private function convertPrice($price, $storeId) {
    	$store = $this->getCalculation()->getStore($storeId);
    	$price = $store->convertPrice($price, false, false);
    	return $store->roundPrice($price);
    }
}