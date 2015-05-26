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
 * @copyright  Copyright (c) 2011 Payment Network AG, 2013 initOS GmbH & Co. KG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: SofortController.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

/**
 * just log all errors for notification
 * 
 * @param string $no
 * @param string $str
 * @param string $file
 * @param string $line
 */
function sofort_notification_error($no,$str,$file,$line){
    $message = $no . " " . $str . " in '". $file .":".$line."'";
    Mage::log($message,Zend_Log::ERR,'sofort_error.log');
    // force to send error back
    if($no == E_ERROR || $no = E_CORE_ERROR || $no = E_COMPILE_ERROR || $no = E_USER_ERROR ){
        header("HTTP/1.1 500 Internal Server Error");
        echo "something went wrong"; // just to force not to have a empty request
        die();
    }    
}

class Paymentnetwork_Pnsofortueberweisung_SofortController extends Mage_Core_Controller_Front_Action
{
	
	protected $_redirectBlockType = 'pnsofortueberweisung/pnsofortueberweisung';
	protected $mailAlreadySent = false;
		
	/**
	 * when customer selects payment method
	 */
	public function redirectAction()
	{
		$session = $this->getCheckout();
		Mage::log($session); 
		$session->setSofortQuoteId($session->getQuoteId());
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Sofortueberweisung payment loaded'))->setIsVisibleOnFront(false);
		$order->save();

		$payment = $order->getPayment()->getMethodInstance();
		$url = $payment->getUrl();
		$this->getResponse()->setRedirect($url);
		
		$session->unsQuoteId();
	}
	
	/**
	 * when customer returns after transaction
	 * used by iDeal, Lastschriftsofort, sofortlastschrift, Rechnung
	 */
	public function returnAction()
	{
		if (!$this->getRequest()->isGet()) {
			$this->norouteAction();
			return;
		}
		$response = $this->getRequest()->getParams();	
		
		if(empty($response['orderId'])) {
			$this->_redirect('pnsofortueberweisung/pnsofortueberweisung/error');
		} else {
		    
		    $waitingStatus = Mage::getStoreConfig('payment/pnsofortueberweisung/order_status');
		    
		    $order = Mage::getModel('sales/order')->loadByIncrementId($response['orderId']);
		    $paymentCode = $order->getPayment()->getMethod();
		    
		    $payment = $order->getPayment();
		    $transaction = $payment->getAdditionalInformation('sofort_transaction');

		    // load current transaction data
		    $transData = new SofortLib_TransactionData(Mage::getStoreConfig('payment/sofort/configkey'));
		    $transData->setTransaction($transaction)->sendRequest();		   		   
		    
		    // customer returns status not clear
		    if($transData->getStatus() && $transData->getStatus() != 'loss'){
    		    if ($paymentCode == 'pnsofortueberweisung'){
    		        $waitingStatus = Mage::getStoreConfig('payment/pnsofortueberweisung/order_status');
    		    } elseif ($paymentCode == 'sofortrechnung'){
    		        $waitingStatus = Mage::getStoreConfig('payment/sofort/sofortrechnung_order_status_waiting');		    
    		    } elseif ($paymentCode == 'pnsofort'){
    		        $waitingStatus = Mage::getStoreConfig('payment/sofort/pnsofort_order_status');
    		    } elseif ($paymentCode == 'lastschriftsofort'){
    		        $waitingStatus = Mage::getStoreConfig('payment/sofort/lastschriftsofort_order_status_refund');
    		    } elseif ($paymentCode == 'sofort_ideal'){
    		        $waitingStatus = Mage::getStoreConfig('payment/sofort_ideal/order_status_waiting');
    		    }
    		    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		    } else {
		        // if we don't no the status on sofort.com, in the shop it well not be changed
		        $waitingStatus = 'unchanged';
		    }
		    
    		if($waitingStatus == 'unchanged'){
    		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer returned successfully'));
    		} else {
    		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer returned successfully'), $waitingStatus);
    		} 
    		$order->save();
		    
		    $session = $this->getCheckout();	
    		$session->setQuoteId($session->getSofortQuoteId(true));
    		$session->getQuote()->setIsActive(false)->save();
    		$session->setData('sofort_aborted', 0);
			$this->_redirect('checkout/onepage/success', array('_secure'=>true));
		}
	}
	
