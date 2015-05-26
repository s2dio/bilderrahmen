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
 * @category Paymentnetwork
 * @package Paymentnetwork_Sofortueberweisung
 * @copyright Copyright (c) 2011 Payment Network AG, 2013 initOS GmbH & Co. KG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version $Id: PnsofortueberweisungController.php 3866 2012-04-18 11:52:31Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_PnsofortueberweisungController extends Mage_Core_Controller_Front_Action
{
	
	protected $_redirectBlockType = 'pnsofortueberweisung/pnsofortueberweisung';
	protected $mailAlreadySent = false;
		
	/**
	 * when customer select payment method
	 */
	public function redirectAction() {
		$session = $this->getCheckout();
		$session->setSofortQuoteId($session->getQuoteId());
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		
		$paymentObj = $order->getPayment()->getMethodInstance();
		$payment = $order->getPayment();
		
		switch($payment->getMethod()) {
			case 'sofortrechnung': 
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Rechnung by SOFORT payment loaded.'));
			break;
			case 'sofortlastschrift': 
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('SOFORT Lastschrift payment loaded.'));
			break;
			case 'lastschriftsofort': 
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Lastschrift by SOFORT payment loaded.'));
			break;
			case 'sofortvorkasse': 
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Vorkasse by SOFORT payment loaded.'));
			break;
			case 'pnsofort': 
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Sofortueberweisung payment loaded.'));
			break;
		}
		
		$order->save();

		$url = $paymentObj->getUrl();
		$this->getResponse()->setRedirect($url);
		
		$session->unsQuoteId();
	}
	
	/**
	 * when customer returns after transaction
	 * used by sofortueberweisung
	 */
	public function returnAction() {
		if (!$this->getRequest()->isGet()) {
			$this->norouteAction();
			return;
		}
		
		$response = $this->getRequest()->getParams();
		
		if(empty($response['orderId'])) {
			$this->_redirect('pnsofortueberweisung/pnsofortueberweisung/error');
		} else {	
		    
		    $order = Mage::getModel('sales/order')->loadByIncrementId($response['orderId']);		    
		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer returned successfully'));    	
    		$order->save();
		    
    		$session = $this->getCheckout();	
    		$session->setQuoteId($session->getSofortQuoteId(true));
    		$session->getQuote()->setIsActive(false)->save();
			$this->_redirect('checkout/onepage/success', array('_secure'=>true));
		}
		
	}
	
	/**
	 * handles notofication from sofort after payment
	 * used by sofortueberweisung
	 */
	public function returnhttpAction() {
		if (!$this->getRequest()->isPost()) {
			$this->norouteAction();
			return;
		}
		
		$response = $this->getRequest()->getParams();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		$payment = $order->getPayment();
		
		if($payment->getAdditionalInformation('sofort_lastchanged') >= 1) {
			Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__);
			return;
		}
		$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 1);
		$order->save();
		
		$status = $this->_checkReturnedData();
		
		if ($status) {
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($response['orderId']);
			if($order->getId() && !$order->getEmailSent()) {  
			    // FIX: mark EMail send before try, becouse on slow servers
			    $order->setEmailSent(true);
			    $order->save();
				$order->sendNewOrderEmail();
			}
		} else {
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($response['orderId']);
			// be sure that order can cancel
		    $order->setState('sofort');	
			$order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer cancled payment or payment error'));				
			$order->save();
		}
	}
	
	
	public function errorAction() {
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
		Mage::getSingleton('checkout/session')->setData('sofort_aborted', 1);
		Mage::getSingleton('checkout/session')->addNotice(Mage::helper('pnsofortueberweisung')->__('Cancelation of payment'));
		$this->_redirect('checkout/cart');
		return;	
	}
	
	
	/**
	 * Checking Get variables.
	 * 
	 */
	protected function _checkReturnedData() {
		$status = false;
		
		if (!$this->getRequest()->isPost()) {
			$this->norouteAction();
			return;
		}
		
		//Get response
		$response = $this->getRequest()->getParams();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		$data = $this->getNotification($paymentObj->getConfigData('notification_pswd'));
		
		if(!is_array($data)) {
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__($data)); 
			$order->save();
			$this->norouteAction();
			return;
		}
		
		$orderId = $data['user_variable_0'];
							
		if($data['transaction'] &&  $response['orderId'] == $orderId){
			$payment = $order->getPayment();
			$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_SUCCESS);
			$payment->setStatusDescription(Mage::helper('pnsofortueberweisung')->__('Payment was successful.',$data['transaction']));
			$payment->setAdditionalInformation('sofort_transaction', $data['transaction']);
			$payment->setTransactionId($data['transaction'])
					->setIsTransactionClosed(1);
			if(method_exists($payment, 'addTransaction')) {
				$payment->addTransaction('authorization'); //transaction overview in magento > 1.5
			}
			
			$order->setPayment($payment);
			
			if($paymentObj->getConfigData('createinvoice') == 1){
				if ($this->saveInvoice($order)) {
					// do nothing
				}
			}
			
			$paymentCode = $order->getPayment()->getMethod();
		    
		    if ($paymentCode == 'pnsofortueberweisung'){
		        $waitingStatus = Mage::getStoreConfig('payment/pnsofortueberweisung/order_status',$order->getStore());
		    } elseif ($paymentCode == 'sofortrechnung'){
		        $waitingStatus = Mage::getStoreConfig('payment/sofort/sofortrechnung_order_status_waiting',$order->getStore());
		    } elseif ($paymentCode == 'pnsofort'){
		        $waitingStatus = Mage::getStoreConfig('payment/sofort/pnsofort_order_status',$order->getStore());
		    } elseif ($paymentCode == 'lastschriftsofort'){
		        $waitingStatus = Mage::getStoreConfig('payment/sofort/lastschriftsofort_order_status_refund',$order->getStore());
		    } elseif ($paymentCode == 'sofort_ideal'){
		        $waitingStatus = Mage::getStoreConfig('payment/sofort_ideal/order_status_waiting',$order->getStore());
		    }
		    
    		$order->setState('sofort');
    		if($waitingStatus == 'unchanged'){
    		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.',$data['transaction']));
    		} else {
    		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.',$data['transaction']), $waitingStatus);   
    		} 
			
			$status = true;
		} else {
			
			$payment = $order->getPayment();
			$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_DECLINED);
			$order->setPayment($payment);
			// be sure that order can cancel
		    $order->setState('sofort');	
			$order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was not successfull'));
			$status = false;
		}
		
		$order->save();
		return $status;
	}
	
	
	/**
	 *  Save invoice for order
	 *
	 *  @param	Mage_Sales_Model_Order $order
	 *  @return	  boolean Can save invoice or not
	 */
	protected function saveInvoice (Mage_Sales_Model_Order $order) {
		if ($order->canInvoice()) {
			$invoice = $order->prepareInvoice();
			
			$invoice->register();
			Mage::getModel('core/resource_transaction')
				->addObject($invoice)
				->addObject($invoice->getOrder())
				->save();
			$invoice->sendEmail(true, '');
			return true;
		}
		
		return false;
	}
	
	
	/**
	* Get singleton of Checkout Session Model
	*
	* @return Mage_Checkout_Model_Session
	*/
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}
	
	
	/**
	 * 	checks server response and gets parameters  
	 *  @return $data array|string response parameters or ERROR_WRONG_HASH|ERROR_NO_ORDER_DETAILS if error
	 * 
	 */
	public function getNotification($pwd) {
		$pnSu =  Mage::helper('pnsofortueberweisung');
		$pnSu->classPnSofortueberweisung($pwd);
		return $pnSu->getNotification();
	}
	
}