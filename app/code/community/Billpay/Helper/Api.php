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
class Billpay_Helper_Api extends Mage_Core_Helper_Abstract {
	public static $BILPPAY_PAYMENT_METHOD 		= 'billpay_rec';
	public static $BILPPAY_PAYMENT_ELV_METHOD 	= 'billpay_elv';
	public static $BILLPAY_PAYMENT_RAT_METHOD 	= 'billpay_rat';

	const TRANSACTION_MODE_TEST 		= 'test';
	const TRANSACTION_MODE_LIVE 		= 'live';
	const TRANSACTION_MODE_SANDBOX 		= 'sandbox';

	const ALLOW_X_FORWARD_FOR			= false;

	private $_encryptedSessionId = null;

  	/**
  	 * Retrieve information from configuration
  	 *
     * @param string $field
     * @return string
     */
    public function getConfigData($field, $storeId = null, $paymentMethod = null) {
    	if ($paymentMethod == null) {
			$path = 'billpaysettings/' . $field;
    	}
    	else {
    		$path = 'payment/' . $paymentMethod . '/' . $field;
    	}

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * @param boolean $quoteId
     */
    public function isOneStepCheckout($storeId) {
    	$checkoutType = $this->getConfigData('settings/checkout_type', $storeId);
		return in_array($checkoutType, array('onestepcheckout', 'lightcheckout'));
    }

  	/**
  	 *  Get checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

 	public function getFraudIdentifier() {
    	if (is_null($this->_encryptedSessionId)) {
    		$this->_encryptedSessionId = md5($this->getSession()->getSessionId());
    	}
    	return $this->_encryptedSessionId;
    }

    /**
     * Get checkout helper
     *
     * @return Mage_Checkout_Helper_Data
     */
    public function getCheckoutHelper() {
    	return Mage::helper('checkout');
    }

    /**
     * Get billpay logger
     *
     * @return Billpay_Helper_Log
     */
    public function getLog() {
    	return Mage::helper('billpay/log');
    }

    /**
     * Get billpay calculation helper
     *
     * @return Billpay_Helper_Calculation
     */
    private function getCalculation() {
    	return Mage::helper('billpay/calculation');
    }

    /**
     *  Get quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

	/**
	 * @return Billpay_Model_Session
	 */
	public function getSession() {
		return Mage::getSingleton('billpay/session');
	}

	/**
	 * @return boolean
	 */
	public function isLoggedIn() {
		return Mage::getSingleton('customer/session')->isLoggedIn();
	}


	/**
	 * @return string
	 */
	public function getDateOfBirth() {
		if ($this->isLoggedIn()) {
			$dob = trim($this->getQuote()->getCustomer()->getDob());
			return $dob;
		}
		else {
			$dob = trim($this->getQuote()->getCustomerDob());
			return $dob;
		}
	}

	/**
	 * @return string
	 */
	public function getSalutation() {
		if ($this->isLoggedIn()) {
			if ($this->isOneStepCheckout($this->getQuote()->getStoreId())) {
				$oneStepCheckoutGenderSelect = $this->mapCustomerGender($this->getQuote()->getBillingAddress()->getGender());
				if ($oneStepCheckoutGenderSelect)
					return $oneStepCheckoutGenderSelect;
			}
			
			$prefix = trim($this->getQuote()->getCustomer()->getPrefix());
			if(!$prefix)
				$prefix = $this->mapCustomerGender($this->getQuote()->getCustomer()->getGender());
			
			return $prefix;
		}
		else {
			$prefix = trim($this->getQuote()->getBillingAddress()->getPrefix());
			if(!$prefix)
				$prefix = $this->mapCustomerGender($this->getQuote()->getCustomerGender());
			
			return $prefix;
		}
	}
	
	/**
	 * @return string
	 */
	public function getPhone() {
		$billingAddress = $this->getQuote()->getBillingAddress();
		if ($billingAddress && $billingAddress->getTelephone()) {
			$phone = trim($billingAddress->getTelephone());
		}
		else if ($this->getQuote()->getCustomer() && $this->getQuote()->getCustomer()->getDefaultBillingAddress()) {
			$phone = trim($this->getQuote()->getCustomer()->getDefaultBillingAddress()->getTelephone());
		}
		return $phone;
	}

    /**
     *  Calculate and round monetary value in cent
     *
     * @param unknown_type $floatingValue
     * @return unknown
     */
    public function currencyToSmallerUnit($floatingValue) {
    	$small = $floatingValue * 100;
    	return (int)round($small);
    }

    /**
     *  Map magento customer type according to billpay interface spec
     *
     * @param string $customerType
     * @return string
     */
    private function mapCustomerType($customerType) {
    	switch ($customerType) {
    		case 'guest':
    			return 'g';
    		case 'register':
    			return 'n';
    		default:
    			return 'e';
    	}
    }
    
    /**
     * Anrede
     *
     * @param int $gender
     * @return string
     */
    private function mapCustomerGender($gender) {
    	if ($gender == 1) 
    		return 'Herr';
    	elseif ($gender == 2) 
    		return 'Frau';
    	
    	return '';
    }

    /**
     *  Map magento payment method according to bilpay interfac spec
     *
     *   	0: Lastschrift
	 *		1: Kreditkarte
	 *		2: Vorkasse
	 *		3: Nachnahme
	 *		4: Paypal
	 *		5: Sofortueberweisung/Giropay
	 *		6: Rechnung
	 *		7: Billpay (Rechnung)
	 *	  100: Other
     *
     * @param $paymentType string
     * @return int
     */
    private function mapPaymentMethod($paymentMethod) {
    	switch($paymentMethod) {
    		// TODO: provide merchant specific mapping here
    		case self::$BILPPAY_PAYMENT_ELV_METHOD:
    		case self::$BILPPAY_PAYMENT_METHOD:
    			return 7;
    		default:
    			return 100;
    	}
    }

    /**
     * Map magento order state according to billpay interface spec
     *
     * From:
     *
     * const STATE_NEW             = 'new';
     * const STATE_PENDING_PAYMENT = 'pending_payment';
     * const STATE_PROCESSING      = 'processing';
     * const STATE_COMPLETE        = 'complete';
     * const STATE_CLOSED          = 'closed';
     * const STATE_CANCELED        = 'canceled';
     * const STATE_HOLDED          = 'holded';
     *
     * To:
     *
     * 0: Bezahlt
   	 * 1: Offen
   	 * 2: Mahnwesen
   	 * 3: Inkasso
     *
     * @param $orderState string
     * @return int
     */
    private function mapOrderState($orderState, $storeId = null) {
		// get user selected order statuses for 'paid' status
		// and explode the returned string
    	// i.e.: string 'pending,pending_paypal,holded,canceled,pending_amazon_asp' (length=57)
    	$paidstatus = $this->getConfigData('settings/paidstatus_by_method', $storeId);
		$paidstatus_array = explode(',', $paidstatus);

		// lookup array, if value in_array exists return 0 (paid)
		if (in_array($orderState, $paidstatus_array)) {
			return 0;
		}
		else {
			return 1;
		}
    }

 	/**
 	 * Format magento date according to billpay intreface spec
     *
     * @param unknown_type $magentoDate
     * @return string
     */
 	private function formatMagentoDate($magentoDate) {
    	$s1 = substr($magentoDate, 0, 4);
    	$s2 = substr($magentoDate, 5, 2);
    	$s3 = substr($magentoDate, 8, 2);
    	return $s1 . $s2 . $s3;
    }

    /**
     * Format datetimeaccording to billpay interface spec
     *
     * @param string $d
     * @return string
     */
    private function formatDatetime($d) {
    	return str_replace('-', '', $d);
    }

    /**
     * Get the language of the current store
     *
     * @return string
     */
    public function getCurrentLanguage() {
   		return substr(Mage::app()->getLocale()->getLocaleCode(),0,2);
    }

    /**
     * Check if request has billpay payment method
     *
     * @return boolean
     */
    public function isBillpayInvoicePayment($paymentMethod) {
    	return $paymentMethod === self::$BILPPAY_PAYMENT_METHOD;
    }

  	/** Check if request has billpay elv payment method
     *
     * @param string $paymentMethod
     * @return boolean
     */
    public function isBillpayElvPayment($paymentMethod) {
    	return $paymentMethod === self::$BILPPAY_PAYMENT_ELV_METHOD;
    }

  	/** Check if request has billpay rat payment method
     *
     * @param string $paymentMethod
     * @return boolean
     */
    public function isBillpayRatPayment($paymentMethod) {
    	return $paymentMethod === self::$BILLPAY_PAYMENT_RAT_METHOD;
    }

    /**
     * Check if request has billpay invoice payment method
     *
     * @param string $paymentMethod
     * @return boolean
     */
    public function isBillpayPayment($paymentMethod) {
    	return
    		$this->isBillpayInvoicePayment($paymentMethod) ||
    		$this->isBillpayElvPayment($paymentMethod) ||
    		$this->isBillpayRatPayment($paymentMethod);
    }

    /**
	 * Check whether the fee charge feature is enabled
	 *
	 * @return boolean
	 */
	public function isFeeChargeEnabled($paymentMethod, $b2b) {
		return $this->getCalculation()->isFeeChargeEnabled($paymentMethod, $this->getQuote()->getStoreId(), $b2b);
	}

    /**
     * Returns the current transaction mode (test|sandbox|live)
     * @return string
     */
    public function getTransactionMode($storeId) {
    	$mode = $this->getConfigData('account/transaction_mode', $storeId);

   		if ($mode == self::TRANSACTION_MODE_LIVE) {
   			return self::TRANSACTION_MODE_LIVE;
   		}
   		else {
   			$merchantId = trim($this->getConfigData('account/merchant_id', $storeId));
   			$portalId 	= trim($this->getConfigData('account/portal_id', $storeId));


   			if (!empty($merchantId) && !empty($portalId)) {
   				return self::TRANSACTION_MODE_TEST;
   			}
   			else {
   				return self::TRANSACTION_MODE_SANDBOX;
   			}
   		}
    }


    /**
     * Calculate cart total net
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return int
     */
	private function calculateCartTotal($address) {
		if ($address->getTaxAmount() > 0) {
			$value = $address->getGrandTotal()
				- $address->getTaxAmount();

			return $this->currencyToSmallerUnit($value);
		}
		else {
			$discount =
	    		$address->getDiscountAmount() < 0 ?
	    		-$address->getDiscountAmount() :
	    		$address->getDiscountAmount();

			$value =
				$address->getSubtotal() +
				$address->getShippingAmount() +
				$this->getQuote()->getBillpayChargedFeeNet() -
				$discount;

				// TODO: for rate payment add fee tax amount

	    	return $this->currencyToSmallerUnit($value);
		}
    }

    /**
     * Calculate cart total gross
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return int
     */
    private function calculateCartTotalGross($address) {
    	$value = $address->getGrandTotal();

    	return $this->currencyToSmallerUnit($value);
    }

    /**
     * Calculate the item gross price
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @param int $qty
     * @return int
     */
    public function calculateItemGross($item, $qty) {
    	$priceInclTax = Mage::helper('checkout')->getPriceInclTax($item);
    	return $this->currencyToSmallerUnit($priceInclTax);
        /*if ($item->getPriceInclTax()) {
            return $this->currencyToSmallerUnit($item->getPriceInclTax());
        }
    	$priceGross = Mage::app()->getStore()->roundPrice(($item->getRowTotal()+$item->getTaxAmount())/$qty);
    	return $this->currencyToSmallerUnit($priceGross);*/
    }


    /**
     * Get the full street name
     *
     * @return string
     */
    public function getFullStreetName($street) {
    	if (is_array($street)) {
    		if (count($street) >= 2) {
    			if (!empty($street[1])) {
    				return $street[0] . ' ' . $street[1];
    			}
    			else {
    				return $street[0];
    			}
    		}
    		else if (count($street) == 1) {
    			return $street[0];
    		}
    	}
    	else if (is_string($street)) {
    		return $street;
    	}
    	return '';
    }

	/**
	 * Returns the full name person who is making the order
	 *
	 * @return string
	 *
	 */
	public function getFullName() {
		return ucfirst($this->getQuote()->getBillingAddress()->getFirstname()) . ' ' . ucfirst($this->getQuote()->getBillingAddress()->getLastname());
	}

    /**
     * Get billpay payment type id
     *
     * @param string $paymentMethod
     * @return int
     */
 	public function getBillpayPaymentType($paymentMethod) {
    	switch($paymentMethod) {
    		case self::$BILPPAY_PAYMENT_METHOD:
    			return 1;
    		case self::$BILPPAY_PAYMENT_ELV_METHOD:
    			return 2;
    		case self::$BILLPAY_PAYMENT_RAT_METHOD;
    			return 3;
    		default:
    			throw new Exception('Unknown payment method (' . $paymentMethod . ')');
    	}
    }


 	/**
 	 * Add a value to the checkout session
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	//protected function storeInSession($key, $value) {
	//	$checkoutSession = Mage::getSingleton('checkout/session');
	//	$checkoutSession->setData($key, $value);
	//	return $checkoutSession;
	//}

	/**
	 * Get a value from the checkout session
	 *
	 * @param string $key
	 * @return Object
	 */
	//protected function getFromSession($key) {
	//	$checkoutSession = Mage::getSingleton('checkout/session');
	//	return $checkoutSession->getData($key);
	//}

	/**
	 * Get the client's IP address
	 * @return string
	 */
	private function getIpAddress() {
		// Probably proxy or CDN in use
		if (self::ALLOW_X_FORWARD_FOR && $this->getQuote()->getData('x_forwarded_for')) {
			$forward = $this->getQuote()->getData('x_forwarded_for');
			$parts = explode(',', $forward);

			if (count($parts) < 1) {
				return $this->getQuote()->getRemoteIp();
			}
			else {
				$ipAddress = trim($parts[0]);
				if (!empty($ipAddress)) {
					return $ipAddress;
				}
				else {
					return $this->getQuote()->getRemoteIp();
				}
			}
		}

		return $this->getQuote()->getRemoteIp();
	}


	/**
	 * Create a specific request object
	 *
	 * @param string $type
	 * @return ipl_xml_request
	 */
	private function createRequestObject($type, $paymentMethod, $storeId = null) {
		$paymentType = $this->getBillpayPaymentType($paymentMethod);

		$mode = $this->getConfigData('account/transaction_mode', $storeId);

		$apiUrlBaseKey =
			($mode == self::TRANSACTION_MODE_TEST) ?
			'account/api_url_base' :
			'account/api_url_base_live';

		$apiUrlBase 		= $this->getConfigData($apiUrlBaseKey, $storeId);
        $merchantId 		= $this->getConfigData('account/merchant_id', $storeId);
   		$portalId 			= $this->getConfigData('account/portal_id', $storeId);
    	$securityCode 		= $this->getConfigData('account/security_key', $storeId);

    	if (substr($apiUrlBase, strlen($apiUrlBase) - 1) != '/') {
    		$apiUrlBase .= '/';
    	}

    	$this->getLog()->logDebug('Creating request (' . $type . ') with url ' . $apiUrlBase);

    	require_once BP . '/lib/billpayApi/ipl_xml_api.php';

		switch($type) {
			case 'preauth':
				require_once BP . '/lib/billpayApi/ipl_preauthorize_request.php';
				$req = new ipl_preauthorize_request($apiUrlBase, $paymentType);
				if ($this->isOneStepCheckout($this->getQuote()->getStoreId())) {
					$req->set_capture_request_necessary(false);
				}
				break;
			case 'prescore':
				require_once BP . '/lib/billpayApi/ipl_prescore_request.php';
				$req = new ipl_prescore_request($apiUrlBase);
				if ($this->isOneStepCheckout($this->getQuote()->getStoreId())) {
					$req->set_capture_request_necessary(false);
				}
				break;
			case 'capture':
				require_once BP . '/lib/billpayApi/ipl_capture_request.php';
				$req = new ipl_capture_request($apiUrlBase);
				break;
			case 'cancel':
				require_once BP . '/lib/billpayApi/ipl_cancel_request.php';
				$req = new ipl_cancel_request($apiUrlBase);
				break;
			case 'partialcancel':
				require_once BP . '/lib/billpayApi/ipl_partialcancel_request.php';
				$req = new ipl_partialcancel_request($apiUrlBase);
				break;
			case 'validate':
				require_once BP . '/lib/billpayApi/ipl_validation_request.php';
				$req = new ipl_validation_request($apiUrlBase);
				break;
			case 'moduleConfig':
				require_once BP . '/lib/billpayApi/ipl_module_config_request.php';
				$req = new ipl_module_config_request($apiUrlBase);
				break;
			case 'invoiceCreated':
				require_once BP . '/lib/billpayApi/ipl_invoice_created_request.php';
				$req = new ipl_invoice_created_request($apiUrlBase);
				break;
			case 'rates':
				require_once BP . '/lib/billpayApi/ipl_calculate_rates_request.php';
				$req = new ipl_calculate_rates_request($apiUrlBase);
				break;
			case 'editCartContent':
				require_once BP . '/lib/billpayApi/ipl_edit_cart_content_request.php';
				$req = new ipl_edit_cart_content_request($apiUrlBase);
				break;
			default:
				throw new Exception('Request type \'' . $type . '\' does not exist');
		}

		$req->set_default_params($merchantId, $portalId, md5($securityCode));
		return $req;
	}

	/**
	 * Create an error message
	 *
	 * @param $req ipl_xml_request
	 * @return string
	 */
	private function createErrorMessage($req, $isHtml, $storeId, $paymentMethod) 	{
		$lineBreak = $isHtml ? '<br />' : '\n';
		$mode = $this->getConfigData('account/transaction_mode', $storeId);

		if ($mode == self::TRANSACTION_MODE_TEST) {
			$error = 'CUSTOMER: ' . $req->get_customer_error_message();
			$error .= $lineBreak . 'MERCHANT: ' . $req->get_merchant_error_message();
			$error .= $lineBreak . 'ERROR CODE: ' . $req->get_error_code();
		}
		else {
			$error = $req->get_customer_error_message();
		}

		return $error;
	}

	/**
	 * Get shop specific values via api request
	 *
	 * @return array
	 */
	public function getModuleConfig($paymentMethod) {
		$currency = $this->getQuote()->getQuoteCurrencyCode();
		$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code();
		$config = $this->getSession()->getModuleConfig($currency, $country);

		if (isset($config) && $config == false) {
			 $this->getLog()->logError('Fetching module config failed previously. Billpay payment not available.');
		}
		elseif (!isset($config)) {
			$storeId = null;
			if ($this->getQuote()) {
				$storeId = $this->getQuote()->getStoreId();
			}

			$req = $this->createRequestObject('moduleConfig', $paymentMethod, $storeId);
			$req->set_locale($country, $currency, $this->getCurrentLanguage());

		 	try {
		    	$req->send();

		    	$this->getLog()->logDebug('Module config request xml:');
				$this->getLog()->logDebug($req->get_request_xml());
				$this->getLog()->logDebug('Module config response xml:');
				$this->getLog()->logDebug($req->get_response_xml());
		    }
		    catch (Exception $e) {
		    	$this->getLog()->logError('Error sending module config request:');
				$this->getLog()->logException($e);
				$this->getSession()->setModuleConfig(array('is_active' => 0), $currency, $country);
	            throw new Exception();
		    }

			if ($req->has_error()) {
				$this->getLog()->logError('ModuleConfig request error code: ' . $req->get_error_code());
				$this->getSession()->setModuleConfig(array('is_active' => 0), $currency, $country);
				//throw new Mage_Core_Exception();
				return false;
			}

			$config = array();
			$config['is_active']													= $req->is_active();
			$config['is_allowed_'	. self::$BILPPAY_PAYMENT_METHOD] 				= $req->is_invoice_allowed();
			$config['is_allowed_'	. self::$BILPPAY_PAYMENT_METHOD . '_b2b'] 		= $req->is_invoicebusiness_allowed();
			$config['is_allowed_'	. self::$BILPPAY_PAYMENT_ELV_METHOD] 			= $req->is_direct_debit_allowed();
			$config['is_allowed_'	. self::$BILPPAY_PAYMENT_ELV_METHOD . '_b2b'] 	= false;
			$config['is_allowed_'	. self::$BILLPAY_PAYMENT_RAT_METHOD] 			= $req->is_hire_purchase_allowed();
			$config['is_allowed_'	. self::$BILLPAY_PAYMENT_RAT_METHOD . '_b2b'] 	= false;
			$config['min_'			. self::$BILPPAY_PAYMENT_METHOD] 				= $req->get_invoice_min_value();
			$config['min_'			. self::$BILPPAY_PAYMENT_METHOD . '_b2b']		= $req->get_invoicebusiness_min_value();
			$config['min_'			. self::$BILPPAY_PAYMENT_ELV_METHOD] 			= $req->get_direct_debit_min_value();
			$config['min_'			. self::$BILPPAY_PAYMENT_ELV_METHOD . '_b2b'] 	= 0;
			$config['min_'			. self::$BILLPAY_PAYMENT_RAT_METHOD]			= $req->get_hire_purchase_min_value();
			$config['min_'			. self::$BILLPAY_PAYMENT_RAT_METHOD . '_b2b']	= 0;
			$config['static_limit_' . self::$BILPPAY_PAYMENT_METHOD] 				= $req->get_static_limit_invoice();
			$config['static_limit_' . self::$BILPPAY_PAYMENT_METHOD . '_b2b']		= $req->get_static_limit_invoicebusiness();
			$config['static_limit_' . self::$BILPPAY_PAYMENT_ELV_METHOD] 			= $req->get_static_limit_direct_debit();
			$config['static_limit_' . self::$BILPPAY_PAYMENT_ELV_METHOD . '_b2b']		= 0;
			$config['static_limit_' . self::$BILLPAY_PAYMENT_RAT_METHOD]			= $req->get_static_limit_hire_purchase();
			$config['static_limit_' . self::$BILLPAY_PAYMENT_RAT_METHOD . '_b2b']		= 0;
			$config['terms']														= $req->get_terms();

			$this->getLog()->logDebug("Module configuration received (country: $country, currency: $currency):");
			$this->getLog()->logDebug($config);

			$this->getSession()->setModuleConfig($config, $currency, $country);
		}

		return $config;
	}


	public function sendPreauthorizationRequest($paymentMethod, $salutation, $dateOfBirth, $phone, $termsAccepted, $expectedDaysTillShipping, $useHTMLFormat, $bankAccount = null, $orderId = '') {
		if ($this->isBillpayPayment($paymentMethod)) {
			/*
    		 * Since Mage 1.2 we have to reserve an order id here everytime.
    		 * Otherwise it can happen that the quote is still available
    		 * after external checkout failed and an order was created.
    		 * In this case the id of the previously created order will
    		 * mistakenly be transmitted.
    		 */
    		//$reservedOrderId = $this->getQuote()->getReservedOrderId();
    		//if (!$reservedOrderId) {
	    		//$this->getQuote()->reserveOrderId();
    		//}

			// Server-side validation if terms checkbox is ticked because client-side validation is too vulnerable for failure
			if (!$termsAccepted) {
				throw new Exception(Mage::helper('sales')->__('billpay_accept_terms'));
			}


			if ($this->isBillpayRatPayment($paymentMethod) && !$this->getCalculation()->isIntVal($this->getSession()->getBillpayRates()) || $this->getSession()->getBillpayRates() < 0) {
				throw new Exception(Mage::helper('sales')->__('billpay_invalid_ratecount'));
			}


			// We have to collect the totals here when onestepcheckout is used. The reason is that
		    // the onestepcheckout controler skips the savePayment logic and does not call collectTotals
		    // explictly like it is done in standard magento. If we do not call it here all total values equal 0.
		    //
		    // (note: seems that this is not necessary for Magento 1.4)
			if ($this->isOneStepCheckout($this->getQuote()->getStoreId())) {
		    	$this->getQuote()->collectTotals();
		    }

    		// Send the request
        	$result = $this->_sendPreauthorizationRequest($useHTMLFormat, $salutation, $dateOfBirth, $phone, $expectedDaysTillShipping, $paymentMethod, $bankAccount, $orderId);		// This will throw if something is going wrong ...

        	$this->getSession()->setTransactionId($result['txid']);
    	    return $result;
    	}
	}

	public function sendPrescoreRequest() {
				if ($this->isOneStepCheckout($this->getQuote()->getStoreId())) {
					$this->getQuote()->collectTotals();
				}

				$result = $this->_sendPrescoreRequest();		// This will throw if something is going wrong ...

				if (empty($result['txid']))
				{
				    $this->getSession()->setPrescoreResult(false);
				    return false;
				}
				else
				{
				    $this->getSession()->setPrescoreResult($result);
				    $this->getSession()->setPrescoreResultPaymentAlowed($result['payments_allowed']);
				    $this->getSession()->setCurrentRateOptions($result['base_amount'], $result['rate_info']);
				    $this->getSession()->setTermsConfig($result['terms']);
				    $this->getSession()->setTransactionId($result['txid']);
				    return true;
				}
	}

	public function sendValidationRequest($paymentMethod, $useHTMLFormat) {
		$quote = $this->getQuote();

		$billingAddress = $quote->getBillingAddress();
		$shippingAddress = $quote->getShippingAddress();
		$customerType = $this->mapCustomerType($quote->getCheckoutMethod());

    	$req = $this->createRequestObject('validate', $paymentMethod, $this->getQuote()->getStoreId());

    	// Set customer details (includes billing address)
    	$street = $billingAddress->getStreet();

    	$email = $this->isLoggedIn() ? $quote->getCustomer()->getEmail() : $billingAddress->getEmail();

    	$req->set_customer_details(
    			null,																				// customer ID
    			$customerType, 																		// customer type
    			trim($billingAddress->getPrefix()),													// salutation
    			trim($billingAddress->getSuffix()),													// title
    			$this->getCalculation()->obfuscateAddressPart($billingAddress->getFirstname()), 	// first name
    			$this->getCalculation()->obfuscateAddressPart($billingAddress->getLastname()),		// last name
    			$this->getCalculation()->obfuscateAddressPart($this->getFullStreetName($street)),	// street
    			'',																					// street no (is appended to street by default)
    			'',																					// address addition
    			$this->getCalculation()->obfuscateAddressPart($billingAddress->getPostcode()),		// zip
    			$this->getCalculation()->obfuscateAddressPart($billingAddress->getCity()),			// city
    			trim($billingAddress->getCountryModel()->getIso3Code()),							// country
    			$this->getCalculation()->obfuscateAddressPart($email),								// email
    			$this->getCalculation()->obfuscateAddressPart($billingAddress->getTelephone()),		// phone
    			'',																					// cell phone (no field present by default)
    			null,																				// birthday
    			trim($this->getCurrentLanguage()),													// language
    			null																				// ip
    		);

    	// Set shipping address
    	if ($shippingAddress->getSameAsBilling() == 1) {
    		$req->set_shipping_details(true);
    	}
    	else {
    		$street = $shippingAddress->getStreet();

    		$req->set_shipping_details(
    			false,																					// use_billing_address
    			trim($shippingAddress->getPrefix()),													// salutation
    			trim($shippingAddress->getSuffix()),													// title
    			$this->getCalculation()->obfuscateAddressPart(trim($shippingAddress->getFirstname())),	// first_name
    			$this->getCalculation()->obfuscateAddressPart(trim($shippingAddress->getLastname())),	// last_name
    			$this->getCalculation()->obfuscateAddressPart(trim($this->getFullStreetName($street))),	// street
    			'',																						// street_no (is appended to the street here)
    			'',																						// address_addition
    			$this->getCalculation()->obfuscateAddressPart(trim($shippingAddress->getPostcode())),	// zip
    			$this->getCalculation()->obfuscateAddressPart(trim($shippingAddress->getCity())),		// city
    			trim($shippingAddress->getCountryModel()->getIso3Code()),								// country
    			$this->getCalculation()->obfuscateAddressPart(trim($shippingAddress->getTelephone())),	// phone
    			''																						// cell_phone
    		);
    	}

	    try {
		   	$req->send();

		   	$this->getLog()->logDebug('Validation request xml:');
			$this->getLog()->logDebug($req->get_request_xml());
			$this->getLog()->logDebug('Validation response xml:');
			$this->getLog()->logDebug($req->get_response_xml());
	    }
	    catch (Exception $e) {
	    	$this->getLog()->logError('Error sending validation request:');
			$this->getLog()->logException($e);
            $error = Mage::helper('sales')->__('internal_error_occured');
            throw new Exception($error);
	    }

    	if ($req->get_error_code() > 0) {
			if ($req->get_error_code() == 1) {
				$this->getLog()->logError('Internal server error received (validation)');
			}
			else {
				$this->getLog()->logDebug('Validation request error code: ' . $req->get_error_code());
			}

			$error = $req->get_customer_error_message();
			throw new Exception($error);
		}
	}

	public function sendCaptureRequest($paymentMethod, $useHTMLFormat, $orderId = '') {
		if ($this->isBillpayPayment($paymentMethod)) {
			$txid = $this->getSession()->getTransactionId();
    		//$txid = $this->getFromSession('ipl_tx_id');

    		if (isset($txid)) { /* Validate the reserved order id with order id from session */
    		 	//$reservedOrderId = $this->getQuote()->getReservedOrderId();
    		 	//$reservedOrderIdSession = $this->getFromSession('ipl_res_order_id');

    		 	//if ($reservedOrderId != $reservedOrderIdSession) {
    		 	//	$this->getLog()->logError('Validation of reserved order id failed (Session: ' . $reservedOrderIdSession . ', Quote: ' . $reservedOrderId);
				// 	$errorMessage = $this->__('internal_error_occured');
				// 	throw new Exception($errorMessage);
    		 	//}
    		 	//else { /* This will throw if something is going wrong ... */
        			return $this->_sendCaptureRequest($paymentMethod, $txid, $useHTMLFormat, $orderId);
    		 	//}
    		}
    		else {
    			$this->getLog()->logError('Transaction ID not found in session');
    			$errorMessage = $this->__('internal_error_occured');
				throw new Exception($errorMessage);
    		}
    	}
	}

	public function sendCalculateRatesRequest($calculationBaseAmount, $cartTotalGross) {
		$quote = $this->getQuote();

	    $calculationBaseAmount 	= $this->currencyToSmallerUnit($calculationBaseAmount);
	    $cartTotalGross			= $this->currencyToSmallerUnit($cartTotalGross);

	    $currency = $quote->getQuoteCurrencyCode();
	    $country = $quote->getBillingAddress()->getCountryModel()->getIso3Code();
	    $language = $this->getCurrentLanguage();

	    $req = $this->createRequestObject('rates', self::$BILLPAY_PAYMENT_RAT_METHOD, $quote->getStoreId());
	    $req->set_locale($country, $currency, $language);
    	$req->set_rate_request_params(
    		$calculationBaseAmount,
    		$cartTotalGross
    	);

		try {
	    	$req->send();

	    	$this->getLog()->logDebug('Calculate rates request xml:');
			$this->getLog()->logDebug($req->get_request_xml());
			$this->getLog()->logDebug('Calculate rates response xml:');
			$this->getLog()->logDebug($req->get_response_xml());
	    }
	    catch (Exception $e) {
	    	$this->getLog()->logError('Error sending calculate rates request:');
			$this->getLog()->logException($e);

            $errorMessage =  $this->__('internal_error_occured');
            throw new Exception($errorMessage);
	    }

    	if ($req->get_error_code() > 0) {
			$this->getLog()->logDebug('Calculate rates request error code: ' . $req->get_error_code());

			$error = $this->createErrorMessage($req, true, $quote->getStoreId(), self::$BILLPAY_PAYMENT_RAT_METHOD);
			throw new Exception($error);
		}

		return array('options' => $req->get_options());
	}

	public function sendCancelRequest($order, $useHTMLFormat) {
		$paymentMethod = $order->getPayment()->getMethod();

		if ($this->isBillpayPayment($paymentMethod)) {
			$cartTotalPriceGross = $this->currencyToSmallerUnit($order->getGrandTotal()-$order->getTotalRefunded());

			if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $order->getStore()->getStoreId())) {
        		$cartTotalPriceGross += $this->getTrustedShopsAmount($order, true);
	        }

			$this->_sendCancelRequest($order, $paymentMethod, $cartTotalPriceGross, $useHTMLFormat);
		}
	}

	/**
     * @param $creditMemo Billpay_Model_Sales_Order_Creditmemo
     */
	public function sendPartialcancelRequest($creditMemo, $data, $useHTMLFormat) {
		$paymentMethod = $creditMemo->getOrder()->getPayment()->getMethod();
		if ($this->isBillpayPayment($paymentMethod)) { /* Send the request */
			try {
				// This will throw if something is going wrong ...
        		$result = $this->_sendPartialCancelRequest($creditMemo, $paymentMethod, $data, $useHTMLFormat);
			}
			catch (Exception $e) {
				throw new Mage_Core_Exception($e->getMessage());
			}
    	}
	}

	/**
     * @param $creditMemo Billpay_Model_Sales_Order_Creditmemo
     */
	public function sendEditCartContentRequest($creditMemo, $data, $useHTMLFormat) {
		$paymentMethod = $creditMemo->getOrder()->getPayment()->getMethod();
		if ($this->isBillpayPayment($paymentMethod)) { /* Send the request */
			try {
				// This will throw if something is going wrong ...
        		$result = $this->_sendEditCartContentRequest($creditMemo, $paymentMethod, $data, $useHTMLFormat);
			}
			catch (Exception $e) {
				throw new Mage_Core_Exception($e->getMessage());
			}
    	}
	}

	/**
	 * @param $order Billpay_Model_Sales_Order
	 */
	public function sendEditCartContentRequest2($order, $reference) {
	    $paymentMethod = $order->getPayment()->getMethod();
	    if ($this->isBillpayPayment($paymentMethod)) { /* Send the request */
	        try {
	            // This will throw if something is going wrong ...
	            $result = $this->_sendEditCartContentRequest2($order, $paymentMethod, $reference);
	        }
	        catch (Exception $e) {
	            throw new Mage_Core_Exception($e->getMessage());
	        }
	    }
	}

	/**
	 * @param Mage_Sales_Model_Order_Invoice $invoice
	 * @return array
	 */
	public function sendConfirmInvoiceRequest($invoice, $paymentMethod, $delayInDays, $useHTMLFormat) {
    	$order = $invoice->getOrder();

		$shippingPriceGross = $this->currencyToSmallerUnit($invoice->getShippingAmount() + $invoice->getShippingTaxAmount());
		$cartTotalPriceGross = $this->calculateCartTotalGross($invoice);

		// add trusted shops amount
		if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $order->getStore()->getId())) {
		    $trustedShopsAmount = $invoice->getTrustedShopsAmount();
	    	if(!empty($trustedShopsAmount)) {
	    		$cartTotalPriceGross += $this->currencyToSmallerUnit($trustedShopsAmount);
	    	}
		}

	    $storeId = $order->getStore()->getStoreId();

	    $req = $this->createRequestObject('invoiceCreated', $paymentMethod, $storeId);
        $req->set_invoice_params(
        	$cartTotalPriceGross,
        	$order->getOrderCurrencyCode(),
        	$order->getOriginalIncrementId() ? $order->getOriginalIncrementId() : $order->getIncrementId(),
        	$delayInDays
        );

	 	try {
			$req->send();

			$this->getLog()->logDebug('InvoiceCreated request xml:');
			$this->getLog()->logDebug($req->get_request_xml());
			$this->getLog()->logDebug('InvoiceCreated response xml:');
			$this->getLog()->logDebug($req->get_response_xml());
		}
		catch(Exception $e) {
			$this->getLog()->logError('Error sending InvoiceCreated request:');
			$this->getLog()->logException($e);

			// This will be caught by calling Mage_Adminhtml_Sales_OrderController controller
			throw new Mage_Core_Exception(Mage::helper('sales')->__('billpay_connection_failed_invoice'));
		}

		if ($req->has_error()) {
			$this->getLog()->logError('InvoiceCreated request error code: ' . $req->get_error_code());

			throw new Mage_Core_Exception($req->get_merchant_error_message() . ' (Error code: ' . $req->get_error_code() . ')');
		}

		$result = array();
		$result['account_holder'] 		= $req->get_account_holder();
		$result['account_number'] 		= $req->get_account_number();
		$result['bank_code'] 			= $req->get_bank_code();
		$result['bank_name'] 			= $req->get_bank_name();
		$result['invoice_duedate'] 		= $req->get_invoice_duedate();
		$result['invoice_reference'] 	= $req->get_invoice_reference();
		$result['dues']					= $req->get_dues();

		return $result;
	}

 	private function _sendCancelRequest($order, $paymentMethod, $cartTotalPriceGross, $useHTMLFormat) {
   		$storeId = $order->getStore()->getStoreId();

	    $req = $this->createRequestObject('cancel', $paymentMethod, $storeId);
        $req->set_cancel_params($order->getOriginalIncrementId() ? $order->getOriginalIncrementId() : $order->getIncrementId(), $cartTotalPriceGross, $order->getOrderCurrencyCode());

        try {
			$req->send();

			$this->getLog()->logDebug('Cancel request xml:');
			$this->getLog()->logDebug($req->get_request_xml());
			$this->getLog()->logDebug('Cancel response xml:');
			$this->getLog()->logDebug($req->get_response_xml());
		}
		catch(Exception $e) {
			$this->getLog()->logError('Error sending cancel request:');
			$this->getLog()->logException($e);

			// This will be caught by calling Mage_Adminhtml_Sales_OrderController controller
			throw new Mage_Core_Exception(Mage::helper('sales')->__('billpay_connection_failed'));
		}

		if ($req->has_error()) {
			if ($req->has_error() == 1) {
				$this->getLog()->logError('Internal server error received (cancel)');
			}
			else {
				$this->getLog()->logDebug('Cancel request error code: ' . $req->get_error_code());
			}

			throw new Mage_Core_Exception($req->get_merchant_error_message() . ' (Error code: ' . $req->get_error_code() . ')');
		}
		else {
			// Reset all values for transaction credit
			$info = $order->getPayment()->getMethodInstance()->getInfoInstance();
			$info->setBillpayRateDues('');
			$info->setBillpayRateSurcharge(0);
			$info->setBillpayRateCount(0);
			$info->setBillpayRateTotalAmount(0);
			$info->setBillpayRateInterestRate(0);
			$info->setBillpayRateAnualRate(0);
			$info->setBillpayRateBaseAmount(0);
			$info->setBillpayRateFee(0);
			$info->setBillpayRateResidualAmount(0);
		}
    }


	private function _sendCaptureRequest($paymentMethod, $txid, $useHTMLFormat, $orderId) {
    	$quote = $this->getQuote();

    	// This is necessary for trusted shops amount to be present
    	if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $quote->getStoreId())) {
    		$quote->collectTotals();
    	}

	    $shippingAddress = $quote->getShippingAddress();
	    $shippingPriceGross = $this->currencyToSmallerUnit($shippingAddress->getShippingAmount() + $shippingAddress->getShippingTaxAmount());

	    $cartTotalPriceGross = $this->calculateCartTotalGross($shippingAddress);
	    $currencyCode = $quote->getQuoteCurrencyCode();

	    $req = $this->createRequestObject('capture', $paymentMethod, $this->getQuote()->getStoreId());
    	$req->set_capture_params(
    		$txid,
    		$cartTotalPriceGross,
    		$currencyCode,
    		$orderId,
    		$shippingAddress->getCustomerId()
    	);

		try {
	    	$req->send();

	    	$this->getLog()->logDebug('Capture request xml:');
			$this->getLog()->logDebug($req->get_request_xml());

			$this->getLog()->logDebug('Capture response xml:');
			if ($this->isBillpayRatPayment($paymentMethod)) {
				$pos = strpos($req->get_response_xml(), '<hire_purchase>');
				$debug = substr($req->get_response_xml(), 0, $pos) . '[DEBUG SKIPPED]';
				$this->getLog()->logDebug($debug);
			}
			else {
				$this->getLog()->logDebug($req->get_response_xml());
			}
	    }
	    catch (Exception $e) {
	    	$this->getLog()->logError('Error sending capture request:');
			$this->getLog()->logException($e);

            $errorMessage =  $this->__('internal_error_occured');
            throw new Exception($errorMessage);
	    }

    	if ($req->get_error_code() > 0) {
			if ($req->get_error_code() == 1) {
				$this->getLog()->logError('Internal server error received (capture)');
			}
			else {
				$this->getLog()->logDebug('Capture request error code: ' . $req->get_error_code());
			}

			$error = $this->createErrorMessage($req, $useHTMLFormat, $quote->getStoreId(), $paymentMethod);
			throw new Exception($error);
		}

		if ($this->isBillpayRatPayment($paymentMethod)) {
			try {
				Mage::helper('billpay/attachment')->savePdfDocuments($req, $orderId, $quote->getStoreId());
			}
			catch(Exception $e) {
				$this->getLog()->logError('Error saving pdf documents for transaction credit order: ' . $orderId);
				$this->getLog()->logException($e);
			}
		}

		$result = array();
		$result['account_holder'] 		= $req->get_account_holder();
		$result['account_number'] 		= $req->get_account_number();
		$result['bank_code'] 			= $req->get_bank_code();
		$result['bank_name'] 			= $req->get_bank_name();
		$result['invoice_duedate'] 		= $req->get_invoice_duedate();
		$result['invoice_reference'] 	= $req->get_invoice_reference();

		return $result;
    }

    private function _sendPreauthorizationRequest($useHTMLFormat, $salutation, $dateOfBirth, $phone, $expectedDaysTillShipping, $paymentMethod, $bankAccount, $orderId) {
    	$quote = $this->getQuote();

		$billingAddress = $quote->getBillingAddress();
		$shippingAddress = $quote->getShippingAddress();

	    $customerType = $this->mapCustomerType($quote->getCheckoutMethod());
	    $customerId = $quote->getCustomerId();


	    $discount = $this->getCalculation()->calculateDiscount($shippingAddress, $quote, true, true);
	    $discountGross = $discount;

	    $shippingPrice = $this->getCalculation()->calculateShippingPrice($shippingAddress, $quote, false, true);
	    $shippingPriceGross = $this->getCalculation()->calculateShippingPrice($shippingAddress, $quote, true, true);

	    $cartTotalPrice = $this->calculateCartTotal($shippingAddress);
	    $cartTotalPriceGross = $this->calculateCartTotalGross($shippingAddress);

	    // Add trusted shops amount
		if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $quote->getStoreId())) {
		    $trustedShopsAmount = $quote->getTrustedShopsAmount();
	    }

	    $tmp = $this->getDateOfBirth();
	    if (!empty($tmp)) {
	    	$dateOfBirth = $this->formatMagentoDate($tmp);
	    }

	    $tmp = $this->getSalutation();
	    if (!empty($tmp)) {
	    	$salutation = $tmp;
	    }

	    $tmp = $this->getPhone();
	    if (!empty($tmp)) {
	    	$phone = $tmp;
	    }

	    $shippingName = $shippingAddress->getShippingDescription();
	    if (empty($shippingName)) {
	    	$shippingName = 'n/a';
	    }

	    $req = $this->createRequestObject('preauth', $paymentMethod, $this->getQuote()->getStoreId());

	    $req->set_prescore_enable($this->getSession()->getPrescore(),$this->getSession()->getTransactionId());

    	$req->set_terms_accepted(true);
    	$req->set_total(
    			$discount,																			// rebate
    			$discountGross,																		// rebate_gross
    			trim($shippingName),																// shipping_name
    			$shippingPrice,																		// shipping_price
    			$shippingPriceGross,																// shipping_price_gross
    			$cartTotalPrice,																	// cart_total_price
    			$cartTotalPriceGross,																// cart_total_price_gross
    			$quote->getQuoteCurrencyCode(),														// currency
    			$orderId																			// reference
    	);

        // Set bank account for elv
    	if ($bankAccount && ($this->isBillpayElvPayment($paymentMethod) || $this->isBillpayRatPayment($paymentMethod))) {
    		$req->set_bank_account(
    			trim($bankAccount['accountholder']),
    			trim($bankAccount['accountnumber']),
    			trim($bankAccount['sortcode'])
    		);
    	}

    	if ($this->isBillpayRatPayment($paymentMethod)) {
    		$totalAmount = $this->getSession()->getTotalPaymentAmount();
    		$rateCount = $this->getSession()->getBillpayRates();

    		$req->set_rate_request(
    			$rateCount,
    			$this->currencyToSmallerUnit($totalAmount)
    		);
    	}

    	// Set customer details (includes billing address)
    	$street = $billingAddress->getStreet();
		$email = $this->isLoggedIn() ? $quote->getCustomer()->getEmail() : $billingAddress->getEmail();

		if($this->getSession()->getCustomerGroup() == 'b') {
			$req->set_company_details(
					$this->getSession()->getCompanyName(),
					$this->getSession()->getLegalForm(),
					$this->getSession()->getRegisterNumber(),
					$this->getSession()->getHolderName(),
					$this->getSession()->getTaxNumber()
			);
		}

    	$req->set_customer_details(
    			$customerId,											// customer ID
    			$customerType, 											// customer type
    			trim($salutation),										// salutation
    			trim($billingAddress->getSuffix()),						// title
    			trim($billingAddress->getFirstname()), 					// first name
    			trim($billingAddress->getLastname()),					// last name
    			trim($this->getFullStreetName($street)),				// street
    			'',														// street no (is appended to street by default)
    			'',														// address addition
    			trim($billingAddress->getPostcode()),					// zip
    			trim($billingAddress->getCity()),						// city
    			trim($billingAddress->getCountryModel()->getIso3Code()),// country
    			trim($email),											// email
    			trim($phone),											// phone
    			'',														// cell phone (no field present by default)
    			$dateOfBirth,											// birthday
    			trim($this->getCurrentLanguage()),						// language
    			trim($this->getIpAddress()),							// ip
    			$this->getSession()->getCustomerGroup()
    			//'p'														// customer group,
    		);

    	// Set shipping address
    	if ($shippingAddress->getSameAsBilling() == 1) {
    		$req->set_shipping_details(true);
    	}
    	else {
    		$street = $shippingAddress->getStreet();

    		$req->set_shipping_details(
    			false,														// use_billing_address
    			trim($salutation),											// salutation
    			trim($shippingAddress->getSuffix()),						// title
    			trim($shippingAddress->getFirstname()),						// first_name
    			trim($shippingAddress->getLastname()),						// last_name
    			trim($this->getFullStreetName($street)),					// street
    			'',															// street_no (is appended to the street here)
    			'',															// address_addition
    			trim($shippingAddress->getPostcode()),						// zip
    			trim($shippingAddress->getCity()),							// city
    			trim($shippingAddress->getCountryModel()->getIso3Code()),	// country
    			trim($phone),												// phone
    			''															// cell_phone
    		);
    	}


    	// Add items
    	$products = array();
    	$items = $shippingAddress->getAllItems();
    	foreach ($items as $item) {
    		$product = $item->getProduct();
    		$type = $product->getTypeId();

    		$addItem = false;
    		if ($type == 'bundle') { /* Handle fix price bundled items as single item */
    			$priceType = $product->getPriceType();

    			if ($priceType == 1) {
    				$addItem = true;
    				$qty = (int)$item->getQty();
    				$price = $this->currencyToSmallerUnit($item->getCalculationPrice());
    				$priceGross = $this->calculateItemGross($item, $qty);
    			}

    			$products[] = $product;
    		}
    		else if (in_array($type, array('simple', 'virtual', 'ugiftcert', 'subscription_simple'))) {
    			$parentItem = $item->getParentItem();

    			if (isset($parentItem)) {
    				$parentProduct = $parentItem->getProduct();
    				$parentType = $parentProduct->getTypeId();

    				if ($parentType == 'configurable') {
    					$addItem = true;
    					$qty = (int)$parentItem->getQty();
    					$price = $this->currencyToSmallerUnit($parentItem->getCalculationPrice());
    					$priceGross = $this->calculateItemGross($parentItem, $qty);
    					$products[] = $product;
    				}
    				else if ($parentType == 'bundle') {
    					$parentPriceType = $parentProduct->getPriceType();

    					if ($parentPriceType != 1) {
    						$addItem = true;
	    					$parentQty = $parentItem->getQty();
    						$qty = (int)$parentQty * (int)$item->getQty();
    						$price = $this->currencyToSmallerUnit($item->getCalculationPrice());
    						$priceGross = $this->calculateItemGross($item, $qty);
	    					$products[] = $product;
    					}
    				}
    				else {
    					$this->getLog()->logError('Payment type ' . $parentType . ' not supported');
    					$error = Mage::helper('sales')->__('internal_error_occured');
    					throw new Exception($error);
    				}
    			}
    			else {
    				$addItem = true;
    				$qty = (int) $item->getQty();
    				$price = $this->currencyToSmallerUnit($item->getCalculationPrice());
    				$priceGross = $this->calculateItemGross($item, $qty);
    				$products[] = $product;
    			}
    		}

    		if ($addItem == true) {
	    		$req->add_article(
	    			$item->getItemId(),						// articleid
	    			$qty,									// articlequantity
	    			trim($product->getName()),				// articlename
	    			'',										// articledescription (Makes no sense here because descriptions are too long)
	    			$price,									// article_price
	    			$priceGross								// article_price_gross
	    		);
    		}
    	}


    	// set expected days till shipping
    	$tmp = Mage::helper('billpay')->getExpectedDaysTillShipping($products);
    	if ($tmp > 0) {
    		$expectedDaysTillShipping = $tmp;
    	}
    	$req->set_expected_days_till_shipping($expectedDaysTillShipping);

   		// if trusedShops buyer protection has been selected
   		if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $quote->getStoreId()) &&
   			isset($trustedShopsAmount) && $trustedShopsAmount > 0) {

            $req->add_article(
                '',   														// articleid
                1,  														// qty
                Mage::helper('sales')->__('billpay_trusted_shops'),   		// articlename
                '', 														// description
                $this->currencyToSmallerUnit($trustedShopsAmount),
                $this->currencyToSmallerUnit($trustedShopsAmount + $this->getCheckout()->getTsTaxAmount())
            );
   		}

   		$req->set_fraud_detection($this->getFraudIdentifier());

    	if (isset($customerId)) {
	    	// $orders = $this->_getCustomerHistory($customerId);
	    	$orders = Mage::helper('billpay')->getCustomerOrderHistory($customerId);
	    	if ($orders) {
		    	foreach ($orders as $row) {
		    		$storeId = $row['store_id'];

		    		$historyOrderId 		= $row['increment_id'];
		    		$historyOrderDate  		= $this->formatDatetime($row['created_at']);
		    		$historyOrderCurrency	= $row['order_currency_code'];
		    		$historyPaymentType 	= $this->mapPaymentMethod($row['method']);
		    		$historyOrderStatus		= $this->mapOrderState($row['status'], $storeId);

		    		$s = $this->currencyToSmallerUnit($row['shipping_amount'] + $row['shipping_tax_amount']);
			    	$historyTotalGross = $this->currencyToSmallerUnit($row['subtotal'] + $row['tax_amount']) + $s;

		    		$req->add_order_history(
		    			$historyOrderId,
		    			$historyOrderDate,
		    			$historyTotalGross,
		    			$historyOrderCurrency,
		    			$historyPaymentType,
		    			$historyOrderStatus
		    		);
		    	}
	    	}
    	}

	    try {
	    	$req->send();

	    	$this->getLog()->logDebug('Preauthorization request xml:');
			$this->getLog()->logDebug($req->get_request_xml());
			$this->getLog()->logDebug('Preauthorization response xml:');
			$this->getLog()->logDebug($req->get_response_xml());
	    }
	    catch (Exception $e) {
	    	$this->getLog()->logError('Error sending preauthorization request:');
			$this->getLog()->logException($e);
            $error = Mage::helper('sales')->__('internal_error_occured');
            throw new Exception($error);
	    }

	    if ($req->get_status() == 'DENIED') {
	    	$this->getCheckout()->setData('hide_billpay_payment_method', true);
	    }

    	if ($req->get_error_code() > 0 || $req->get_status() != 'APPROVED') {
			if ($req->get_error_code() == 1) {
				$this->getLog()->logError('Internal server error received (preauthorization)');
			}
			else {
				$this->getLog()->logDebug('Preauthorization request error code: ' . $req->get_error_code());
			}

			$error = $this->createErrorMessage($req, $useHTMLFormat, $quote->getStoreId(), $paymentMethod);
			if ($this->getConfigData('settings/activate_auto_hide_payment', $quote->getStoreId()) &&
				!$this->isOneStepCheckout($this->getQuote()->getStoreId()) &&
				$req->get_status() == 'DENIED') {
					throw new Mage_Payment_Exception($error, "BILLPAY_DENIED");
			}
			throw new Exception($error);
		}

		$result = array();
		$result['txid'] = $req->get_bptid();
		$result['corrected_street'] = $req->get_corrected_street();
		$result['corrected_corrected_street_no'] = $req->get_corrected_street_no();
		$result['corrected_zip'] = $req->get_corrected_zip();
		$result['corrected_city'] = $req->get_corrected_city();
		$result['corrected_country'] = $req->get_corrected_country();

		// add invoice data
		if ($req->get_capture_request_nesessary() == false) {
			$result['account_holder'] 		= $req->get_account_holder();
			$result['account_number'] 		= $req->get_account_number();
			$result['bank_code'] 			= $req->get_bank_code();
			$result['bank_name'] 			= $req->get_bank_name();
			$result['invoice_duedate'] 		= $req->get_invoice_duedate();
			$result['invoice_reference'] 	= $req->get_invoice_reference();
    	}

    	return $result;
    }


    private function _sendPrescoreRequest() {
    	$quote = $this->getQuote();

    	$billingAddress = $quote->getBillingAddress();
    	$shippingAddress = $quote->getShippingAddress();

    	$customerType = $this->mapCustomerType($quote->getCheckoutMethod());
    	$customerId = $quote->getCustomerId();
    	$customer_data = Mage::getModel('customer/customer')->load($customerId);
    	$customer_data = $customer_data->__toArray();



    	$discount = $this->getCalculation()->calculateDiscount($shippingAddress, $quote, true, true);
    	$discountGross = $discount;

    	$shippingPrice = $this->getCalculation()->calculateShippingPrice($shippingAddress, $quote, false, true);
    	$shippingPriceGross = $this->getCalculation()->calculateShippingPrice($shippingAddress, $quote, true, true);

    	$cartTotalPrice = $this->calculateCartTotal($shippingAddress);
    	$cartTotalPriceGross = $this->calculateCartTotalGross($shippingAddress);

    	// Add trusted shops amount
    	if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $quote->getStoreId())) {
    		$trustedShopsAmount = $quote->getTrustedShopsAmount();
    	}

    	$tmp = $this->getDateOfBirth();
	    if (!empty($tmp)) {
	    	$dateOfBirth = $this->formatMagentoDate($tmp);
	    }

	    $tmp = $this->getSalutation();
	    if (!empty($tmp)) {
	    	$salutation = $tmp;
	    }

	    $tmp = $this->getPhone();
	    if (!empty($tmp)) {
	    	$phone = $tmp;
	    }

	    $shippingName = $shippingAddress->getShippingDescription();
	    if (empty($shippingName)) {
	    	$shippingName = 'n/a';
	    }
	    //TODO: Hardcoded payment since we anyway dont use it, this should be change !!
    	$paymentMethod = "billpay_elv";
    	$req = $this->createRequestObject('prescore', $paymentMethod, $this->getQuote()->getStoreId());
    	$req->set_total(
    			$discount,																			// rebate
    			$discountGross,																		// rebate_gross
    			trim($shippingName),																// shipping_name
    			$shippingPrice,																		// shipping_price
    			$shippingPriceGross,																// shipping_price_gross
    			$cartTotalPrice,																	// cart_total_price
    			$cartTotalPriceGross,																// cart_total_price_gross
    			$quote->getQuoteCurrencyCode()														// currency
    	);



    	// Set customer details (includes billing address)
    	$street = $billingAddress->getStreet();
    	$email = $this->isLoggedIn() ? $quote->getCustomer()->getEmail() : $billingAddress->getEmail();

    	if($this->getSession()->getCustomerGroup() == 'b') {
    		$req->set_company_details(
    				$this->getSession()->getCompanyName(),
    				$this->getSession()->getLegalForm(),
    				$this->getSession()->getRegisterNumber(),
    				$this->getSession()->getHolderName(),
    				$this->getSession()->getTaxNumber()
    		);
    	}

    	$req->set_customer_details(
    			$customerId,											// customer ID
    			$customerType, 											// customer type
    			trim($salutation),										// salutation
    			trim($billingAddress->getSuffix()),						// title
    			trim($billingAddress->getFirstname()), 					// first name
    			trim($billingAddress->getLastname()),					// last name
    			trim($this->getFullStreetName($street)),				// street
    			'',														// street no (is appended to street by default)
    			'',														// address addition
    			trim($billingAddress->getPostcode()),					// zip
    			trim($billingAddress->getCity()),						// city
    			trim($billingAddress->getCountryModel()->getIso3Code()),// country
    			trim($email),											// email
    			trim($phone),											// phone
    			'',														// cell phone (no field present by default)
    			$dateOfBirth,											// birthday
    			trim($this->getCurrentLanguage()),						// language
    			trim($this->getIpAddress()),							// ip
    			$this->getSession()->getCustomerGroup()
    			//'p'														// customer group,
    	);

    	// Set shipping address
    	if ($shippingAddress->getSameAsBilling() == 1) {
    		$req->set_shipping_details(true);
    	}
    	else {
    		$street = $shippingAddress->getStreet();

    		$req->set_shipping_details(
    				false,														// use_billing_address
    				trim($salutation),											// salutation
    				trim($shippingAddress->getSuffix()),						// title
    				trim($shippingAddress->getFirstname()),						// first_name
    				trim($shippingAddress->getLastname()),						// last_name
    				trim($this->getFullStreetName($street)),					// street
    				'',															// street_no (is appended to the street here)
    				'',															// address_addition
    				trim($shippingAddress->getPostcode()),						// zip
    				trim($shippingAddress->getCity()),							// city
    				trim($shippingAddress->getCountryModel()->getIso3Code()),	// country
    				trim($phone),												// phone
    				''															// cell_phone
    		);
    	}


    	// Add items
    	$products = array();
    	$items = $shippingAddress->getAllItems();
    	foreach ($items as $item) {
    		$product = $item->getProduct();
    		$type = $product->getTypeId();

    		$addItem = false;
    		if ($type == 'bundle') { /* Handle fix price bundled items as single item */
    			$priceType = $product->getPriceType();

    			if ($priceType == 1) {
    				$addItem = true;
    				$qty = (int)$item->getQty();
    				$price = $this->currencyToSmallerUnit($item->getCalculationPrice());
    				$priceGross = $this->calculateItemGross($item, $qty);
    			}

    			$products[] = $product;
    		}
    		else if (in_array($type, array('simple', 'virtual', 'ugiftcert', 'subscription_simple'))) {
    			$parentItem = $item->getParentItem();

    			if (isset($parentItem)) {
    				$parentProduct = $parentItem->getProduct();
    				$parentType = $parentProduct->getTypeId();

    				if ($parentType == 'configurable') {
    					$addItem = true;
    					$qty = (int)$parentItem->getQty();
    					$price = $this->currencyToSmallerUnit($parentItem->getCalculationPrice());
    					$priceGross = $this->calculateItemGross($parentItem, $qty);
    					$products[] = $product;
    				}
    				else if ($parentType == 'bundle') {
    					$parentPriceType = $parentProduct->getPriceType();

    					if ($parentPriceType != 1) {
    						$addItem = true;
    						$parentQty = $parentItem->getQty();
    						$qty = (int)$parentQty * (int)$item->getQty();
    						$price = $this->currencyToSmallerUnit($item->getCalculationPrice());
    						$priceGross = $this->calculateItemGross($item, $qty);
    						$products[] = $product;
    					}
    				}
    				else {
    					$this->getLog()->logError('Payment type ' . $parentType . ' not supported');
    					$error = Mage::helper('sales')->__('internal_error_occured');
    					throw new Exception($error);
    				}
    			}
    			else {
    				$addItem = true;
    				$qty = (int) $item->getQty();
    				$price = $this->currencyToSmallerUnit($item->getCalculationPrice());
    				$priceGross = $this->calculateItemGross($item, $qty);
    				$products[] = $product;
    			}
    		}

    		if ($addItem == true) {
    			$req->add_article(
    					$item->getItemId(),						// articleid
    					$qty,									// articlequantity
    					trim($product->getName()),				// articlename
    					'',										// articledescription (Makes no sense here because descriptions are too long)
    					$price,									// article_price
    					$priceGross								// article_price_gross
    			);
    		}
    	}


    	// set expected days till shipping
    	/*
    	$tmp = Mage::helper('billpay')->getExpectedDaysTillShipping($products);
    	if ($tmp > 0) {
    		$expectedDaysTillShipping = $tmp;
    	}
    	$req->set_expected_days_till_shipping($expectedDaysTillShipping);
*/
    	// if trusedShops buyer protection has been selected
    	if ($this->getConfigData('settings/use_trusted_shops_buyer_protection', $quote->getStoreId()) &&
    			isset($trustedShopsAmount) && $trustedShopsAmount > 0) {

    		$req->add_article(
    				'',   														// articleid
    				1,  														// qty
    				Mage::helper('sales')->__('billpay_trusted_shops'),   		// articlename
    				'', 														// description
    				$this->currencyToSmallerUnit($trustedShopsAmount),
    				$this->currencyToSmallerUnit($trustedShopsAmount + $this->getCheckout()->getTsTaxAmount())
    		);
    	}

    	//$req->set_fraud_detection($this->getFraudIdentifier());

    	if (isset($customerId)) {
    		// $orders = $this->_getCustomerHistory($customerId);
    		$orders = Mage::helper('billpay')->getCustomerOrderHistory($customerId);
    		if ($orders) {
    			foreach ($orders as $row) {
    				$storeId = $row['store_id'];

    				$historyOrderId 		= $row['increment_id'];
    				$historyOrderDate  		= $this->formatDatetime($row['created_at']);
    				$historyOrderCurrency	= $row['order_currency_code'];
    				$historyPaymentType 	= $this->mapPaymentMethod($row['method']);
    				$historyOrderStatus		= $this->mapOrderState($row['status'], $storeId);

    				$s = $this->currencyToSmallerUnit($row['shipping_amount'] + $row['shipping_tax_amount']);
    				$historyTotalGross = $this->currencyToSmallerUnit($row['subtotal'] + $row['tax_amount']) + $s;

    				$req->add_order_history(
    						$historyOrderId,
    						$historyOrderDate,
    						$historyTotalGross,
    						$historyOrderCurrency,
    						$historyPaymentType,
    						$historyOrderStatus
    				);
    			}
    		}
    	}

    	try {
    		$req->send();

    		$this->getLog()->logDebug('Prescore request xml:');
    		$this->getLog()->logDebug($req->get_request_xml());
    		$this->getLog()->logDebug('Prescore response xml:');
    		$this->getLog()->logDebug($req->get_response_xml());
    	}
    	catch (Exception $e) {
    		$this->getLog()->logError('Error sending prescore request:');
    		$this->getLog()->logException($e);
    		$error = Mage::helper('sales')->__('internal_error_occured');
    		throw new Exception($error);
    	}

    	if ($req->get_status() == 'DENIED') {
    		$this->getCheckout()->setData('hide_billpay_payment_method', true);
    	}

    	if ($req->get_error_code() > 0 || $req->get_status() != 'APPROVED') {
    		if ($req->get_error_code() == 1) {
    			$this->getLog()->logError('Internal server error received (prescore)');
    		}
    		else {
    			$this->getLog()->logDebug('Prescore request error code: ' . $req->get_error_code());
    		}

    		//$error = $this->createErrorMessage($req, $useHTMLFormat, $quote->getStoreId(), $paymentMethod);
    		/*if ($this->getConfigData('settings/activate_auto_hide_payment', $quote->getStoreId()) &&
    				!$this->isOneStepCheckout($this->getQuote()->getStoreId()) &&
    				$req->get_status() == 'DENIED') {
    			throw new Mage_Payment_Exception($error, "BILLPAY_DENIED");
    		}
    		throw new Exception($error);*/
    	}
    	list($key1, $first_rate_option) = each($req->get_terms());
    	$result = array();
    	$result['txid'] = $req->get_bptid();
    	$result['corrected_street'] = $req->get_corrected_street();
    	$result['corrected_corrected_street_no'] = $req->get_corrected_street_no();
    	$result['corrected_zip'] = $req->get_corrected_zip();
    	$result['corrected_city'] = $req->get_corrected_city();
    	$result['corrected_country'] = $req->get_corrected_country();
    	$result['payments_allowed'] = $req->get_payments_allowed();
    	$result['payments_allowed_all'] = $req->get_payments_allowed_all();
    	$result['rate_info'] = $req->get_rate_info();
    	$result['terms'] = $req->get_terms();
    	$result['base_amount'] = $result['rate_info'][$first_rate_option]['calculation']['base'];



    	return $result;
    }


    /**
     *
     * @param $creditMemo Billpay_Model_Sales_Order_Creditmemo
     * @param $data is obsolute!
     */
	private function _sendEditCartContentRequest($creditMemo, $paymentMethod, $data, $useHTMLFormat) {
	    $storeId = $creditMemo->getOrder()->getStore()->getStoreId();
		$req = $this->createRequestObject('editCartContent', $paymentMethod, $storeId);
		$order = $creditMemo->getOrder();

		$newGrandTotal = round($order->getGrandTotal()-$order->getTotalRefunded(),3);

		if ($newGrandTotal <= 0) {
			$carttotalgross = $this->currencyToSmallerUnit($creditMemo->getGrandTotal());

			$this->getLog()->logDebug('Remaining order amount is being refunded. Send cancel request.');
			$this->_sendCancelRequest($order, $paymentMethod, $carttotalgross, $useHTMLFormat);
		}
		else {
			$originalShippingGross = $order->getShippingAmount() + $order->getShippingTaxAmount() + $order->getBillpayChargedFee();
			$refundedShippingGross = $order->getShippingRefunded() + $order->getShippingTaxRefunded() + $order->getBillpayChargedFeeRefunded();

			$originalTotalNet = $order->getGrandTotal() - $order->getTaxAmount();
			$refundedNet = $order->getTotalRefunded() - $order->getTaxRefunded();

			$originalDiscountGross = $order->getDiscountAmount() < 0 ? -$order->getDiscountAmount() : $order->getDiscountAmount();
			$discountRefundedGross = $order->getDiscountRefunded() < 0 ? -$order->getDiscountRefunded() : $order->getDiscountRefunded();
			$discountGross = $this->currencyToSmallerUnit($originalDiscountGross-$discountRefundedGross);
			$discountNet = $discountGross;

			$shippingName = $order->getShippingDescription();
			if (empty($shippingName)) {
		    	$shippingName = 'n/a';
		    }

			// set totals
			$req->set_total($discountNet < 0 ? -$discountNet : $discountNet,
				$discountGross < 0 ? -$discountGross : $discountGross,
				$shippingName,
				$this->currencyToSmallerUnit($order->getShippingAmount()-$order->getShippingRefunded()),
				$this->currencyToSmallerUnit($originalShippingGross-$refundedShippingGross),
				$this->currencyToSmallerUnit($originalTotalNet-$refundedNet),
				$this->currencyToSmallerUnit($order->getGrandTotal()-$order->getTotalRefunded()),
				$order->getOrderCurrencyCode(),
				$order->getOriginalIncrementId() ? $order->getOriginalIncrementId() : $order->getIncrementId());
			
			

			// set item lines
			foreach ($order->getItemsCollection() as $orderItem) {
				$qty = (int)$orderItem->getQtyOrdered()-$orderItem->getQtyRefunded();
				if ($qty <= 0) {
					continue;
				}

				$type = $orderItem->getProductType();
	    		$parentItem = $orderItem->getParentItem();

	    		if ($parentItem) {
	    			$parentType = $parentItem->getProductType();
	    			if ($parentType == 'bundle') {
	    				if ($options = $parentItem->getProductOptions()) {
		            		if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_CHILD) {
		            			/* dynamic pricing: add child items */
		            			$req->add_article($orderItem->getId(), $qty,
									$orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
									$this->calculateItemGross($orderItem,$qty));
			            	}
		    		    }
	    			}
					else if ($parentType == 'configurable') {
					}
	    			else {
	    				$req->add_article($orderItem->getId(), $qty,
									$orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
									$this->calculateItemGross($orderItem,$qty));
	    			}
	    		}
	    		else {
	    			if ($type == 'bundle') {
	    				if ($options = $orderItem->getProductOptions()) {
		            		if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_PARENT) {
		            			/* static pricing: add parent item */
		            			$req->add_article($orderItem->getId(), $qty,
									$orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
									$this->calculateItemGross($orderItem,$qty));
			            	}
		    		    }
	    			}
	    			else if(in_array($type, array('simple', 'grouped', 'ugiftcert', 'subscription_simple'))) {
	    				$req->add_article($orderItem->getId(), $qty,
									$orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
									$this->calculateItemGross($orderItem,$qty));
	    			}
					else if($type == 'configurable') {
						$children = $orderItem->getChildrenItems();
						if (count($children) > 0) {
							
							$req->add_article($orderItem->getId(), $qty,
									$orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
									$this->calculateItemGross($orderItem,$qty));
						}
					}
	    		}
			}

			try {
		    	$req->send();

		    	$this->getLog()->logDebug('Edit cart content request xml:');
				$this->getLog()->logDebug($req->get_request_xml());
				$this->getLog()->logDebug('Edit cart content response xml:');
				$this->getLog()->logDebug($req->get_response_xml());
		    }
		    catch (Exception $e) {
		    	$this->getLog()->logError('Error sending edit cart content request:');
				$this->getLog()->logException($e);
	            $error = Mage::helper('sales')->__('internal_error_occured');
	            throw new Exception($error);
		    }

			if ($req->get_error_code() > 0) {
				if ($req->get_error_code() == 1) {
					$this->getLog()->logError('Internal server error received (edit cart content)');
				}
				else {
					$this->getLog()->logDebug('Edit cart content error code: ' . $req->get_error_code());
				}

	    		throw new Exception($req->get_merchant_error_message() . ' (Error code: ' . $req->get_error_code() . ')');
			}

			// Update transaction credit params
			if ($this->isBillpayRatPayment($paymentMethod)) {
				$this->updateTransactionCreditParams($creditMemo, $req);
			}
		}
	}


	/**
	 *
	 * @param $creditMemo Billpay_Model_Sales_Order_Creditmemo
	 * @param $data is obsolute!
	 */
	private function _sendEditCartContentRequest2($order, $paymentMethod, $reference) {
	    $storeId = $order->getStore()->getStoreId();
	    $req = $this->createRequestObject('editCartContent', $paymentMethod, $storeId);
	    //$order = $creditMemo->getOrder();

	    $newGrandTotal = round($order->getGrandTotal(),3);

	    if ($newGrandTotal <= 0) {
	        $carttotalgross = $this->currencyToSmallerUnit($newGrandTotal);

	        $this->getLog()->logDebug('Remaining order amount is being refunded. Send cancel request.');
	        $this->_sendCancelRequest($order, $paymentMethod, $carttotalgross, $useHTMLFormat);
	    }
	    else {
	        $originalShippingGross = $order->getShippingAmount() + $order->getShippingTaxAmount() + $order->getBillpayChargedFee();
	        $refundedShippingGross = $order->getShippingRefunded() + $order->getShippingTaxRefunded() + $order->getBillpayChargedFeeRefunded();

	        $originalTotalNet = $order->getGrandTotal() - $order->getTaxAmount();
	        $refundedNet = $order->getTotalRefunded() - $order->getTaxRefunded();

	        $originalDiscountGross = $order->getDiscountAmount() < 0 ? -$order->getDiscountAmount() : $order->getDiscountAmount();
	        $discountRefundedGross = $order->getDiscountRefunded() < 0 ? -$order->getDiscountRefunded() : $order->getDiscountRefunded();
	        $discountGross = $this->currencyToSmallerUnit($originalDiscountGross-$discountRefundedGross);
	        $discountNet = $discountGross;

	        $shippingName = $order->getShippingDescription();
	        if (empty($shippingName)) {
	            $shippingName = 'n/a';
	        }

	        // set totals
	        $req->set_total($discountNet < 0 ? -$discountNet : $discountNet,
	                $discountGross < 0 ? -$discountGross : $discountGross,
	                $shippingName,
	                $this->currencyToSmallerUnit($order->getShippingAmount()-$order->getShippingRefunded()),
	                $this->currencyToSmallerUnit($originalShippingGross-$refundedShippingGross),
	                $this->currencyToSmallerUnit($originalTotalNet-$refundedNet),
	                $this->currencyToSmallerUnit($order->getGrandTotal()-$order->getTotalRefunded()),
	                $order->getOrderCurrencyCode(),
	                $reference);


	        // set item lines
	        foreach ($order->getItemsCollection() as $orderItem) {
	        	$qty = (int)$orderItem->getQtyOrdered()-$orderItem->getQtyRefunded();
	            if ($qty <= 0) {
	                continue;
	            }
	            $iId = $orderItem->getId();
	            if(empty($iId)){
	                $iId = $orderItem->getData('product_id');
	            }

	            $type = $orderItem->getProductType();
	            $parentItem = $orderItem->getParentItem();

	            if ($parentItem) {
	                $parentType = $parentItem->getProductType();
	                if ($parentType == 'bundle') {
	                    if ($options = $parentItem->getProductOptions()) {
	                        if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_CHILD) {
	                            /* dynamic pricing: add child items */
	                            $req->add_article($iId, $qty,
	                                    $orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
	                                    $this->calculateItemGross($orderItem,$qty));
	                        }
	                    }
	                }
	                else if ($parentType == 'configurable') {
	                }
	                else {
	                    $req->add_article($iId, $qty,
	                            $orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
	                            $this->calculateItemGross($orderItem,$qty));
	                }
	            }
	            else {
	                if ($type == 'bundle') {
	                    if ($options = $orderItem->getProductOptions()) {
	                        if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_PARENT) {
	                            /* static pricing: add parent item */
	                            $req->add_article($iId, $qty,
	                                    $orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
	                                    $this->calculateItemGross($orderItem,$qty));
	                        }
	                    }
	                }
	                else if(in_array($type, array('simple', 'grouped', 'ugiftcert', 'subscription_simple'))) {
	                    $req->add_article($iId, $qty,
	                            $orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
	                            $this->calculateItemGross($orderItem,$qty));
	                }
	                else if($type == 'configurable') {
	                    $children = $orderItem->getChildrenItems();
	                    if (count($children) > 0) {
	                        $req->add_article($iId, $qty,
	                                $orderItem->getName(), '', $this->currencyToSmallerUnit($orderItem->getPrice()),
	                                $this->calculateItemGross($orderItem,$qty));
	                    }
	                }
	            }
	        }

	        try {
	            $req->send();

	            $this->getLog()->logDebug('Edit cart content request xml:');
	            $this->getLog()->logDebug($req->get_request_xml());
	            $this->getLog()->logDebug('Edit cart content response xml:');
	            $this->getLog()->logDebug($req->get_response_xml());
	        }
	        catch (Exception $e) {
	            $this->getLog()->logError('Error sending edit cart content request:');
	            $this->getLog()->logException($e);
	            $error = Mage::helper('sales')->__('internal_error_occured');
	            throw new Exception($error);
	        }

	        if ($req->get_error_code() > 0) {
	            if ($req->get_error_code() == 1) {
	                $this->getLog()->logError('Internal server error received (edit cart content)');
	            }
	            else {
	                $this->getLog()->logDebug('Edit cart content error code: ' . $req->get_error_code());
	            }

	            throw new Exception($req->get_merchant_error_message() . ' (Error code: ' . $req->get_error_code() . ')');
	        }


	    }
	}

	/**
     * @param $creditMemo Billpay_Model_Sales_Order_Creditmemo
     */
	private function _sendPartialCancelRequest($creditMemo, $paymentMethod, $data, $useHTMLFormat) {
		$storeId = $creditMemo->getOrder()->getStore()->getStoreId();

		$feeRefunded = $creditMemo->getBillpayChargedFeeRefunded();
		$feeRefundedNet = $creditMemo->getBillpayChargedFeeRefundedNet();

		$shippingTax = $creditMemo->getShippingTaxAmount();
	    $shipping = $creditMemo->getShippingAmount();
	    $adjustmentPositive = $creditMemo->getAdjustmentPositive();
	    $adjustmentNegative = $creditMemo->getAdjustmentNegative();
	    $currentDiscount = $creditMemo->getDiscountAmount() > 0 ? $creditMemo->getDiscountAmount() : -$creditMemo->getDiscountAmount();

	    if ($creditMemo->getDiscountAmount() && $creditMemo->getDiscountAmount() != 0) {
	    	if ($creditMemo->getGrandTotal() > 0 && $creditMemo->getSubtotalInclTax()) {
		    	$discount = $creditMemo->getGrandTotal()
					- $creditMemo->getSubtotalInclTax()
					- $creditMemo->getAdjustmentPositive()
					+ $creditMemo->getAdjustmentNegative()
					- $creditMemo->getShippingAmount()
					- $creditMemo->getShippingTaxAmount()
					- $feeRefunded;
	    	}
	    	else {
	    		$discount = $creditMemo->getDiscountAmount();
	    	}

			if ($discount < 0) {
				$discount = -$discount;
			}
	    }
	    else {
	    	 $discount = 0;
	    }

	    $rebateDecreaseAmount = $this->currencyToSmallerUnit($discount - $adjustmentPositive + $adjustmentNegative);
	    $rebateDecreaseAmountGross = $rebateDecreaseAmount;

	    $shippingDescrease = $this->currencyToSmallerUnit($shipping + $feeRefundedNet);
	    $shippingDescreaseGross = $this->currencyToSmallerUnit($shipping + $shippingTax + $feeRefunded);

		$currency = $creditMemo->getOrder()->getOrderCurrencyCode();

		$req = $this->createRequestObject('partialcancel', $paymentMethod, $storeId);
		$req->set_cancel_params(
			$creditMemo->getOrder()->getOriginalIncrementId(),
			$rebateDecreaseAmount,
			$rebateDecreaseAmountGross,
			$shippingDescrease,
			$shippingDescreaseGross,
			$currency
		);

		// Add canceled items
		$items = $creditMemo->getAllItems();
		if ($items) {
			foreach ($items as $item) {
				$orderItem = $item->getOrderItem();
				$type = $orderItem->getProductType();
    			$parentItem = $orderItem->getParentItem();

    			if ($parentItem) {
    				$parentType = $parentItem->getProductType();
    				if ($parentType == 'bundle') {
    					if ($options = $parentItem->getProductOptions()) {
		            		if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_CHILD) {
		            			/* dynamic pricing: add child items */
		            			$req->add_canceled_article($orderItem->getQuoteItemId(), $item->getQty());
			            	}
		    		    }
    				}
					else if ($parentType == 'configurable') {
					}
    				else {
    					$req->add_canceled_article($orderItem->getQuoteItemId(), $item->getQty());
    				}
    			}
    			else {
    				if ($type == 'bundle') {
    					if ($options = $orderItem->getProductOptions()) {
		            		if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_PARENT) {
		            			/* static pricing: add parent item */
		            			$req->add_canceled_article($orderItem->getQuoteItemId(), $item->getQty());
			            	}
		    		    }
    				}
    				else if(in_array($type, array('simple', 'grouped', 'ugiftcert', 'subscription_simple'))) {
    					$req->add_canceled_article($orderItem->getQuoteItemId(), $item->getQty());
    				}
					else if($type == 'configurable') {
						$children = $orderItem->getChildrenItems();
						if (count($children) > 0) {
							$req->add_canceled_article($children[0]->getQuoteItemId(), $item->getQty());
						}
					}
    			}
			}
		}

		try {
	    	$req->send();

	    	$this->getLog()->logDebug('Partial cancel request xml:');
			$this->getLog()->logDebug($req->get_request_xml());
			$this->getLog()->logDebug('Partial cancel response xml:');
			$this->getLog()->logDebug($req->get_response_xml());
	    }
	    catch (Exception $e) {
	    	$this->getLog()->logError('Error sending partial cancel request:');
			$this->getLog()->logException($e);
            $error = Mage::helper('sales')->__('internal_error_occured');
            throw new Exception($error);
	    }

		if ($req->get_error_code() > 0) {
			if ($req->get_error_code() == 1) {
				$this->getLog()->logError('Internal server error received (partial cancel)');
			}
			else {
				$this->getLog()->logDebug('Partial cancel request error code: ' . $req->get_error_code());
			}

    		throw new Exception($req->get_merchant_error_message() . ' (Error code: ' . $req->get_error_code() . ')');
		}

		// Update transaction credit params
		if ($this->isBillpayRatPayment($paymentMethod)) {
			$this->updateTransactionCreditParams($creditMemo, $req);
		}
	}

	/**
	 *
	 * Update parameters for transaction credit after credit memo
	 * @param $creditMemo
	 * @param $req
	 */
	private function updateTransactionCreditParams($creditMemo, $req) {
		$dueUpdate 		= $req->get_due_update();
		$numberOfRates 	= $req->get_number_of_rates();
		$info 			= $creditMemo->getOrder()->getPayment()->getMethodInstance()->getInfoInstance();

		if (!$dueUpdate) {	/* FULL CANCEL */
			$info->setBillpayRateDues('');
			$info->setBillpayRateSurcharge(0);
			$info->setBillpayRateCount(0);
			$info->setBillpayRateTotalAmount(0);
			$info->setBillpayRateInterestRate(0);
			$info->setBillpayRateAnualRate(0);
			$info->setBillpayRateBaseAmount(0);
			$info->setBillpayRateFee(0);
			$info->setBillpayRateFeeTax(0);
			$info->setBillpayRateResidualAmount(0);
		}
		else {
			$dues = $this->getCalculation()->getSerializedDues($dueUpdate['dues']);
			$info->setBillpayRateDues($dues);
			$info->setBillpayRateSurcharge($dueUpdate['calculation']['surcharge']/100);
			$info->setBillpayRateCount($numberOfRates);
			$info->setBillpayRateTotalAmount($dueUpdate['calculation']['total']/100);
			$info->setBillpayRateInterestRate($dueUpdate['calculation']['interest']/100);
			$info->setBillpayRateAnualRate($dueUpdate['calculation']['anual']/100);
			$info->setBillpayRateBaseAmount($dueUpdate['calculation']['base']/100);
			$info->setBillpayRateFee($dueUpdate['calculation']['fee']/100);
			$info->setBillpayRateResidualAmount(($dueUpdate['calculation']['cart']-$dueUpdate['calculation']['base'])/100);

			// change temporaty values from Billpay_Model_Total_Creditmemo_Surcharge
			$creditMemo->setBillpayRateSurcharge($creditMemo->getBillpayRateSurcharge() - $info->getBillpayRateSurcharge());
			$creditMemo->setBillpayRateFee($creditMemo->getBillpayRateFee() - $info->getBillpayRateFee());
			$creditMemo->setBillpayRateTotalAmount($creditMemo->getBillpayRateTotalAmount() - $info->getBillpayRateTotalAmount());

			$taxClassId = $this->getConfigData('transaction_fee_tax_class', $storeId, Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD);
			$feeTaxAmount = $this->getCalculation()->getTaxAmount($creditMemo->getBillpayRateFee(), $taxClassId, $storeId);
			$creditMemo->setBillpayRateFeeTax($feeTaxAmount);
		}
	}



    /**
     * Get trusted shops buyer protection amount in cent
     *
     * @param Mage_Sales_Model_Order $order
     * @param boolean $addTax
     * @return int
     */
    private function getTrustedShopsAmount($order, $addTax) {
     	$model = Mage::getModel('trustedshops/products');
        $ts_product = $model->getAdminTsProductByQuoteId($order->getQuoteId());

        if ($ts_product) {
            $tsNetAmount = $model->getTsProductAmount($ts_product);
            $amount = $tsNetAmount;
            $tax_data = $model->getTsProductsTaxData(array(
                'key' => 'rate',
                'order' => $order
            ));
            if ($tax_data !== null) {
                $ts_tax_amount = round($amount * $tax_data / 100, 2);
                $amount = $tsNetAmount + $ts_tax_amount;
            }

            return $this->currencyToSmallerUnit($amount);
        }

        return 0;
    }

}