	/**
	 * Customer returns after sofortvorkasse transaction
	 */
	public function returnSofortvorkasseAction() 
	{
		// Vorkasse was removed
		$this->_redirect('pnsofortueberweisung/pnsofortueberweisung/error');
		return;
			
	}
	
	/**
	 * 
	 * customer canceled payment
	 */
	public function errorAction()
	{
	    // prevent to submit cancel back
	    $GLOBALS['isNotificationAction'] = true;
	    
		$session = $this->getCheckout();	
		$session->setQuoteId($session->getSofortQuoteId(true));
		$session->getQuote()->setIsActive(true)->save();
		
		$order = Mage::getModel('sales/order');
		$order->load($this->getCheckout()->getLastOrderId());
		// be sure that order can cancel
		$order->setState('sofort');		
		$order->cancel();
		$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Cancelation of payment')); 
		$order->save();

		if(!($session->getData('sofort_aborted') == 1)){
			$session->setData('sofort_aborted', 0);
		}
			
		$session->addNotice(Mage::helper('pnsofortueberweisung')->__('Cancelation of payment'));
		$this->_redirect('checkout/cart');		
		return;	
	}	
	
	/**
	 * notification about status change
	 */
	public function notificationAction()
	{
	    set_error_handler('sofort_notification_error');
	    
	    // prevent to submit confirm/cancel back
	    $GLOBALS['isNotificationAction'] = true;
	    
		$response = $this->getRequest()->getParams();	
		$orderId = $response['orderId'];
		$secret = $response['secret'];

		// read notofication
		$sofort = new SofortLib_Notification();
		$transaction = $sofort->getNotification(); 

	    //no valid parameters/xml
		if(empty($orderId) || empty($transaction) ) {
		    Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ . " - " . $orderId . " - " . $transaction);
			return;
		}
		if( $sofort->isError() ){
		    Mage::log($sofort->getError() ,Zend_Log::ERR,'sofort_error.log');
		}

		// load current transaction data
		$transData = new SofortLib_TransactionData(Mage::getStoreConfig('payment/sofort/configkey'));
		$transData->setTransaction($transaction)->sendRequest();
		
