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
class Billpay_Model_Total_Invoice_Surcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract {
	
	/**
	 * @return Billpay_Helper_Api
	 */
	private function getHelper() {
		return Mage::helper('billpay/api');
	}
	
    public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
    	$order 		= $invoice->getOrder();
    	$payment 	= $order->getPayment(); 
    	$paymentMethod = $payment->getMethod();

    	if ($this->getHelper()->isBillpayRatPayment($paymentMethod)) {
    		$info = $payment->getMethodInstance()->getInfoInstance();
    		
    		// Attach total values to invoice
			$invoice->setBillpayRateSurcharge($info->getBillpayRateSurcharge());
			$invoice->setBillpayRateTotalAmount($info->getBillpayRateTotalAmount());
			$invoice->setBillpayRateFee($info->getBillpayRateFee());
			$invoice->setBillpayRateFeeNet($info->getBillpayRateFeeNet());
			$invoice->setBillpayRateFeeTax($info->getBillpayRateFeeTax());
			
			// increase tax amount
			//$feeTaxAmount = $info->getBillpayRateFee() - $info->getBillpayRateFeeNet();
	    	//$invoice->setTaxAmount($invoice->getTaxAmount() + $feeTaxAmount);
	    	//$invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $feeTaxAmount);
    	}
    	
        return $this;
    }
}