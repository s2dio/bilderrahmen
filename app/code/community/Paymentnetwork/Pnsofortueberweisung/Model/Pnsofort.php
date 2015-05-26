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
 * @version	$Id: Pnsofort.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Pnsofort extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract
{
	
	/**
	* Availability options
	*/
	protected $_code = 'pnsofort';   
	
	/**
	 * set the state and status of order
	 * will be executed instead of authorize()
	 * 
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 * @return Paymentnetwork_Pnsofortueberweisung_Model_Pnsofort
	 */
	public function initialize($paymentAction, $stateObject)
	{
	    $holdingStatus = Mage::getStoreConfig('payment/sofort/pnsofort_order_status_holding', $this->getStore());
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
		$amount		= number_format($order->getGrandTotal(),2,'.','');
		$billing	= $order->getBillingAddress();
		$security 	= $this->getSecurityKey();
		
		$reason1_template = Mage::getStoreConfig('payment/sofort/pnsofort_reason_1', $this->getStore());
		$reason2_template = Mage::getStoreConfig('payment/sofort/pnsofort_reason_2', $this->getStore());
		
		$tmp_orderid = $order->getRealOrderId();
		$address = $order->getBillingAddress();
		$tmp_name = $this->_getFirstname($address) . " " .  $this->_getLastname($address);
		$tmp_date = Mage::getModel('core/date')->date('d.m.Y');
		$tmp_storename = Mage::getStoreConfig('general/store_information/name', $this->getStore());
		
		$reason1 	= preg_replace('#\{\{ordernr\}\}#', $tmp_orderid, $reason1_template);
        $reason2 	= preg_replace('#\{\{ordernr\}\}#', $tmp_orderid, $reason2_template);
                
        $reason1 	= preg_replace('#Order No.:#', Mage::helper('pnsofortueberweisung')->__('Order No.: '), $reason1);
        $reason2 	= preg_replace('#Order No.:#', Mage::helper('pnsofortueberweisung')->__('Order No.: '), $reason2);
        
        $reason1 	= preg_replace('#\{\{name\}\}#', $tmp_name, $reason1);
        $reason2 	= preg_replace('#\{\{name\}\}#', $tmp_name, $reason2);
        
        $reason1 	= preg_replace('#\{\{date\}\}#', $tmp_date, $reason1);
        $reason2 	= preg_replace('#\{\{date\}\}#', $tmp_date, $reason2);
        
        $reason1 	= preg_replace('#\{\{shopname\}\}#', $tmp_storename, $reason1);
        $reason2 	= preg_replace('#\{\{shopname\}\}#', $tmp_storename, $reason2);
        
        $reason1 	= preg_replace('#\{\{transaction\}\}#', '-TRANSACTION-', $reason1);
        $reason2 	= preg_replace('#\{\{transaction\}\}#', '-TRANSACTION-', $reason2);
		
		$reason1 	= preg_replace('#[^a-zA-Z0-9+-\.,\s]#', '', $reason1);		
		$reason2 	= preg_replace('#[^a-zA-Z0-9+-\.,\s]#', '', $reason2);
		
		$success_url = Mage::getUrl('pnsofortueberweisung/sofort/return',array('orderId'=>$order->getRealOrderId(), '_secure'=>true));
		$cancel_url = Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		$notification_url = Mage::getUrl('pnsofortueberweisung/sofort/notification',array('orderId'=>$order->getRealOrderId(), 'secret' =>$security));
					
		$sObj = new SofortLib_Multipay(Mage::getStoreConfig('payment/sofort/configkey',$this->getStore()));
		$sObj->setVersion(self::MODULE_VERSION);
		$sObj->setAmount($amount, $order->getOrderCurrencyCode());
		$sObj->setReason($reason1, $reason2);
		$sObj->setSuccessUrl($success_url);
		$sObj->setAbortUrl($cancel_url);
		$sObj->setNotificationUrl($notification_url);
		$sObj->addUserVariable($order->getRealOrderId());
		//$sObj->setEmailCustomer($order->customer['email_address']);
		//$sObj->setPhoneNumberCustomer($order->customer['telephone']);

		$sObj->setSofortueberweisung();
		$sObj->setSofortueberweisungCustomerprotection($this->getConfigData("pnsofort_customerprotection") == 1);
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
	
}