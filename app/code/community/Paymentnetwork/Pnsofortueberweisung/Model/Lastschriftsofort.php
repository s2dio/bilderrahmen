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
 * @copyright  Copyright (c) 2011 Payment Network AG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Lastschriftsofort.php 3844 2012-04-18 07:37:02Z dehn $
 */

require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Lastschriftsofort extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract
{
	
	/**
	* Availability options
	*/
	protected $_code = 'lastschriftsofort';   
	protected $_formBlockType = 'pnsofortueberweisung/form_lastschriftsofort';	
	
	/**
	 * set the state and status of order
	 * will be executed instead of authorize()
	 * 
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 * @return Paymentnetwork_Pnsofortueberweisung_Model_Lastschriftsofort
	 */
	public function initialize($paymentAction, $stateObject)
	{
	    $holdingStatus = Mage::getStoreConfig('payment/sofort/lastschriftsofort_order_status_holding');
	    if($holdingStatus == 'unchanged'){
	        return $this;
	    }
		$stateObject->setState(Mage_Sales_Model_Order::STATE_HOLDED);
		$stateObject->setStatus($holdingStatus);
		$stateObject->setIsNotified(false);
		return $this;
	}
	
	public function getUrl(){
		$order 		= $this->getOrder();
		$session 	= Mage::getSingleton('core/session');
		$amount		= number_format($order->getGrandTotal(),2,'.','');
		$security 	= $this->getSecurityKey();
		$reason1 	= Mage::helper('pnsofortueberweisung')->__('Order No.: ').$order->getRealOrderId();
		$reason1 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason1);
		$reason2 	= Mage::getStoreConfig('general/store_information/name');
		$reason2 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason2);
		$success_url = Mage::getUrl('pnsofortueberweisung/sofort/return',array('orderId'=>$order->getRealOrderId(), '_secure'=>true));
		$cancel_url = Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		$notification_url = Mage::getUrl('pnsofortueberweisung/sofort/notification',array('orderId'=>$order->getRealOrderId(), 'secret' =>$security));
					
		$sObj = new SofortLib_Multipay(Mage::getStoreConfig('payment/sofort/configkey'));
		$sObj->setVersion(self::MODULE_VERSION);
		$sObj->setAmount($amount, $order->getOrderCurrencyCode());
		$sObj->setReason($reason1, $reason2);
		$sObj->setSuccessUrl($success_url);
		$sObj->setAbortUrl($cancel_url);
		$sObj->setNotificationUrl($notification_url);
		$sObj->addUserVariable($order->getRealOrderId());
		$sObj->setEmailCustomer($order->getCustomerEmail());
		$sObj->setSenderAccount($session->getLsBankCode(), $session->getLsAccountNumber(), $session->getLsHolder());

		// set address
		$sObj->setLastschriftAddress($this->_getFirstname($order->getBillingAddress()),
		                             $this->_getLastname($order->getBillingAddress()),
		                             $this->_getStreet($order->getBillingAddress()),
		                             $this->_getNumber($order->getBillingAddress()),
		                             $this->_getPostcode($order->getBillingAddress()),
		                             $this->_getCity($order->getBillingAddress()),
		                             '');
		//$sObj->setPhoneNumberCustomer($order->getCustomerTelephone());

		$sObj->setLastschrift();
		
		$sObj->sendRequest();
		if(!$sObj->isError()) {
			$url = $sObj->getPaymentUrl();
			$tid = $sObj->getTransactionId();
			$order->getPayment()->setTransactionId($tid)->setIsTransactionClosed(0);			
			$order->getPayment()->setAdditionalInformation('sofort_transaction', $tid);
			$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 0);
			$order->getPayment()->setAdditionalInformation('sofort_secret', $security)->save();			
			return $url;
		} else {	
			$errors = $sObj->getErrors();
			foreach($errors as $error)
				Mage::getSingleton('checkout/session')->addError(Mage::helper('pnsofortueberweisung')->localizeXmlError($error));
						
			return $cancel_url;
		}
	}
	
	/**
	 * Retrieve information from payment configuration
	 *
	 * @param   string $field
	 * @return  mixed
	 */
	public function getConfigData($field, $storeId = null)
	{

		return parent::getConfigData($field, $storeId);
	}
	
	
	public function assignData($data)
	{
	   	if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		
		Mage::getSingleton('core/session')
				->setLsAccountNumber($data->getLsAccountNumber())
				->setLsBankCode($data->getLsBankCode())
				->setLsHolder($data->getLsHolder());   
				  
		return $this;
	}	
	
	public function validate()
	{
		parent::validate();
		$session = Mage::getSingleton('core/session');
		if (!$session->getLsHolder()) {
			Mage::throwException(Mage::helper('pnsofortueberweisung')->__('Please fill out the account holder'));
		}
		if (!$session->getLsAccountNumber()) {
			Mage::throwException(Mage::helper('pnsofortueberweisung')->__('Please fill out the account number'));
		}
		if (!$session->getLsBankCode()) {
			Mage::throwException(Mage::helper('pnsofortueberweisung')->__('Please fill out the bank code'));
		}
		
		return $this;
	}	
	
}