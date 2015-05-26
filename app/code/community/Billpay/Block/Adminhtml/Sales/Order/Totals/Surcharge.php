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
class Billpay_Block_Adminhtml_Sales_Order_Totals_Surcharge extends Mage_Adminhtml_Block_Sales_Order_Totals_Item {

    /**
     * @return Billpay_Helper_Api
     */
    public function getApi() {
    	return Mage::helper('billpay/api');
    }
    
    public function getBig() {
    	return false;
    }
    
    public function getSurcharge() {
    	if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateSurcharge();
    	}
    	else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
    		$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateSurcharge();
    	}
    }
    
 	public function getTotalPaymentAmount() {
 		if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateTotalAmount();
    	}
 		else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
 			$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateTotalAmount();
    	}
    }
    
 	public function getTransactionFee() {
 		if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateFee();
    	}
 		else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
 			$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateFee();
    	}
    }
    
    public function getFeeTaxAmount() {
    	if ($this->getSource() instanceof Mage_Sales_Model_Order) {
    		return $this->getOrder()->getPayment()->getMethodInstance()->getInfoInstance()->getBillpayRateFeeTax();
    	}
    	else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
 			$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
 				return $this->getSource()->getBillpayRateFeeTax();
 		}
    }
    
    public function _beforeToHtml() {
        parent::_beforeToHtml();
        
        if($this->getOrder()) {
	        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
	        if($this->getApi()->isBillpayRatPayment($paymentMethod)) {
	        	return;
	        }
        }
        	
       	$this->setTemplate('');
    }
    
}