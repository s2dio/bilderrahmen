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
 * @category   Paymentnetwork
 * @package	Paymentnetwork_Sofortueberweisung
 * @copyright  Copyright (c) 2011 Payment Network AG, 2012 initOS GmbH & Co. KG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Observer.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';
class Paymentnetwork_Pnsofortueberweisung_Model_Observer extends Mage_Core_Model_Abstract {

	/**
	 * Pay-Event
	 * we will confirm the invoice with this method
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function sales_order_invoice_pay($observer) {
		
	    // dont trigger if we are notifyed
	    if(!empty($GLOBALS['isNotificationAction'])){
	        return $this;
	    }
	    
	    // get invoice
		$invoice = $observer->getEvent()->getInvoice();
		$order = $invoice->getOrder();
		$payment = $order->getPayment();
		$addinfo = $payment->getAdditionalInformation();
		$invoices = $invoice->getOrder()->hasInvoices();
		$method = $payment->getMethod();
		
		if($method != 'sofortrechnung' ){
			return $this;
		}

	    $transactionId = $payment->getAdditionalInformation('sofort_transaction');
		if(!empty($transactionId)) {
			
			$PnagInvoice = new PnagInvoice(Mage::getStoreConfig('payment/sofort/configkey'), $transactionId);
			
			$entity_type_model = Mage::getSingleton('eav/config')->getEntityType('invoice');
            $newInvoiceNumber = $entity_type_model->fetchNewIncrementId($invoice->getOrder()->getStoreId());
            $invoice->setIncrementId($newInvoiceNumber);
			
			$PnagInvoice->confirmInvoice($transactionId, $newInvoiceNumber );
			
            if($PnagInvoice->isError())  {
				Mage::throwException($PnagInvoice->getError());
			} else {
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully confirmed invoice: %s', $transactionId));
				$invoice->setTransactionId($transactionId);
				$payment->setAdditionalInformation("sofortrechnung_invoice_url", $PnagInvoice->getInvoiceUrl());
				$payment->save();
				return $this;
			}
		}
		
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not confirm invoice.'));
		return $this;
	}
	
	/**
	 * order save
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function sales_order_save_after($observer) {
	    
	    $order = $observer->getEvent()->getOrder();
	    
	    // HACK #1 lets fix refund
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('sales/order');
        $query = "UPDATE {$table} SET total_refunded = base_total_refunded WHERE entity_id = " . (int)$order->getId();
        $writeConnection->query($query);
	}
	
	/**
	 * cancel PNAG invoice
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function sales_order_payment_cancel($observer) {
		
	    // dont trigger if we are notifyed
	    if(!empty($GLOBALS['isNotificationAction'])){
	        return $this;
	    }
	    
	    //get payment
		$payment = $observer->getEvent()->getPayment();
		$method = $payment->getMethod();
		
		if($method != 'sofortrechnung' ){
			return $this;
		}

		$transactionId = $payment->getAdditionalInformation('sofort_transaction');
		if(!empty($transactionId)) {
			
			$PnagInvoice = new PnagInvoice(Mage::getStoreConfig('payment/sofort/configkey'), $transactionId);
			if($PnagInvoice->getStatus() != 'loss'){
			    $PnagInvoice->cancelInvoice($transactionId);
			}
			
            if($PnagInvoice->isError())  {
				Mage::throwException($PnagInvoice->getError());
			} else {
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully canceled invoice: %s', $transactionId));
				return $this;
			}
		}
		
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not cancel invoice.'));
		return $this;
				
	}
	
	/**
	 * did nothing at the moment
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function sales_order_payment_refund($observer) {
		$payment = $observer->getEvent()->getPayment();
		$creditmemo = $observer->getEvent()->getCreditmemo();
		$method = $payment->getMethod();
		
		return $this;
	}
}
