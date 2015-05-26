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
class Billpay_Block_Adminhtml_Sales_Order_Totals_Item extends Mage_Adminhtml_Block_Sales_Order_Totals_Item {

	private $_chargedFeeDisplayValue = null;
	private $_baseChargedFeeDisplayValue = null;
	
	private $_billpaySourceType = 0;
    
    /**
     * Get billpay api
     *
     * @return Billpay_Helper_Api
     */
    public function getApi() {
    	return Mage::helper('billpay/api');
    }
    
    public function getChargedFeeDisplayValue() {
    	return $this->_chargedFeeDisplayValue;
    }
    
    public function getBaseChargedFeeDisplayValue() {
    	return $this->_baseChargedFeeDisplayValue;
    }
    
    public function setBillpaySourceType($billpaySourceType) {
    	$this->_billpaySourceType = $billpaySourceType;
    }
    
    public function _beforeToHtml() {
        parent::_beforeToHtml();
        
        $order = $this->getOrder();
        
        $paymentMethod = $order->getPayment()->getMethod();
        if($order && $this->getApi()->isBillpayPayment($paymentMethod) && $order->getBillpayChargedFee() > 0) {
        	
        	if ($this->getApi()->isBillpayInvoicePayment($paymentMethod)) {
        		$this->setLabel($this->getApi()->__('billpay_rec_step_fee_text'));	
        	}
        	else {
        		$this->setLabel($this->getApi()->__('billpay_elv_step_fee_text'));
        	}
        	
        	/**
        	 * Billpay source types:
        	 * 1: order/invoice (read-only)
        	 * 2: creditmemo (create)
        	 * 3: creditmemo (view)
        	 */
        	
        	if ($this->getApi()->getConfigData('fee/display_incl_tax_admin', $order->getStoreId())) {
        		switch ($this->_billpaySourceType) {
        			case 1:
        				$this->_chargedFeeDisplayValue = $order->getBillpayChargedFee();
        				$this->_baseChargedFeeDisplayValue = $order->getBaseBillpayChargedFee();
        				break;
        			case 2:
        				$this->_chargedFeeDisplayValue = $order->getBillpayChargedFee() - $order->getBillpayChargedFeeRefunded();
        				$this->_baseChargedFeeDisplayValue = $order->getBaseBillpayChargedFee() - $order->getBaseBillpayChargedFeeRefunded();
        				break;
        			case 3:
        				$creditmemo = $this->getSource();
        				$this->_chargedFeeDisplayValue = $creditmemo->getBillpayChargedFeeRefunded();
        				$this->_baseChargedFeeDisplayValue = $creditmemo->getBaseBillpayChargedFeeRefunded();
        				break;
        		}
        	}
        	else {
        		switch ($this->_billpaySourceType) {
        			case 1:
        				$this->_chargedFeeDisplayValue = $order->getBillpayChargedFeeNet();
        				$this->_baseChargedFeeDisplayValue = $order->getBaseBillpayChargedFeeNet();
        				break;
        			case 2:
        				$this->_chargedFeeDisplayValue = $order->getBillpayChargedFeeNet() - $order->getBillpayChargedFeeRefundedNet();
        				$this->_baseChargedFeeDisplayValue = $order->getBaseBillpayChargedFeeNet() - $order->getBaseBillpayChargedFeeRefundedNet();
        				break;
        			case 3:
        				$creditmemo = $this->getSource();
        				$this->_chargedFeeDisplayValue = $creditmemo->getBillpayChargedFeeRefundedNet();
        				$this->_baseChargedFeeDisplayValue = $creditmemo->getBaseBillpayChargedFeeRefundedNet();
        				break;
        		}
        	}
			
			// ensure backward compability beacuse we switched to usage of base value
			if ($this->_baseChargedFeeDisplayValue <= 0 && $this->_chargedFeeDisplayValue && $this->_chargedFeeDisplayValue > 0) {
				$this->_baseChargedFeeDisplayValue = $this->_chargedFeeDisplayValue;
			}
        }
        else {
        	$this->setTemplate('');
        }
        
        if (is_null($this->_chargedFeeDisplayValue) || $this->_chargedFeeDisplayValue <= 0) {
        	$this->setTemplate('');
        }
    }
    
}