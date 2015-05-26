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
class Billpay_Model_Total_Invoice_Fee extends Mage_Sales_Model_Order_Invoice_Total_Abstract {
	
	/**
	 * Get billpay api
	 *
	 * @return Billpay_Helper_Api
	 */
	private function getHelper() {
		return Mage::helper('billpay/api');
	}
	
	/**
     * Get billpay logger
     *
     * @return Billpay_Helper_Log
     */
    public function getLog() {
    	return Mage::helper('billpay/log');
    }
	
	
    public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
    	$order = $invoice->getOrder();
    	$paymentMethod = $order->getPayment()->getMethod();

    	if ($this->getHelper()->isBillpayPayment($paymentMethod)) {
    		$net = $order->getBillpayChargedFeeNet();
    		$gross = $order->getBillpayChargedFee();
    		$baseNet = $order->getBaseBillpayChargedFeeNet();
    		$baseGross = $order->getBaseBillpayChargedFee();

    		if ($this->getHelper()->getConfigData('fee/display_incl_tax_admin', $order->getStoreId())) {
	    		$invoice->setBillpayChargedFeeAmount($gross);
	    		$invoice->setBaseBillpayChargedFeeAmount($baseGross);
    		}
    		else {
    			$invoice->setBillpayChargedFeeAmount($net);
    			$invoice->setBaseBillpayChargedFeeAmount($baseNet);
    		}
    		
    		if (isset($gross) && $gross > 0) {
    			$feeTaxAmount 		= $gross - $net;
    			$baseFeeTaxAmount	= $baseGross - $baseNet;
    			
    			Mage::helper('billpay')->setFeeOnInvoice($invoice, $baseNet, $baseGross, $net, $gross);
    			
    			// We have to set the value for subtotal incl. tax here when a fee tax is present 
				// Otherwise the value will be calculated wrongly in Mage_Tax_Block_Sales_Order_Tax::_initSubtotal
				if (!$invoice->getSubtotalInclTax() && $feeTaxAmount > 0) {
					$subtotalInclTax = $invoice->getSubtotal()+$invoice->getTaxAmount()-$invoice->getShippingTaxAmount() - $feeTaxAmount;
					$invoice->setSubtotalInclTax($subtotalInclTax);
				}

	    		// hack start (only valid for complete activation!)
	    		//$this->adjustFeeAmount($invoice, $feeTaxAmount, $baseFeeTaxAmount);
    		}
    	}
    	
        return $this;
    }
    
	private function adjustFeeAmount($invoice, $feeTaxAmount, $baseFeeTaxAmount) {
    	$order = $invoice->getOrder();
		
		$feeTaxAmount = round($feeTaxAmount, 2);
    	$residualTax = round($order->getGrandTotal() - $invoice->getGrandTotal(), 2);
		
	    if ((string)(float)$residualTax == (string)(float)$feeTaxAmount) {
	    	$this->getLog()->logDebug("Adjusting fee amount. Order: " . 
	    		$order->getGrandTotal() . ", Invoice: " . $invoice->getGrandTotal());
			$invoice->setGrandTotal(round($invoice->getGrandTotal() + $feeTaxAmount, 2));	
	    }
	    
	    $baseResidualTax = round($order->getBaseGrandTotal() - $invoice->getBaseGrandTotal(), 2);
	    if ((string)(float)$baseResidualTax == (string)(float)$baseFeeTaxAmount) {
	    	$this->getLog()->logDebug("Adjusting base fee amount. Order: " . 
	    		$order->getBaseGrandTotal() . ", Invoice: " . $invoice->getBaseGrandTotal());
	    	$invoice->setBaseGrandTotal(round($invoice->getBaseGrandTotal() + $baseFeeTaxAmount, 2));
	    }
    }

}