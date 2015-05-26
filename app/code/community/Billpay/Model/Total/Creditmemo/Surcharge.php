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
class Billpay_Model_Total_Creditmemo_Surcharge extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract {
	
	/**
	 * @return Billpay_Helper_Api
	 */
	private function getHelper() {
		return Mage::helper('billpay/api');
	}
	
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo) {
    	$order 		= $creditmemo->getOrder();
    	$invoice	= $order->getInvoiceCollection()->getFirstItem();
    	$payment 	= $order->getPayment(); 
    	$paymentMethod = $payment->getMethod();

    	if ($this->getHelper()->isBillpayRatPayment($paymentMethod)) {
    		$info = $payment->getMethodInstance()->getInfoInstance();
    		
    		// Temporary set creditmemo amount to current amount (will be overriden after partialCancel-request was successful)
			$creditmemo->setBillpayRateSurcharge($info->getBillpayRateSurcharge());
			$creditmemo->setBillpayRateFee($info->getBillpayRateFee());
			$creditmemo->setBillpayRateFeeNet($info->getBillpayRateFeeNet());
			$creditmemo->setBillpayRateTotalAmount($info->getBillpayRateTotalAmount());
			$creditmemo->setBillpayRateFeeTax($info->getBillpayRateFeeTax());
			
    		// TODO: ...
    		/*if ($diff < 0 && -$diff = $feeTaxAmount) {		// must be a full cancel
				$creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $feeTaxAmount);
			}
			else if ($diff == 0) {
				$creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $feeTaxAmount);
			}
			
			$diffBase = $order->getBaseGrandTotal() - $order->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal();
			if ($diffBase < 0 && -$diffBase = $feeTaxAmount) {		// must be a full cancel
				$creditmemo->setBaseGrandTotal($creditmemo->getGrandTotal() - $feeTaxAmount);
			}
			else if ($diff == 0) {
				$creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $feeTaxAmount);
			}*/
    	}
    	
        return $this;
    }
}