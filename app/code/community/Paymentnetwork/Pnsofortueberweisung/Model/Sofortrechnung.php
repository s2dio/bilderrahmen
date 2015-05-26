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
 * @version	$Id: Sofortrechnung.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Sofortrechnung extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract
{
	
	/**
	* Availability options
	*/
	protected $_code = 'sofortrechnung'; 
	protected $_formBlockType = 'pnsofortueberweisung/form_sofortrechnung';
	protected $_infoBlockType = 'pnsofortueberweisung/info_sofortrechnung';
	protected $_canCapture = true;
	protected $_canCancelInvoice = true;
	protected $_canCapturePartial = true;
	protected $_canVoid = false;
	protected $_canRefund = true;
	protected $_isGateway = true;
	
	/**
	 * set the state and status of order
	 * will be executed instead of authorize()
	 * 
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 * @return Paymentnetwork_Pnsofortueberweisung_Model_Sofortrechnung
	 */
	public function initialize($paymentAction, $stateObject)
	{
	    $holdingStatus = Mage::getStoreConfig('payment/sofort/sofortrechnung_order_status_holding');
	    if($holdingStatus == 'unchanged'){
	        return $this;
	    }
		$stateObject->setState(Mage_Sales_Model_Order::STATE_HOLDED);
		$stateObject->setStatus($holdingStatus);
		$stateObject->setIsNotified(false);
		return $this;
	}	
	
	/**
	 * returns url to call to register payment with sofortrechnung
	 * 
	 * @return string
	 */
	public function getUrl(){
	    // get current order
		$order 		= $this->getOrder();
		// generate new key
		$security 	= $this->getSecurityKey();
		// generate payment request object	
		try{
		    $sObj = $this->createPaymentFromOrder($order, $security);
		}catch (Exception $e){
		    Mage::getSingleton('checkout/session')->addError($e->getMessage());
		    return Mage::getUrl('checkout/cart', array('_secure'=>true));
		}
        // register payment to service
		$sObj->sendRequest();
		// if everythink fine
		if(!$sObj->isError()) {
			$tid = $sObj->getTransactionId();
			$order->getPayment()->setTransactionId($tid)->setIsTransactionClosed(0);			
			$order->getPayment()->setAdditionalInformation('sofort_transaction', $tid);
			$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 0);
			$order->getPayment()->setAdditionalInformation('sofort_secret', $security)->save();
			
			Mage::getSingleton('checkout/session')->setData('sofort_aborted', 1);
			
			return $sObj->getPaymentUrl();
		// something wrong
		} else {	
			$errors = $sObj->getErrors();
			foreach($errors as $error) {
				Mage::getSingleton('checkout/session')->addError(Mage::helper('pnsofortueberweisung')->localizeXmlError($error));
			}

			return Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		}
	}
	
	/**
	 * create the connection class and add order info
	 * 
	 * @param Mage_Sales_Model_Order_Item $order
	 * @param string $security key for information
	 */
	public function createPaymentFromOrder($order, $security = null){
	    
	    // check if security key is given
		if($security === null){
		    // get existing security key
		    $security = $order->getPayment()->getAdditionalInformation('sofort_secret');
		    // generate new one
		    if(empty($security)){
		        $security 	= $this->getSecurityKey();
		    }
		}
		
		// create new object
		$sObj = new SofortLib_Multipay(Mage::getStoreConfig('payment/sofort/configkey'));
		$sObj->setVersion(self::MODULE_VERSION);
		
		// set type
		$sObj->setSofortrechnung();
		
		// basic information
		$sObj->addUserVariable($order->getRealOrderId());
		$sObj->setEmailCustomer($order->getCustomerEmail());
		$sObj->setSofortrechnungCustomerId($order->getCustomerId());
		$sObj->setSofortrechnungOrderId($order->getRealOrderId());
		
		// add order number and shop name
	    $reason1 = Mage::helper('pnsofortueberweisung')->__('Order No.: ').$order->getRealOrderId();
		$reason1 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason1);
		$reason2 = Mage::getStoreConfig('general/store_information/name');
		$reason2 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason2);
		$sObj->setReason($reason1, $reason2);
		
		// set amount
		$amount		= number_format($order->getGrandTotal(),2,'.','');
		$sObj->setAmount($amount, $order->getOrderCurrencyCode());
				
		// setup urls
		$success_url = Mage::getUrl('pnsofortueberweisung/sofort/return',array('orderId'=>$order->getRealOrderId(), '_secure'=>true));
		$cancel_url = Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		$notification_url = Mage::getUrl('pnsofortueberweisung/sofort/notification',array('orderId'=>$order->getRealOrderId(), 'secret' =>$security));
		$sObj->setSuccessUrl($success_url);
		$sObj->setAbortUrl($cancel_url);
		$sObj->setNotificationUrl($notification_url);

		// items, shipping, discount
	    $this->_appendItems($order, $sObj);
		
		// invoice address
		$address = $order->getBillingAddress();
		$sObj->setSofortrechnungInvoiceAddress( $this->_getFirstname($address),
		                                        $this->_getLastname($address),
		                                        $this->_getStreet($address), 
		                                        $this->_getNumber($address), 
		                                        $this->_getPostcode($address),
		                                        $this->_getCity($address), 
		                                        $this->_getSalutation($address), 
		                                        $this->_getCountry($address),
		                                        $this->_getNameAdditive($address),
		                                        $this->_getStreetAdditive($address),
		                                        $this->_getCompany($address));

		// shipping address
		$address = $order->getShippingAddress();
		$sObj->setSofortrechnungShippingAddress($this->_getFirstname($address),
		                                        $this->_getLastname($address),
		                                        $this->_getStreet($address), 
		                                        $this->_getNumber($address), 
		                                        $this->_getPostcode($address),
		                                        $this->_getCity($address), 
		                                        $this->_getSalutation($address), 
		                                        $this->_getCountry($address),
		                                        $this->_getNameAdditive($address),
		                                        $this->_getStreetAdditive($address),
		                                        $this->_getCompany($address));				
	    
	    return $sObj;
	}

	/**
	 * append items, shipping and discount to the payment object
	 * 
	 * @param Mage_Sales_Model_Order_Item $order
	 * @param SofortLib_Multipay $sofortPayment
	 */
	private function _appendItems($order, $sofortPayment) {
		//items
		$discountTax = 19;
		foreach ($order->getAllVisibleItems() as $item) {
		    
		    if(($item->product_type == 'downloadable' || $item->product_type == 'virtual')
		        && $item->getRowTotal() > 0){
		        throw new Exception(Mage::helper('pnsofortueberweisung')->__('Kauf auf Rechnung not allowed for downloadable or virtual products'));
		    }
		    
            $name = $item->getName();
            $uid = $item->getSku()."-".$item->getItemId();
            // FIXME getDescription is not default method for Mage_Sales_Model_Order_Item ?
            $desc = $item->getDescription();
            // configurable product
            if ($item->product_type == 'configurable'){
                $productOptions= unserialize($item->product_options);
                // check attributes
                if(!empty($productOptions['attributes_info'])){
                    $configAttr = array();
                    foreach ($productOptions['attributes_info'] as $pOp){
                        $configAttr[] =  $pOp['value'];
                    }
                    if(!empty($configAttr)){
                        $desc = implode(", ",$configAttr)."\n".$desc;
                    }
                }
            }
            // handle bundles
            else if ($item->product_type == 'bundle'){
                $productOptions = unserialize($item->product_options);
                // check bundle options
                if(!empty($productOptions['bundle_options'])){
                    $bundleTitle = array();
                    foreach ($productOptions['bundle_options'] as $pOp){
                        if(!empty($pOp['value'])){
                            foreach ($pOp['value'] as $bValue){
                                $bundleTitle[] = $bValue['title'];
                            }
                        }
                        
                    }
                    if(!empty($bundleTitle)){
                        $desc = implode(", ",$bundleTitle)."\n".$desc;
                    }
                }
            }
            
			// add item
            $sofortPayment->addSofortrechnungItem(md5($uid), 
		                                          $item->getSku(), 
		                                          $name, 
		                                          $this->_getPriceInclTax($item), 
		                                          0, 
		                                          $desc, 
		                                          ( $item->getQtyOrdered() - $item->getQtyCanceled() - $item->getQtyRefunded() ), 
		                                          $item->getTaxPercent()
		                                          );
		                                          
			if($item->getTaxPercent() > 0) {
			    // tax of discount is min of cart-items
				$discountTax = min($item->getTaxPercent(), $discountTax);
			}

		}
		
		
		//shipping
		if($order->getShippingAmount() != 0) {
			$shippingTax = round($order->getShippingTaxAmount()/$order->getShippingAmount()*100);
		}
		else {
			$shippingTax = 0;
		}
		// check if amount is removed
		if( ($order->getShippingAmount() - $order->getShippingRefunded()) > 0){
		    $sofortPayment->addSofortrechnungItem(1, 1, $order->getShippingDescription(), $this->_getShippingInclTax($order), 1, '', 1, $shippingTax);
		}
		
		//discount
		if($order->getDiscountAmount() != 0) {
			$sofortPayment->addSofortrechnungItem(2, 2, Mage::helper('sales')->__('Discount'), $order->getDiscountAmount(), 2, '', 1, $discountTax);
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
	

	
	/**
	 * check billing country is allowed for the payment method
	 *
	 * @return bool
	 */
	public function canUseForCountry($country)
	{
		//we only support DE right now
		return strtolower($country) == 'de' && parent::canUseForCountry($country);
	}	
	
	/**
	 * we deactivate this payment method if it was aborted before
	 * 
	 * @return bool
	 */
	public function canUseCheckout() {
		$aborted = Mage::getSingleton('checkout/session')->getData('sofort_aborted') == 1;
		
		return !$aborted && parent::canUseCheckout();
	}
	
	 /**
	 * Capture payment
	 *
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @return Mage_Paypal_Model_Payflowpro
	 */
	public function capture(Varien_Object $payment, $amount)
	{
		$tid = $payment->getAdditionalInformation('sofort_transaction');
		$payment->setTransactionId($tid);
		return $this;
	}	
	
	/**
	 * Refund money
	 *
	 * @param   Varien_Object $invoicePayment
	 * @return  Mage_GoogleCheckout_Model_Payment
	 */
	public function refund(Varien_Object $payment, $amount) {
		
		$transactionId = $payment->getAdditionalInformation('sofort_transaction');
		$order = $payment->getOrder();
		if(!empty($transactionId)) {
		
		    $PnagInvoice = new PnagInvoice(Mage::getStoreConfig('payment/sofort/configkey'), $transactionId);
			$PnagInvoice->cancelInvoice($transactionId);
			
			
			if($PnagInvoice->isError()) {
				Mage::throwException($PnagInvoice->getError());
			} else {
				$payment->setTransactionId($transactionId.'-refund')
					->setShouldCloseParentTransaction(true)
					->setIsTransactionClosed(0);		
			
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been canceled.'))->setIsVisibleOnFront(true);
				$order->save();
					
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully canceled invoice. Credit memo created: %s', $transactionId));
				return $this;
			}		
		}
		
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not cancel invoice.'));	  	
		

		return $this;
	}
	
	/**
	 * update quantity to refund
	 * 
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param array() $dataQty
	 * @param int $keepQty
	 * @return array() $dataQty
	 */
	private function _refundItem($item, $dataQty, $keepQty = 0){
	    
	    $refundQty = $item->getQtyToRefund() - $keepQty;
	    if($refundQty > 0){
	        // set quantity to refund
	        $dataQty[$item->getId()] = $refundQty;	        
	        
	        // search child to by refund as well
	        $childItems = $item->getChildrenItems();
    	    foreach($childItems as $child){
    	        $dataQty = $this->_refundItem($child, $dataQty, $keepQty);
    	    }
	    }
	    
	    return $dataQty;
	}
	
	/**
	 * create creditMemo for order of given changed
	 * 
	 * due to some Magento Core Bugs this function deals with some hacks
	 * 
	 * - HACK #1 - order CLOSED / COMPLETED auto on save, if you have any value>0 in total_refunded => move to sale_order_save_after event
	 * - HACK #2 - creditmemo refund all items if it have no items
	 * - HACK #3 - without item, shippment refund broken
	 * - HACK #4 - partial shippment check broken => fix alread with HACK #5
	 * - HACK #5 - partial shippment tax broken
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @param array $checkItems
	 * @return boolean
	 */
	private function _createCreditMemo($transData, $order, $checkItems){
	    /* @var $service Mage_Sales_Model_Service_Order  */
	    $service = Mage::getModel('sales/service_order', $order);
	    $invoice = $order->getInvoiceCollection()->getFirstItem();
	    $invoice->setOrder($order);
	    
	    $itemDecreaceAmount = 0;
	    
	    $data = array( 'qtys' => array() );
	    
	    foreach($order->getAllVisibleItems() as $item) {
	        /* @var $item Mage_Sales_Model_Order_Item */
	        $uid = md5($item->getSku()."-".$item->getItemId());
	        // if not exist it should removed
	        if(empty($checkItems[$uid])){
	            // item was cancel
	            $data['qtys'] = $this->_refundItem($item, $data['qtys']);
	            // add removed items
	            $itemDecreaceAmount += $this->_getPriceInclTax($item) * $item->getQtyToRefund();
	            
	            unset($checkItems[$uid]);
	            continue;
	        }
	        // quantity or price change, new row values will be calculated
	        if($checkItems[$uid]['quantity'] != ($item->getQtyInvoiced() - $item->getQtyRefunded() ) ){
	            $data['qtys'] = $this->_refundItem($item, $data['qtys'], $checkItems[$uid]['quantity']);
	            // add removed items
	            $itemDecreaceAmount += $this->_getPriceInclTax($item) * ($item->getQtyToRefund() - $checkItems[$uid]['quantity'] );
	        }
	    }

		$forceShippment = 0;
		$partialShippment = 0;
	    
	    if(!empty($checkItems[1])){
	        // HACK #5 - partial update
	        $partialShippment = $checkItems[1]['quantity'] * $checkItems[1]['unit_price'];  
	        // add canceled shippment          
	        $itemDecreaceAmount += ($order->getBaseShippingInclTax() - $order->getBaseShippingAmountRefunded() - $order->getBaseShippingTaxRefunded()) - $partialShippment;
        } else {
            // HACK #3 refund full shipping leaving $data['shipping_amount'] empty
            if($order->getBaseShippingInclTax() - $order->getBaseShippingRefunded() - $order->getBaseShippingTaxRefunded() > 0 ){
                $forceShippment = $order->getBaseShippingInclTax() - $order->getBaseShippingRefunded() - $order->getBaseShippingTaxRefunded();
                // add canceled shippment
                $itemDecreaceAmount += $forceShippment;
            }
        }
         
        // correct creditmemo          <--             Not Refundended Value                  -->         NEW REFUND           items + shipping
        // => discount + change prices 
        $data['adjustment_positive'] = ($order->getGrandTotal() - $order->getBaseTotalRefunded()) - $transData->getAmount() - $itemDecreaceAmount;
	    
		//FIXME: check multible invoice ???
	    if ($invoice) {
            $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
        } else {
            $creditmemo = $service->prepareCreditmemo($data);
        }
        
        // no items removed, just remove from creditmemo
        if(empty($data['qtys'])){
            // HACK #2 we dont have any item for creditmemo
	        foreach($creditmemo->getAllItems() as $item) {
                $item->setQty(0);
            }
            // reset before recolact
	        $creditmemo->setGrandTotal(0);
	        $creditmemo->setBaseGrandTotal(0);
	        
	        $creditmemo->collectTotals();
	        
	        // HACK #3 make update
            if($forceShippment > 0 || $partialShippment > 0){	   
                // HACK #5 inside from HACK #3
                if($partialShippment > 0){
	                $shippingWithTax = $checkItems[1]['quantity'] * $checkItems[1]['unit_price'];
	                $shippingTax = $shippingWithTax - ($shippingWithTax * (100 / ($checkItems[1]['tax']+100)) );
	                $shippingAmount = $shippingWithTax - $shippingTax;	
                } else {
                    $shippingWithTax = 0;
                    $shippingTax = 0;
                    $shippingAmount = 0;
                }      
	        
                $creditmemo->setBaseShippingAmount($order->getBaseShippingAmount() - $order->getBaseShippingRefunded() - $shippingAmount);
	            $creditmemo->setShippingAmount($order->getShippingAmount() - $order->getShippingRefunded() - $shippingAmount );
	           
	            $creditmemo->setShippingTaxAmount($order->getShippingTaxAmount() - $order->getShippingTaxRefunded() - $shippingTax);
	            $creditmemo->setBaseShippingTaxAmount($order->getBaseShippingTaxAmount() - $order->getBaseShippingTaxRefunded() - $shippingTax);
	            
	            $creditmemo->setShippingInclTax($order->getShippingInclTax() - $order->getShippingRefunded() - $order->getShippingTaxRefunded() - $shippingWithTax);
	            $creditmemo->setBaseShippingInclTax($order->getBaseShippingInclTax() - $order->getBaseShippingAmountRefunded() - $order->getBaseShippingTaxRefunded() - $shippingWithTax);

                $creditmemo->setBaseTaxAmount($order->getShippingTaxAmount() - $order->getShippingTaxRefunded() - $shippingTax);
	            $creditmemo->setTaxAmount($order->getShippingTaxAmount() - $order->getShippingTaxRefunded() - $shippingTax);
                $creditmemo->setGrandTotal($data['adjustment_positive'] + $order->getBaseShippingInclTax() - $order->getBaseShippingRefunded() - $order->getShippingTaxRefunded() - $shippingWithTax);
	            $creditmemo->setBaseGrandTotal($data['adjustment_positive'] + $order->getBaseShippingInclTax() - $order->getBaseShippingRefunded() - $order->getBaseShippingTaxRefunded() - $shippingWithTax);
            }
	    }
        // HACK #5
        else if($partialShippment > 0){
	        $shippingWithTax = $checkItems[1]['quantity'] * $checkItems[1]['unit_price'];
	        $shippingTax = $shippingWithTax - ($shippingWithTax * (100 / ($checkItems[1]['tax']+100)) );
	        $shippingAmount = $shippingWithTax - $shippingTax;	      
	        
	        $creditmemo->setBaseShippingAmount($creditmemo->getBaseShippingAmount() - $shippingAmount);
	        $creditmemo->setShippingAmount($creditmemo->getShippingAmount() - $shippingAmount);
	       
	        $creditmemo->setShippingTaxAmount($creditmemo->getShippingTaxAmount() - $shippingTax);
	        $creditmemo->setBaseShippingTaxAmount($creditmemo->getBaseShippingTaxAmount() - $shippingTax);
	        
	        $creditmemo->setShippingInclTax($creditmemo->getShippingInclTax() - $shippingWithTax);
	        $creditmemo->setBaseShippingInclTax($creditmemo->getShippingInclTax() - $shippingWithTax);
	        
	        $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() - $shippingTax);
	        $creditmemo->setTaxAmount($creditmemo->getTaxAmount() - $shippingTax);
	        $creditmemo->setGrandTotal( $creditmemo->getGrandTotal() - $shippingWithTax);
	        $creditmemo->setBaseGrandTotal( $creditmemo->getBaseGrandTotal() - $shippingWithTax );
        }
	    
        // dont notify
        $creditmemo->setOfflineRequested(true);
        
	    // add credotmemo to order
	    $creditmemo->register();

        // this is a HACK #1 - just needed to prevent magento to set order to complete state
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_EDIT, false);
	    $totalRefund = $order->getTotalRefunded();
        $order->setTotalRefunded(0);
        
        // save all data
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($order);
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();
        
	    return true;
	}
	
	/**
	 * edit order if something changed
	 * 
	 * @param SofortLib_TransactionData $transData
	 * @param Mage_Sales_Model_Order $order
	 * @return boolean
	 */
	public function updateOrderFromTransactionData($transData, $order) {
	    
	    // total amount without refunded
	    $amount		= number_format($order->getGrandTotal() - $order->getBaseTotalRefunded(),2,'.','');
	    
	    // if amount still the same, there was nothing edit
	    if($amount == $transData->getAmount()){
	        return false;
	    }
	    
	    // transaction was cancel, nothing change, order will cancel full
	    if($transData->isLoss()) {
	        return false;
	    }
	    
		// store items get from remote
	    $checkItems = array();
	    foreach($transData->getItems() as $item){
	        $checkItems[$item['item_id']] = $item;
	    }
	    
	    // order already invoice => create creditmemo
	    if($order->hasInvoices()){
	        return $this->_createCreditMemo($transData, $order, $checkItems);
	    }
	    
	    // update total
	    $order->setGrandTotal($transData->getAmount());
	    $order->setBaseGrandTotal($transData->getAmount());
	    $subTotal = 0;
	    $taxAmount = array();
	    
        // if discount value change the discount store on each row is broken
        // so we just remove it
	    $removeDiscount = false;
	    // edit discount amount
	    if(empty($checkItems[2])){
	        $order->setDiscountAmount(0);
	        $removeDiscount = true;
	    }else{
	        $order->setDiscountAmount($checkItems[2]['quantity'] * $checkItems[2]['unit_price']);
	        $removeDiscount = true;
	    }
	    
	    // check all items in the current order
	    foreach($order->getAllVisibleItems() as $item) {
	        $uid = md5($item->getSku()."-".$item->getItemId());
	        
	        // if not exist it should removed
	        if(empty($checkItems[$uid])){
	            // item was cancel
	            $this->_cancelItem($item);
	            
	            unset($checkItems[$uid]);
	            continue;
	        }
	        // quantity or price change, new row values will be calculated
	        if($checkItems[$uid]['quantity'] != $item->getQtyOrdered() || $item->getPrice() != $checkItems[$uid]['unit_price']){
	            $item->setQtyCanceled($item->getQtyOrdered() - $checkItems[$uid]['quantity']);	            

	            $singleTax = $checkItems[$uid]['unit_price'] - ($checkItems[$uid]['unit_price'] * (100 / ($checkItems[$uid]['tax']+100)) );
	            
	            $item->setPrice($checkItems[$uid]['unit_price'] - $singleTax);
	            $item->setBasePrice($checkItems[$uid]['unit_price'] - $singleTax);
	            $item->setPriceInclTax($checkItems[$uid]['unit_price']);
	            $item->setBasePriceInclTax($checkItems[$uid]['unit_price']);
	            
	            $rowTotalInclTag = $checkItems[$uid]['quantity'] * $checkItems[$uid]['unit_price'];
	            $rowTax = $rowTotalInclTag - ($rowTotalInclTag * (100 / ($checkItems[$uid]['tax']+100)) );
	            $rowTotal = $rowTotalInclTag - $rowTax;
	            
                $item->setRowTotalInclTax( $rowTotalInclTag );
                $item->setBaseRowTotalInclTax( $rowTotalInclTag );
                
                $item->setRowTotal( $rowTotal );
	            $item->setBaseRowTotal( $rowTotal );
	            
	            $item->setTaxAmount( $rowTax );
	            $item->setBaseTaxAmount( $rowTax );	            

	        }
	        // add to subtotal
	        $subTotal += $checkItems[$uid]['quantity'] * $checkItems[$uid]['unit_price'];
	        // appent to tax group
	        if(empty($taxAmount[$checkItems[$uid]['tax']])) {
	            $taxAmount[$checkItems[$uid]['tax']] = 0;
	        } 
	        $taxAmount[$checkItems[$uid]['tax']] += $item->getRowTotalInclTax();
	        
	        // remove discount from order row
	        if($removeDiscount){
	            $item->setDiscountPercent(0);
	            $item->setDiscountAmount(0);
	            $item->setBaseDiscountAmount(0);
	        }
	        
	        unset($checkItems[$uid]);
	    }
	    
	    // edit shipment amount if it was removed
	    if(empty($checkItems[1]) && $order->getShippingAmount()){
	        $order->setShippingAmount(0);
	        $order->setBaseShippingAmount(0);
	        $order->setShippingTaxAmount(0);
	        $order->setBaseShippingTaxAmount(0);
	        $order->setShippingInclTax(0);
	        $order->setBaseShippingInclTax(0);
	    }else{
	        $shippingWithTax = $checkItems[1]['quantity'] * $checkItems[1]['unit_price'];
	        $shippingTax = $shippingWithTax - ($shippingWithTax * (100 / ($checkItems[1]['tax']+100)) );
	        $shippingAmount = $shippingWithTax - $shippingTax;
	        
	        $order->setShippingAmount($shippingAmount);
	        $order->setBaseShippingAmount($shippingAmount);
	        
	        $order->setShippingTaxAmount($shippingTax);
	        $order->setBaseShippingTaxAmount($shippingTax);
	        
	        $order->setShippingInclTax($shippingWithTax);
	        $order->setBaseShippingInclTax($shippingWithTax);
	    }
	    
	    // fix tax from discount and shipping
	    foreach($checkItems as $item) {
	        if(empty($taxAmount[$item['tax']])) {
	            $taxAmount[$item['tax']] = 0;
	        } 
	       
	        $taxAmount[$item['tax']] += ($item['unit_price'] * $item['quantity']);
	    }
	    
	    // update subtotal
	    $order->setBaseSubtotalInclTax($subTotal);
	    $order->setSubtotalInclTax($subTotal);
	    
	    // sum for all tax amount
	    $totalTaxAmount = 0;
	    
	    // update all tax rate items
	    $rates = Mage::getModel('tax/sales_order_tax')->getCollection()->loadByOrder($order);
	    foreach($rates as $rate){
	        // format rate
	        $tRate = sprintf("%01.2f", $rate->getPercent());
	        if(!empty($taxAmount[$tRate])){
	            // calc new tax value
	            $tAmount = $taxAmount[$tRate] - ($taxAmount[$tRate] * (100 / ($tRate+100)) );
	            $totalTaxAmount += $tAmount;
	            $rate->setAmount($tAmount);
	            $rate->setBaseAmount($tAmount);
	            $rate->setBaseRealAmount($tAmount);
	            $rate->save();
	        }
	    }
	    
	    // update total tax amount
	    $order->setTaxAmount($totalTaxAmount);
	    $order->setBaseTaxAmount($totalTaxAmount);
	    
	    // update subtotal without tax
	    $order->setBaseSubtotal( $subTotal - $totalTaxAmount + $order->getShippingTaxAmount() );
	    $order->setSubtotal($subTotal - $totalTaxAmount + $order->getShippingTaxAmount() );
	    
	    $order->save();
	    
	    return true;
	}
	
	/**
	 * deletes item with all children
	 * 
	 * @param Mage_Sales_Model_Order_Item $item
	 */
	private function _cancelItem($item){
	    $childItems = $item->getChildrenItems();
	    foreach($childItems as $child){
	        $this->_cancelItem($child);
	    }
	    $item->delete();
	}
	
    /**
     * update invoice items to sofortueberweisung
     * 
     * @param Varien_Object $payment object of the order
     * @param array $items of the the invoice
     * @param string $comment to add
     * @param string $invoiceNumber
     * @param string $customerNumber
     * @param string $orderNumber
     * @return Paymentnetwork_Pnsofortueberweisung_Model_Sofortrechnung
     * @throws Exception
     */
	public function updateInvoice(Varien_Object $payment, $items, $comment, $invoiceNumber = '', $customerNumber = '', $orderNumber = '') {
	    
	    // load current transaction id
	    $transactionId = $payment->getAdditionalInformation('sofort_transaction');
		$order = $payment->getOrder();
		
		if(!empty($transactionId)) {
		    
		    // create articles
		    $pnagArticles = array();
		    foreach($items as $item){
		       array_push( $pnagArticles,
		                    array('itemId'        => $item['item_id'],
		                          'productNumber' => $item['product_number'],
		                          'productType'   => $item['product_type'],
		                          'title'         => $item['title'],
		                          'description'   => $item['description'],
		                          'quantity'      => $item['quantity'],
		                          'unitPrice'     => $item['unit_price'],
		                          'tax'           => $item['tax']
		                        )
		                );
			}
		    
			// create connection class
			$PnagInvoice = new PnagInvoice(Mage::getStoreConfig('payment/sofort/configkey'), $transactionId);
			$PnagInvoice->setTransactionId($transactionId);
			$PnagInvoice->updateInvoice($transactionId, $pnagArticles, $comment, $invoiceNumber, $customerNumber, $orderNumber);			
			
			// add error
			if($PnagInvoice->isError()) {
				Mage::throwException($PnagInvoice->getError());
			} else {
			    // update history
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been edit.')."\n\n\"".$comment.'"');
				$order->save();
					
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully edit invoice.'));
				return $this;
			}		
		}
		// no transaction id exist
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not edit invoice.'));	  	
		
        return $this;
	}

	
	/*
	 * workaround for magento < 1.4.1
	 */
	private function _getPriceInclTax($item)
	{
		if ($item->getPriceInclTax()) {
			return $item->getPriceInclTax();
		}
		$qty = ($item->getQty() ? $item->getQty() : ($item->getQtyOrdered() ? $item->getQtyOrdered() : 1));
		$price = (floatval($qty)) ? ($item->getRowTotal() + $item->getTaxAmount())/$qty : 0;
		return Mage::app()->getStore()->roundPrice($price);
	}

	/*
	 * workaround for magento < 1.4.1
	 */
	private function _getShippingInclTax($order) 
	{
		if($order->getShippingInclTax()) {
			return $order->getShippingInclTax() - $order->getShippingRefunded() - $order->getShippingTaxRefunded();
		}
		
		$price = $order->getShippingTaxAmount()+$order->getShippingAmount();
		return Mage::app()->getStore()->roundPrice($price);
	}
}