		if($transData->isError()) {
			Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ . $transData->getError());
			return;
		}
		
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);
		$paymentObj = $order->getPayment()->getMethodInstance();		
		$payment = $order->getPayment();
		
		//data of transaction doesn't match order
		if($payment->getAdditionalInformation('sofort_transaction') != $transaction 
		|| $payment->getAdditionalInformation('sofort_secret') != $secret ) {
			Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ );
			return;
		}

		// BUGFIX
		// if notification for confirm + refund got the same, we need to confirm first
		if($transData->isSofortrechnung() && $transData->isRefunded() && !$order->hasInvoices() ) {
		    $this->_transactionConfirmed($transData, $order, true);
		    // reload order
		    $order = Mage::getModel('sales/order');
		    $order->loadByIncrementId($orderId);
		}
		
		// check if order was edit
	    $sofortRechnung = Mage::getModel('pnsofortueberweisung/sofortrechnung');
	    $sofortRechnung->updateOrderFromTransactionData($transData, $order);
		
		
		
		// check if something other change
		if( $payment->getAdditionalInformation('sofort_lastchanged') === $this->_getLastChanged($transData) ) {
		    return;
		}

		$payment->setAdditionalInformation('sofort_lastchanged', $this->_getLastChanged($transData))->save();
		
		// kauf auf Rechnung
		if($transData->isSofortrechnung()) {
		    if($transData->isLoss()) {
    			$this->_transactionLoss($transData, $order);
		    } elseif($transData->isPending() && $transData->getStatusReason() == 'confirm_invoice') {
    			$this->_transactionUnconfirmed($transData, $order);	
		    } elseif($transData->isPending()) { 
    			$this->_transactionConfirmed($transData, $order);
		    } elseif($transData->isReceived()) {
    			//don't do anything
		    } elseif($transData->isRefunded()) {
    			$this->_transactionRefunded($transData, $order);
		    } else {
		        //uups
    			$order->addStatusHistoryComment($transData->getStatus() . " " . $transData->getStatusReason());
		    }   
		// sofortueberweisung, lastschrift
		} else {
		    if($transData->isLoss()) {
    			$this->_transactionLoss($transData, $order);		    
		    } elseif($transData->isPending()) { 
    			$this->_transactionConfirmed($transData, $order);		  
		    } elseif($transData->isReceived()) {
		        // no status change on received
    			$this->_transactionReceived($transData, $order);
		    } elseif($transData->isRefunded()) {
    			$this->_transactionRefunded($transData, $order);
		    } else {
		        //uups
    			$order->addStatusHistoryComment($transData->getStatus() . " " . $transData->getStatusReason());
		    }
		}

		$order->save();
	}
	
	/**
	 * notification about status change for iDEAL
	 */
	public function notificationIdealAction()
	{
	    // get response
	    $response = $this->getRequest()->getParams();	
		$orderId = $response['orderId'];
		
		// get order
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);
		$paymentObj = $order->getPayment()->getMethodInstance();		
		$payment = $order->getPayment();
		
		// get post
		$post = $this->getRequest()->getPost();
		list($userid, $projectid) = explode(':', Mage::getStoreConfig('payment/sofort_ideal/configkey'));
		// load transaction Data
		$transData = new SofortLib_ClassicNotification($userid, $projectid, Mage::getStoreConfig('payment/sofort_ideal/notification_password'));
	    $transData->getNotification($post);
	    
	    // hash not matched
	    if($transData->isError()){
	        Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ );
	        return;
	    }
	    if($payment->getAdditionalInformation('sofort_transaction')){
	        // wrong transaction id
	        if($payment->getAdditionalInformation('sofort_transaction') != $transData->getTransaction()){
	            Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ );
	            return;
	        } 	        
	    }else{
	        // store transaction
	        $payment->setAdditionalInformation('sofort_transaction',$transData->getTransaction());
	        $payment->save();
	    }
	    
	    // check if something change
		if( $payment->getAdditionalInformation('sofort_lastchanged') === $this->_getLastChanged($transData) ) {
		    return;
		}

		$payment->setAdditionalInformation('sofort_lastchanged', $this->_getLastChanged($transData))->save();
		
		/*
		 * payment was receiced
		 * - mark as pay
		 * - update order status
		 * - make visible frontend
		 * - send customer email
		 */
		if($transData->getStatus() =='received'){
		    $payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_SUCCESS);
		    $payment->save();
		    	    
    		$order->setPayment($payment);
    		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);			
    		$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transData->getTransaction()), $paymentObj->getConfigData('order_status'))
    					->setIsVisibleOnFront(true);
    		
            // send email to customer if not send already					
    		if(!$order->getEmailSent()) {
    		    $order->setEmailSent(true);
    			$order->save();
    			$order->sendNewOrderEmail();
    		}
			$order->save();
			
		}
		/*
		 * pending payment
		 * - just save transaction id before
		 */
	    if($transData->getStatus() =='pending'){
	        $newStatus = $paymentObj->getConfigData('order_status_waiting');
	        if($newStatus == 'unchanged'){
	            $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Waiting for money'));
	        } else {
	            $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Waiting for money'), $newStatus);
	        }		    
			$order->save();
		}
		/*
		 * transaction is loss to various reasons
		 * - cancel order 
		 * - make visible frontend
		 */
	    if($transData->getStatus() =='loss'){
	        // be sure that order can cancel
		    $order->setState('sofort');	
		    $order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer canceled payment'))->setIsVisibleOnFront(true);
			$order->save();
		}
	    
	}
	
	
	/**
	 * execute if transaction was loss
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionLoss($transData, $order) {
		$payment = $order->getPayment();
		
		if($transData->isLastschrift()) {

			$payment->setParentTransactionId($transData->getTransaction())
				->setShouldCloseParentTransaction(true)
				->setIsTransactionClosed(0)
				->registerRefundNotification($transData->getAmount());			

			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer returned payment'))->setIsVisibleOnFront(true);
			$order->save();
		} elseif($transData->isSofortrechnung()) {
		    // be sure that order can cancel
		    $order->setState('sofort');	
			$order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Successfully canceled invoice: %s', $transData->getTransaction()))->setIsVisibleOnFront(true);
		} elseif($transData->isSofortueberweisung()) {
		    $lossStatus = Mage::getStoreConfig('payment/sofort/pnsofort_order_status_loss');
		    $order->setState('sofort');
		    if($newStatus == 'unchanged'){
		        $order->addStatusHistoryComment($transData->getStatus() . " " . $transData->getStatusReason());
		    }else{
		        $order->addStatusHistoryComment($transData->getStatus() . " " . $transData->getStatusReason(), $lossStatus);
		    }
		} else {
		    // be sure that order can cancel
		    $order->setState('sofort');	
			$order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer canceled payment'))->setIsVisibleOnFront(true);
		}
		$order->save();
	}
	
	/**
	 * unconfirmed transaction
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @param boolean $forceUpdate = false to gerate update
	 * @return void
	 */
	private function _transactionUnconfirmed($transData, $order, $forceUpdate = false) {
		$payment = $order->getPayment();
		$transaction = $transData->getTransaction();
		$statusReason = $transData->getStatusReason();
		
		// rechnung
		if ( $transData->isSofortrechnung() 
		           && ($statusReason == 'confirm_invoice' || $forceUpdate)) {
			$order->setState('sofort');

			//customer may have changed the address during payment process
			$address = $transData->getInvoiceAddress();
			$order->getBillingAddress()
				->setStreet($address['street'] . ' ' . $address['street_number'])
				->setFirstname($address['firstname'])
				->setLastname($address['lastname'])
				->setCompany($address['company']) 
				->setPostcode($address['zipcode'])
				->setCity($address['city'])
				->setCountryId($address['country_code']);

			$address = $transData->getShippingAddress();
			$order->getShippingAddress()
				->setStreet($address['street'] . ' ' . $address['street_number'])
				->setFirstname($address['firstname'])
				->setLastname($address['lastname'])
				->setCompany($address['company'])
				->setPostcode($address['zipcode'])
				->setCity($address['city'])
				->setCountryId($address['country_code']);

			$order->save();
			
			$waitingStatus = Mage::getStoreConfig('payment/sofort/sofortrechnung_order_status_waiting');
			if($waitingStatus == 'unchanged'){
			    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment successfull. Invoice needs to be confirmed.', $transaction))->setIsCustomerNotified(true);
			} else {
			    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment successfull. Invoice needs to be confirmed.', $transaction), $waitingStatus)->setIsCustomerNotified(true);    
			}			
			$order->setIsVisibleOnFront(true);

			if(!$order->getEmailSent()){
			    $order->setEmailSent(true);
			    $order->save();
			    $order->sendNewOrderEmail();
			}
		} else {
		    // mark for notify
		    $order->setState('sofort');
		}
		$order->save();	
	}
	
	/**
	 * execute if transaction was confirmed
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @param boolean $forceInvoice = false to create invoice for rechnung, ignoring transaction status
	 * @return void
	 */
	private function _transactionConfirmed($transData, $order, $forceInvoice = false) {
		
	    // unconfirmed notification not process
	    // only rechnung needed
	    if( $order->getState() != 'sofort' 
	        && ( $transData->isSofortrechnung()) ){
	        $this->_transactionUnconfirmed($transData, $order, true);
	    }
	    
	    $payment = $order->getPayment();
		$paymentObj = $order->getPayment()->getMethodInstance();
		$amount = $forceInvoice ? $transData->getAmount() + $transData->getAmountRefunded() : $transData->getAmount();
		$currency = $transData->getCurrency();
		$statusReason = $transData->getStatusReason();
		$transaction = $transData->getTransaction();
		
		// should send a email
		$notifyCustomer = false;
		// status the order will be changed
		$newOrderStatus = $paymentObj->getConfigData('order_status');
		
		// rechnung bestätigt
		if( $transData->isSofortrechnung() 
		          && (( $statusReason == 'not_credited_yet' && $transData->getInvoiceStatus() == 'pending') 
		                || $forceInvoice)) { 
			$notifyCustomer = false;
			$newOrderStatus = Mage::getStoreConfig('payment/sofort/sofortrechnung_order_status');
			if($transData->getInvoiceType() == 'OR'){
			    // is process as invoice
    			$invoice = array(
    							'number' => $transData->getInvoiceNumber(),
    							'bank_holder' => $transData->getInvoiceBankHolder(),
    							'bank_account_number' => $transData->getInvoiceBankAccountNumber(),
    							'bank_code' => $transData->getInvoiceBankCode(),
    							'bank_name' => $transData->getInvoiceBankName(),
    							'reason' => $transData->getInvoiceReason(1). ' '.$transData->getInvoiceReason(2),
    							'date' => $transData->getInvoiceDate(),
    							'due_date' => $transData->getInvoiceDueDate(),
    							'debitor_text' => $transData->getInvoiceDebitorText()
    			);
			} else {
			    // is process as lastschrift, fill with empty data
			    $invoice = array(
    							'number' => $transData->getInvoiceNumber(),
    							'bank_holder' => '',
    							'bank_account_number' => '',
    							'bank_code' => '',
    							'bank_name' => '',
    							'reason' => '',
    							'date' => '',
    							'due_date' => '',
    							'debitor_text' => Mage::helper('pnsofortueberweisung')->__('your invoice amount will automatically be deducted from your your bank account.')
    			);
			}
			$order->getPayment()->setAdditionalInformation("sofortrechnung_invoice_url", $transData->getInvoiceUrl());
			$order->getPayment()->setAdditionalInformation('sofort_invoice', serialize($invoice));
		// rechnung 
		} elseif($transData->isSofortrechnung()) {
			return;		
		// lastschrift
		} elseif($transData->isLastschrift()) { 
		    $newOrderStatus = Mage::getStoreConfig('payment/sofort/lastschriftsofort_order_status');
			$notifyCustomer = true;
		// sofortueberweisung
		} else {
		    $newOrderStatus = Mage::getStoreConfig('payment/sofort/pnsofort_order_status');
			$notifyCustomer = true;
		}
		
			
		$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_SUCCESS);
		$payment->setStatusDescription(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transaction));
		$order->setPayment($payment);

		
		if($order->getPayment()->canCapture() && $order->canInvoice()) {
			$payment->setTransactionId($transaction)
					->setIsTransactionClosed(0)
					->registerCaptureNotification($amount);
		} elseif(method_exists($payment, 'addTransaction')) {  //transaction overview in magento > 1.5
			$payment->setTransactionId($transaction)
					->setIsTransactionClosed(0)
					->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE); 
		}

		$order->setPayment($payment);
		
		// if status is already closed or completed dont change it
		if( $order->getStatus() == 'closed' || $order->getStatus() == 'complete' ){
		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transaction))->setIsCustomerNotified($notifyCustomer);
		    $order->setIsVisibleOnFront(true);
		} else if($newOrderStatus == 'unchanged') {
		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transaction))->setIsCustomerNotified($notifyCustomer);
		    $order->setIsVisibleOnFront(true);
		} else{
		    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);		
		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transaction), $newOrderStatus)->setIsCustomerNotified($notifyCustomer);
		    $order->setIsVisibleOnFront(true);
		}
		
        // FIX BUG to send multible mails to customer					
		if($notifyCustomer && !$order->getEmailSent()) {
			$order->setEmailSent(true);
		    $order->save();
			$order->sendNewOrderEmail();
		}
		
		$order->save();
	}
	
	/**
	 * execute if transaction was received
	 * only: sofortueberweisung, lastschrift, sofortlastschrift
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionReceived($transData, $order) {
		$payment = $order->getPayment();
		if( $transData->isLastschrift() ) {
			//don't do anything
		} else { 
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Money received.'));
		}
		
		$order->save();
	}
	
	/**
	 * execute if transaction was refunded
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionRefunded($transData, $order) {
		$payment = $order->getPayment();

		if(!$payment->getTransaction($transData->getTransaction().'-refund')) {
			$payment->setParentTransactionId($transData->getTransaction())
				->setShouldCloseParentTransaction(true)
				->setIsTransactionClosed(0)
				->registerRefundNotification($transData->getAmountRefunded());

			// lastschrift by sofort
			if($transData->isLastschrift()){
			    $refundStatus = Mage::getStoreConfig('payment/sofort/lastschriftsofort_order_status_refund');			   
			// Rechnung
			}else{	
			    $refundStatus = Mage::getStoreConfig('payment/sofort/sofortrechnung_order_status_refund');				
			}
			
		    if($refundStatus == 'unchanged'){
		        $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been canceled.'))->setIsVisibleOnFront(true);
		    } else {
		        $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been canceled.'), $refundStatus)->setIsVisibleOnFront(true);
		    }
			$order->save();
		}
	}
	
	/**
	 * generates hash of status
	 * 
	 * @param SofortLib_TransactionData $transData
	 */
	private function _getLastChanged($transData) {
		return sha1($transData->getStatus() . $transData->getStatusReason());
	}
	
	/**
	* Get singleton of Checkout Session Model
	*
	* @return Mage_Checkout_Model_Session
	*/
	public function getCheckout()
	{
		return Mage::getSingleton('checkout/session');
	}

	public function indexAction(){
	    echo "Hello World";
	}
	
}