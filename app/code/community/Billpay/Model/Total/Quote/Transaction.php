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
class Billpay_Model_Total_Quote_Transaction extends Mage_Sales_Model_Quote_Address_Total_Abstract {

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
		return Mage::helper('core')
			->formatPrice($value, false);	// TODO: use format method of store object
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
    
   /* public function collect(Mage_Sales_Model_Quote_Address $address) {
    	if ($address->getAddressType() == 'shipping' && $address->getQuote()->getIsActive()) {
    		$paymentMethod = $address->getQuote()->getPayment()->getMethod();

    		if ($this->getHelper()->isBillpayRatPayment($paymentMethod)) {
    			$feeAmountGross = $this->getSession()->getTransationFee();
    			
    			if ($feeAmountGross) {
    				$feeTaxAmount = $this->getSession()->getTransationFeeTaxAmount();
	    			
    				// increase tax amount
	    			$address->setTaxAmount($address->getTaxAmount() + $feeTaxAmount);
	    			$address->setBaseTaxAmount($address->getBaseTaxAmount() + $feeTaxAmount);
    			}
    		}
    	}
    }*/

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
    	if ($address->getAddressType() == 'shipping' && $address->getQuote()->getIsActive()) {
    		$paymentMethod = $address->getQuote()->getPayment()->getMethod();

    		if (isset($paymentMethod) && $this->getHelper()->isBillpayRatPayment($paymentMethod)) {
    			$quote 		= $address->getQuote();
    			
    			if ($this->getSession()->getTransationFee() > 0) {
	    			$title = $this->getHelper()->__('billpay_rate_transaction_fee');
	    			$feeTaxAmount = $this->getSession()->getTransationFee() - $this->getSession()->getTransationFeeNet();
	    			if ($feeTaxAmount > 0) {
		    				$title .= '<br >('.$this->getHelper()->__('billpay_rate_included_tax').': ' . $this->format($feeTaxAmount) . ')';
	    			}
	    			
	    			$address->addTotal(array(
	           			'area'=>'footer',
	               		'code'=>$this->getCode(),
	                	'title'=>$title,
	                	'value'=>$this->getSession()->getTransationFee()
	            	));
    			}
    		}
    	}
        return $this;
    }

}