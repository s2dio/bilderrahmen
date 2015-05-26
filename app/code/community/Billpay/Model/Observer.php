<?php

/**
<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http:opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @package    Billpay
 * @author 	   Jan Wehrs <jan.wehrs@billpay.de>
 * @copyright  Copyright (c) 2009 Billpay GmbH
 * @license    http:opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Billpay_Model_Observer {

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
	private function getLog() {
		return Mage::helper('billpay/log');
	}

  	/**
  	 *  Get checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

	/**
     * @return Billpay_Helper_Calculation
     */
    public function getCalculation() {
        return Mage::helper('billpay/calculation');
    }

	/**
	 * @return Billpay_Model_Session
	 */
	private function getSession() {
		return Mage::getSingleton('billpay/session');
	}

	/**
	 * Catches billpay event 'billpay_before_save_payment'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function sendPreauthorizeRequest(Varien_Event_Observer $observer) {
		$e = $observer->getEvent();

		$data 						= $e->getData('data');
		$useHTMLFormat 				= $e->getData('useHTMLFormat');
		$expectedDaysTillShipping 	= $e->getData('expectedDaysTillShipping');

		$paymentMethod 	= $data['method'];

    	if ($this->getHelper()->isBillpayPayment($paymentMethod) &&
    		!$this->getHelper()->isOneStepCheckout($this->getCheckout()->getQuote()->getStoreId())) {
    		$this->_sendPreauthorizeRequest($data, $paymentMethod, $useHTMLFormat, $expectedDaysTillShipping);
    	}
	}

	private function _sendPreauthorizeRequest($data, $paymentMethod, $useHTMLFormat, $expectedDaysTillShipping, $orderId = '') {
		try {
			$salutation 	= $this->getSession()->getSelectedSalutation();
			$dateOfBirth 	= $this->getSession()->getSelectedDateOfBirth();
			$phone			= $this->getSession()->getSelectedPhone();
			$termsAccepted 	= $this->getSession()->getTermsAccepted();

			$bankAccount = null;
			if ($this->getHelper()->isBillpayElvPayment($paymentMethod) || $this->getHelper()->isBillpayRatPayment($paymentMethod)) {
				$bankAccount = $this->getSession()->getBankAccount();
			}

			return $this->getHelper()->sendPreauthorizationRequest($paymentMethod, $salutation, $dateOfBirth, $phone, $termsAccepted, $expectedDaysTillShipping, $useHTMLFormat, $bankAccount, $orderId);
    	}
    	catch (Exception $e) {
    	    if (!$e instanceof Mage_Payment_Exception) {
				throw new Mage_Payment_Exception($e->getMessage());
    		}
    		else {
    			throw $e;
    		}
    	}
	}


	/**
	 * Catches magento default event 'sales_order_creditmemo_save_before'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function sendPartialcancelRequest(Varien_Event_Observer $observer) {
		$creditMemo = $observer->getEvent()->getData('creditmemo');
		$data = Mage::app()->getRequest()->getPost('creditmemo');

		if (isset($data)) {
			// $this->getHelper()->sendPartialcancelRequest($creditMemo, $data, true);
		    $this->getHelper()->sendEditCartContentRequest($creditMemo, $data, true);

		}
	}

	/**
	 * Catches billpay event 'billpay_before_cancel_order'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function sendCancelRequest(Varien_Event_Observer $observer) {
		$order = $observer->getEvent()->getData('order');

		if ($order->canCancel()) {
			$paymentMethod = $order->getPayment()->getMethod();

			    $childId = $order->getData('relation_child_real_id');
			if ($this->getHelper()->isBillpayPayment($paymentMethod)  && empty($childId)) {

				$useHTMLFormat = $observer->getEvent()->getData('useHTMLFormat');

				$this->getHelper()->sendCancelRequest($order, $useHTMLFormat);

				$comment = $this->getHelper()->__('billpay_cancelled_successully');
				$order->addStatusToHistory($order->getStatus(), $comment, false);
			}
		}
	}

	/**
	 * Catches default magento event 'checkout_type_onepage_save_order'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function sendCaptureRequest(Varien_Event_Observer $observer) {
		$order = $observer->getEvent()->getData('order');
		$paymentMethod = $order->getPayment()->getMethod();

		$result = null;
		if ($this->getHelper()->isOneStepCheckout($order->getStoreId())) {
			$data = Mage::app()->getRequest()->getPost('payment', array());

	    	if ($this->getHelper()->isBillpayPayment($paymentMethod)) {
				try {
					// this will start an auto-capture request
					$result = $this->_sendPreauthorizeRequest($data, $paymentMethod, true, 0, $order->getIncrementId());
				}
				catch (Exception $e) {
					// transform payment exception to core exception
					throw new Mage_Core_Exception($e->getMessage());
				}
	    	}
		}
		else {
			if ($this->getHelper()->isBillpayPayment($paymentMethod)) {
				$result = $this->getHelper()->sendCaptureRequest($paymentMethod, false, $order->getIncrementId());
			}
		}

		if (!is_null($result)) {
			$quote = $this->getCheckout()->getQuote();

			// Save charged fee to order
			$order->setBillpayChargedFee($quote->getBillpayChargedFee());
			$order->setBillpayChargedFeeNet($quote->getBillpayChargedFeeNet());
			$order->setBaseBillpayChargedFee($quote->getBaseBillpayChargedFee());
			$order->setBaseBillpayChargedFeeNet($quote->getBaseBillpayChargedFeeNet());
			$order->setBillpayChargedFeeRefunded(0);
			$order->setBillpayChargedFeeRefundedNet(0);
			$order->setBaseBillpayChargedFeeRefunded(0);
			$order->setBaseBillpayChargedFeeRefundedNet(0);
			$order->setBillpayTransactionId($this->getSession()->getTransactionId());

			if ($this->getHelper()->isBillpayInvoicePayment($paymentMethod)) {
				$this->setBankAccountData($order, $result);
			}
		}
	}

	/**
	 *
	 * @param Varien_Event_Observer $observer
	 * @throws Exception
	 */
    public function sendEditcartRequest(Varien_Event_Observer $observer) {
        $order = $observer->getEvent()->getData('order');
        $Id = $order->getOriginalIncrementId();

        /** @var Billpay_Model_Sales_Order $oldOrder */
        $oldOrder = Mage::getModel('sales/order')->loadByIncrementId($Id);

        if(is_object($oldOrder->getPayment()) && $oldOrder->getPayment()->getMethod()) {
            
            $OldpaymentMethod = $oldOrder->getPayment()->getMethod();
            $paymentMethod    = $order->getPayment()->getMethod();

            if($this->getHelper()->isBillpayPayment($OldpaymentMethod) || $this->getHelper()->isBillpayPayment($paymentMethod)) {
                if($OldpaymentMethod != $paymentMethod) {
                    throw new Exception($this->getHelper()->__('billpay_payment_method_changed')   . ' ( ' .
                                         $oldOrder->getPayment()->getMethodInstance()->getTitle()     . ' - ' .
                                         $order->getPayment()->getMethodInstance()->getTitle()  . ' ) ');
                }
            }

			if($this->getHelper()->isBillpayPayment($OldpaymentMethod)) {
    			$order->setBillpayChargedFee($oldOrder->getOrigData('billpay_charged_fee'));
    			$order->setBillpayChargedFeeNet($oldOrder->getOrigData('billpay_charged_fee_net'));
    			$order->setBaseBillpayChargedFee($oldOrder->getOrigData('base_billpay_charged_fee'));
    			$order->setBaseBillpayChargedFeeNet($oldOrder->getOrigData('base_billpay_charged_fee_net'));
    			$order->setBillpayChargedFeeRefunded(0);
    			$order->setBillpayChargedFeeRefundedNet(0);
    			$order->setBaseBillpayChargedFeeRefunded(0);
    			$order->setBaseBillpayChargedFeeRefundedNet(0);
    			$order->setBillpayTransactionId($oldOrder->getOrigData('billpay_transaction_id'));
    			$this->setBankAccountDataForEditcart($order, $oldOrder);
            }

			if($this->getHelper()->isBillpayPayment($OldpaymentMethod)) {
			    $this->getHelper()->sendEditCartContentRequest2($order, $Id);
            }
        }
    }

	/**
	 * Catches magento event 'sales_order_invoice_save_before'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function sendConfirmInvoiceRequest(Varien_Event_Observer $observer) {
		$invoice = $observer->getInvoice();
		$paymentMethod = $invoice->getOrder()->getPayment()->getMethod();

		if ($this->getHelper()->isBillpayPayment($paymentMethod)) {

			// Is there a cleaner way to access the post data within a model?
			$data = Mage::app()->getRequest()->getPost('invoice');

			$delayInDays = 0;
			if (is_array($data) && array_key_exists('billpay_delay', $data)) {
				$delayInDays = $data['billpay_delay'];
			}

			$result = $this->getHelper()->sendConfirmInvoiceRequest($invoice, $paymentMethod, $delayInDays, true);

			$order = $invoice->getOrder();

			// Store bank account in database
			$this->setBankAccountData($order, $result);

			if ($this->getHelper()->isBillpayRatPayment($paymentMethod)) {

				$info = $order->getPayment()->getMethodInstance()->getInfoInstance();

				// Store due dates in database for hire purchase
				if (is_array($result['dues']) && count($result['dues']) > 0) {
					$serializedDuesWithDates = $this->getCalculation()->getSerializedDues($result['dues']);
					$info->setBillpayRateDues($serializedDuesWithDates);
				}
				else {
					$this->getLog()->logError("No due dates found for hire purchase order after invoiceCreated request");
				}
			}

			// Send copy of invoice mail to billpay
			if ($this->getHelper()->getConfigData('settings/send_invoice_mail_copy', $invoice->getStoreId())) {

				try {
					$this->sendInvoiceCopy($invoice);
					$this->getLog()->logDebug('Successfully sent copy of invoice mail to billpay');
				}
				catch (Exception $e) {
					$this->getLog()->logError('Error sending copy of invoice mail to billpay');
					$this->getLog()->logException($e);
				}
			}

			// Add comment to status history
			$comment = $this->getHelper()->__('billpay_activated_successully');
			if ($delayInDays > 0) {
				$comment .= ' (+'.$delayInDays.' '.$this->getHelper()->__('billpay_activated_days').')';
			}
			$order->addStatusToHistory($order->getStatus(), $comment, false);
			$invoice->addComment($comment, false);
		}
	}

	/**
	 * Catches magento event 'checkout_type_onepage_save_order_after'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function cleanup(Varien_Event_Observer $observer) {
		$this->getSession()->clearSessionParameters();

		$this->getLog()->logDebug('Session cleared');
	}

	/**
	 * Catches magento event 'sales_convert_quote_payment_to_order_payment'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function convertQuoteToOrder(Varien_Event_Observer $observer) {
		$event = $observer->getEvent();
		$orderPayment = $event->getOrderPayment();
		$quotePayment = $event->getQuotePayment();
		$paymentMethod = $quotePayment->getQuote()->getPayment()->getMethod();

		if ($this->getHelper()->isBillpayRatPayment($paymentMethod)) {
			$orderPayment->setBillpayRateSurcharge($quotePayment->getBillpayRateSurcharge());
			$orderPayment->setBillpayRateCount($quotePayment->getBillpayRateCount());
			$orderPayment->setBillpayRateTotalAmount($quotePayment->getBillpayRateTotalAmount());
			$orderPayment->setBillpayRateDues($quotePayment->getBillpayRateDues());
			$orderPayment->setBillpayRateInterestRate($quotePayment->getBillpayRateInterestRate());
			$orderPayment->setBillpayRateAnualRate($quotePayment->getBillpayRateAnualRate());
			$orderPayment->setBillpayRateBaseAmount($quotePayment->getBillpayRateBaseAmount());
		    $orderPayment->setBillpayRateResidualAmount($quotePayment->getBillpayRateResidualAmount());
			$orderPayment->setBillpayRateFee($quotePayment->getBillpayRateFee());
			$orderPayment->setBillpayRateFeeNet($quotePayment->getBillpayRateFeeNet());
			$orderPayment->setBillpayRateFeeTax($quotePayment->getBillpayRateFeeTax());
		}
	}

	/**
	 * Catches billpay event 'billpay_before_send_new_order_mail'
	 * @param Varien_Event_Observer $observer
	 */
	public function attachHirePurchasePdf(Varien_Event_Observer $observer) {
		$event = $observer->getEvent();
		$order = $event->getOrder();
		$mail = $event->getMail();

		if ($order && $mail) {
			$paymentMethod = $order->getPayment()->getMethod();
			if ($this->getHelper()->isBillpayRatPayment($paymentMethod)/* && !$order->getEmailSent()*/) {
				foreach (array(1, 2) as $type) {
					$pathToAttachement = Mage::helper('billpay/attachment')
						->getFullAttachmentPath($type, $order->getIncrementId(), $order->getStoreId());

					if (file_exists($pathToAttachement)) {
						$file = file_get_contents($pathToAttachement);
	        			$attachment = $mail->createAttachment($file);
	        			$attachment->filename = ($type == 1) ? 'Ratenkauf.pdf' : 'Anlage_1.pdf';

	        			$this->getLog()->logDebug('Last pdf found: ' . $pathToAttachement);
					}
					else {
						$this->getLog()->logError('Last pdf NOT found: ' . $pathToAttachement);
					}
				}
			}
		}
	}

	/**
	 * Set billpay bank account data to order
	 *
	 * @param Mage_Sales_Model_Order $order
	 */
	private function setBankAccountData($order, $data) {
		$this->getLog()->logDebug('Going to attach bank data to order');

		$orderUpdated = false;
		if (!empty($data['account_holder'])) {
			$order->setBillpayAccountHolder($data['account_holder']);
			$orderUpdated = true;
		}
		if (!empty($data['account_number'])) {
    		$order->setBillpayAccountNumber($data['account_number']);
    		$orderUpdated = true;
		}
		if (!empty($data['bank_code'])) {
    		$order->setBillpayBankCode($data['bank_code']);
    		$orderUpdated = true;
		}
		if (!empty($data['bank_name'])) {
    		$order->setBillpayBankName($data['bank_name']);
    		$orderUpdated = true;
		}
		if (!empty($data['account_holder'])) {
    		$order->setBillpayAccountHolder($data['account_holder']);
    		$orderUpdated = true;
		}
		if (!empty($data['invoice_reference'])) {
    		$order->setBillpayInvoiceReference($data['invoice_reference']);
    		$orderUpdated = true;
		}
    	if (!empty($data['invoice_duedate'])) {
    		$order->setBillpayInvoiceDuedate($data['invoice_duedate']);
    		$orderUpdated = true;
    	}

    	if ($orderUpdated) {
	    	$this->getLog()->logDebug('Bank data attached to order');
    	}
	}

	private function setBankAccountDataForEditcart($order, $oldOrder) {
	    $this->getLog()->logDebug('Going to reAttach bank data to order');
	    $order->setBillpayAccountHolder($oldOrder->getOrigData('billpay_account_holder'));
	    $order->setBillpayAccountNumber($oldOrder->getOrigData('billpay_account_number'));
	    $order->setBillpayBankCode($oldOrder->getOrigData('billpay_bank_code'));
	    $order->setBillpayBankName($oldOrder->getOrigData('billpay_bank_name'));
	    $order->setBillpayAccountHolder($oldOrder->getOrigData('billpay_account_holder'));
	    $order->setBillpayInvoiceReference($oldOrder->getOrigData('billpay_invoice_reference'));
	    $order->setBillpayInvoiceDuedate($oldOrder->getOrigData('billpay_invoice_duedate'));
	    $this->getLog()->logDebug('Bank data attached to order');

	}

	/**
	 * This is a copy of the mail function in class Mage_Sales_Model_Order_Invoice.
	 * In this case the only recipient is billpay and a comment was added.
	 *
	 * @param Mage_Sales_Model_Order_Invoice $invoice
	 */
	protected function sendInvoiceCopy($invoice) {
        $currentDesign = Mage::getDesign()->setAllGetOld(array(
            'package' => Mage::getStoreConfig('design/package/name', $invoice->getStoreId()),
            'store'   => $invoice->getStoreId()
        ));

        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        $order  = $invoice->getOrder();

        $paymentBlock   = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true);

        $mailTemplate = Mage::getModel('core/email_template');

        if ($order->getCustomerIsGuest()) {
            $template = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_GUEST_TEMPLATE, $order->getStoreId());
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $template = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_TEMPLATE, $order->getStoreId());
            $customerName = $order->getCustomerName();
        }

        $mode = $this->getHelper()->getConfigData('account/transaction_mode', $order->getStoreId());

        $recipientMail = ($mode == Billpay_Helper_Api::TRANSACTION_MODE_TEST) ?
        	'rechnung-test@billpay.de' :
        	'rechnung@billpay.de';

        $recipientName = 'Billpay';

        $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$order->getStoreId()))
            ->sendTransactional(
                 $template,
                 Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_IDENTITY, $order->getStoreId()),
                 $recipientMail,
                 $recipientName,
                 array(
                     'order'       => $order,
                     'invoice'     => $invoice,
                     'comment'     => 'This mail was automatically sent by billpay magento plugin',
                     'billing'     => $order->getBillingAddress(),
                     'payment_html'=> $paymentBlock->toHtml(),
                 )
             );

        $translate->setTranslateInline(true);
	}
}
