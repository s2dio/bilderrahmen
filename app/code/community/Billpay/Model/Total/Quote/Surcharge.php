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
class Billpay_Model_Total_Quote_Surcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    
	/**
	 * @return Billpay_Model_Session
	 */
	public function getSession() {
		return Mage::getSingleton('billpay/session');
	}
	
	/**
	 * @return string
	 */
	public function format($value) {
		return Mage::getSingleton('checkout/session')->getQuote()
			->getStore()->formatPrice($value);
	}
	    
	/**
	 * @return Billpay_Helper_Api
	 */
	private function getHelper() {
		return Mage::helper('billpay/api');
	}
	
	/**
	 * @return Billpay_Helper_Calculation
	 */
	private function getCalculation() {
		return Mage::helper('billpay/calculation');
	}	
	
    /**
     * @return Billpay_Helper_Log
     */
    public function getLog() {
    	return Mage::helper('billpay/log');
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
    	if ($address->getAddressType() == 'shipping' && $address->getQuote()->getIsActive()) {
    		$paymentMethod = $address->getQuote()->getPayment()->getMethod();

    		if (isset($paymentMethod) && $this->getHelper()->isBillpayRatPayment($paymentMethod)) {
    			$quote 		= $address->getQuote();
    			$baseAmount = $this->getCalculation()->getCalculationBaseAmount($quote);
    			$baseAmount = $this->getHelper()->currencyToSmallerUnit($baseAmount);
    			
    			if ($this->getSession()->validateRateOptions($baseAmount)) {
	    			$interestFormula= $this->getCalculation()->getRateSurchargeFormula(
	    				$this->format($this->getCalculation()->getCalculationBaseAmount($quote)),
	    				$this->getSession()->getInterestRate(),
	    				$this->getSession()->getBillpayRates()
	    			);
	    			
		            $address->addTotal(array(
		            	'area'=>'footer',
		                'code'=>$this->getCode(),
		                'title'=>Mage::helper('billpay')->__('billpay_rate_calculation_interest_add').
		                	' '.Mage::helper('billpay')->__('billpay_rate_for_text').' '.$this->getSession()->getBillpayRates().
		            		' '.Mage::helper('billpay')->__('billpay_rate_rate_text').
		            		'<br />'.$interestFormula,
		                'value'=>$this->getSession()->getSurchargeAmount()
		            ));
    			}
    			else {
    				$this->getSession()->clearCurrentRateOptions();
    			}
    		}
    	}
        return $this;
    }

}