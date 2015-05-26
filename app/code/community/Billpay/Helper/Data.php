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
class Billpay_Helper_Data extends Mage_Payment_Helper_Data {

    /**
     * Get expected days till shipping
     *
     * @param $products
     * @return int
     */
	public function getExpectedDaysTillShipping($products) {
		return 0;
	}
	
	/**
	 * Get base amount for calculation of fee (for Magento 1.4.X - 1.6.X)
	 * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @return number
     */
 	public function getFeeBaseAmount(Mage_Sales_Model_Quote_Address $shippingAddress) {
    	return array_sum($shippingAddress->getAllTotalAmounts());
    }
    
    /**
     * Set fee and fee tax on shipping address object (for Magento 1.4.X - 1.6.X)
     * @param $shippingAddress
     * @param $baseNet
     * @param $baseGross
     * @param $net
     * @param $gross
     */
    public function setFeeOnShippingAddress($shippingAddress, $baseNet, $baseGross, $net, $gross) {
    	$billpayTaxAmount 		= ($gross - $net);
    	$billpayBaseTaxAmount	= ($baseGross - $baseNet);
    	
		// increase tax amount
		$shippingAddress->setTaxAmount($shippingAddress->getTaxAmount() + $billpayTaxAmount);
		$shippingAddress->setBaseTaxAmount($shippingAddress->getBaseTaxAmount() + $billpayBaseTaxAmount);
		
		$shippingAddress->setTotalAmount('billpay', $net);
		$shippingAddress->setBaseTotalAmount('billpay', $baseNet);
		$shippingAddress->addTotalAmount('tax', $billpayTaxAmount);
		$shippingAddress->addBaseTotalAmount('tax', $billpayBaseTaxAmount);
		
   		// We have to set the value for subtotal incl. tax here when a fee tax is present 
		// Otherwise the value will be calculated wrongly in Mage_Tax_Model_Sales_Total_Quote_Tax::fetch
		if (!$shippingAddress->getSubtotalInclTax() && $billpayTaxAmount > 0) {
			$subtotalInclTax = $shippingAddress->getSubtotal()+$shippingAddress->getTaxAmount()-$shippingAddress->getShippingTaxAmount() - $billpayTaxAmount;
			$shippingAddress->setSubtotalInclTax($subtotalInclTax);
		}
    }

    /**
     * Set fee and fee tax on invoice object (for Magento 1.4.X - 1.6.X)
     *
     * @param $invoice
     * @param $baseNet
     * @param $baseGross
     * @param $net
     * @param $gross
     */
    public function setFeeOnInvoice($invoice, $baseNet, $baseGross, $net, $gross) {
   		if (!$invoice->isLast()) {
    				
    		// increase tax amount
    		$invoice->setTaxAmount($invoice->getTaxAmount() + ($gross - $net));
    		$invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + ($baseGross - $baseNet));

    		// increase grand total
    		$invoice->setGrandTotal($invoice->getGrandTotal() + $gross);
    		$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseGross);
    	}
    	else {
    		// increase grand total
    		$invoice->setGrandTotal($invoice->getGrandTotal() + $net);
    		$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseNet);
    	}
    }

    /**
     * Get customer order history (only for Magento 1.6.X)
     *
     * @param $customerId
     *
     * @throws Exception
     * @return array
     */
	public function getCustomerOrderHistory($customerId) {
		try {
			$orders = Mage::getModel('sales/order')
				->getCollection()
				->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
				->addFieldToFilter('customer_id', $customerId)
				->setPage(1, 10)
				->load();
				  
			$ordersArray = array();
			foreach ($orders as $order) {
				$order->setMethod($order->getPayment()->getMethod());
				$ordersArray[] = $order->getData();
			}
    		
    		return $ordersArray;
		}
		catch (Exception $e) {
			$this->getLog()->logError('Error fetching order history from db:');
			$this->getLog()->logException($e);
			$errorMessage = $this->__('internal_error_occured');
			throw new Exception($errorMessage);
		}
	}
}