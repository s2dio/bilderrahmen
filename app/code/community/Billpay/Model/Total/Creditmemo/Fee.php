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
class Billpay_Model_Total_Creditmemo_Fee extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract {
	
	/**
	 * Get billpay api
	 *
	 * @return Billpay_Helper_Api
	 */
	private function getHelper() {
		return Mage::helper('billpay/api');
	}
	
    /**
     * Get billpay calculation helper
     * 
     * @return Billpay_Helper_Calculation
     */
    private function getCalculation() {
    	return Mage::helper('billpay/calculation');
    }
	
	
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo) {
    	$order = $creditmemo->getOrder();
    	$paymentMethod = $order->getPayment()->getMethod();
    	
    	if ($this->getHelper()->isBillpayPayment($paymentMethod)) {
    		$action = Mage::app()->getRequest()->getActionName();
    		
    		switch($action) {
    			case 'updateQty':
    			case 'new';
    				$gross 		= $order->getBillpayChargedFee();
		    		$net 		= $order->getBillpayChargedFeeNet();
		    		$baseGross 	= $order->getBaseBillpayChargedFee();
		    		$baseNet 	= $order->getBaseBillpayChargedFeeNet();
		    		
		    		$refundable 	= $order->getBillpayChargedFee() - $order->getBillpayChargedFeeRefunded();
		    		$baseRefundable = $order->getBaseBillpayChargedFee() - $order->getBaseBillpayChargedFeeRefunded();
		    		
		        	$refundableTax 		= $refundable - ($order->getBillpayChargedFeeNet() - $order->getBillpayChargedFeeRefundedNet());
		        	$baseRefundableTax 	= $baseRefundable - ($order->getBaseBillpayChargedFeeNet() - $order->getBaseBillpayChargedFeeRefundedNet());
		    		
		    		// increase tax amount
			    	$creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $refundableTax);
				    $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseRefundableTax);
			    		
			    	// increase grand total
			    	$creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $refundable);
			    	$creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseRefundable);
			    	
    				// We have to set the value for subtotal incl. tax here when a fee tax is present 
					// Otherwise the value will be calculated wrongly in Mage_Tax_Block_Sales_Order_Tax::_initSubtotal
					if (!$creditmemo->getSubtotalInclTax() && $refundableTax > 0) {
						$subtotalInclTax = $creditmemo->getSubtotal()+$creditmemo->getTaxAmount()-$creditmemo->getShippingTaxAmount() - $refundableTax;
						$creditmemo->setSubtotalInclTax($subtotalInclTax);
					}
    				break;
    				
    			case 'save':
    				$data = Mage::app()->getRequest()->getPost('creditmemo');
		    		if (isset($data)) {
						if(array_key_exists('billpay_charged_fee_refund', $data)) {
							$storeId = $creditmemo->getOrder()->getStore()->getStoreId();
							$baseChargedFeeRefunded 	= Mage::app()->getStore()->roundPrice($data['billpay_charged_fee_refund']);
				
							if ($baseChargedFeeRefunded < 0) {
								Mage::throwException('Refunded billpay fee must not be smaller than zero');
							}
							
							$baseOrderGross 		= $order->getBaseBillpayChargedFee();
							$baseOrderNet 			= $order->getBaseBillpayChargedFeeNet();
							$orderGross 			= $order->getBillpayChargedFee();
							$orderNet	 			= $order->getBillpayChargedFeeNet();
							
							$baseAllowedRefund = $baseOrderGross - $order->getBaseBillpayChargedFeeRefunded();
							
							if ((string)(float)$baseChargedFeeRefunded > (string)(float)$baseAllowedRefund) {
								$baseAllowedRefund = $order->formatBasePrice($baseAllowedRefund);
								Mage::throwException("Maximum billpay fee amount to refund is: $baseAllowedRefund");
							}
							if ($baseOrderGross > 0 && $baseOrderNet > 0) {
								if ($this->getHelper()->getConfigData('fee/display_incl_tax_admin', $storeId)) {
									$part = $baseChargedFeeRefunded/$baseOrderGross;
									$baseChargedFeeRefundedNet = Mage::app()->getStore()->roundPrice($baseOrderNet*$part);
								}
								else {
									$part = $baseChargedFeeRefunded/$baseOrderNet;
									$baseChargedFeeRefundedNet = $baseChargedFeeRefunded;
									$baseChargedFeeRefunded = Mage::app()->getStore()->roundPrice($baseOrderGross*$part);
								}
								
								$chargedFeeRefunded	 	= Mage::app()->getStore()->roundPrice($orderGross*$part);
								$chargedFeeRefundedNet	= Mage::app()->getStore()->roundPrice($orderNet*$part);
								
								$allowedRefund = $orderGross - $order->getBillpayChargedFeeRefunded();
								$allowedRefundNet = $orderNet - $order->getBillpayChargedFeeRefundedNet();
								if ($chargedFeeRefunded > $allowedRefund) {
									$chargedFeeRefunded = $allowedRefund;
								}
								
								if ($chargedFeeRefundedNet > $allowedRefundNet) {
									$chargedFeeRefundedNet = $allowedRefundNet;
								}
								
								$order->setBaseBillpayChargedFeeRefunded($order->getBaseBillpayChargedFeeRefunded() + $baseChargedFeeRefunded);
					        	$order->setBaseBillpayChargedFeeRefundedNet($order->getBaseBillpayChargedFeeRefundedNet() + $baseChargedFeeRefundedNet);
					        	$order->setBillpayChargedFeeRefunded($order->getBillpayChargedFeeRefunded() + $chargedFeeRefunded);
					        	$order->setBillpayChargedFeeRefundedNet($order->getBillpayChargedFeeRefundedNet() + $chargedFeeRefundedNet);
								
								$creditmemo->setBillpayChargedFeeRefunded($chargedFeeRefunded);
								$creditmemo->setBillpayChargedFeeRefundedNet($chargedFeeRefundedNet);
								$creditmemo->setBaseBillpayChargedFeeRefunded($baseChargedFeeRefunded);
								$creditmemo->setBaseBillpayChargedFeeRefundedNet($baseChargedFeeRefundedNet);
								
								$creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseChargedFeeRefunded);
								$creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $chargedFeeRefunded);
								$creditmemo->setTaxAmount($creditmemo->getTaxAmount() + ($chargedFeeRefunded - $chargedFeeRefundedNet));
								$creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + ($baseChargedFeeRefunded - $baseChargedFeeRefundedNet));
							}
						}
					}
    				
    				break;
    		}
    	}
    	
        return $this;
    }

}