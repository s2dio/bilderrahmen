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
class Billpay_Model_Total_Quote_Total extends Mage_Sales_Model_Quote_Address_Total_Abstract {

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
	 * Get billpay api
	 *
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
	
    public function fetch(Mage_Sales_Model_Quote_Address $address) {
    	if ($address->getAddressType() == 'shipping' && $address->getQuote()->getIsActive()) {
    		$paymentMethod = $address->getQuote()->getPayment()->getMethod();

    		if (isset($paymentMethod) && $this->getHelper()->isBillpayRatPayment($paymentMethod)) {
	            $quote 		= $address->getQuote();
    			$baseAmount = $this->getCalculation()->getCalculationBaseAmount($quote);
    			$baseAmount = $this->getHelper()->currencyToSmallerUnit($baseAmount);
    			
    			if ($this->getSession()->validateRateOptions($baseAmount)) {
	    			$address->addTotal(array(
		            	'area'=>'footer',
		                'code'=>$this->getCode(),
		                'title'=>Mage::helper('billpay')->__('billpay_rate_calculation_partial_price'),
		                'value'=>$this->getSession()->getTotalPaymentAmount()
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