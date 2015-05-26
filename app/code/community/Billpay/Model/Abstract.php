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
class Billpay_Model_Abstract extends Mage_Payment_Model_Method_Abstract {

	protected $_code			= 'billpay_rec';
	protected $_formBlockType	= 'billpay/form';
	protected $_infoBlockType	= 'billpay/info';
	protected $_paymentMethod	= 'rec';

	protected $_isGateway				= false;
	protected $_canAuthorize			= true;
	protected $_canCapture				= true;
	protected $_canCapturePartial		= true;
	protected $_canRefund				= false;
	protected $_canVoid					= false;
	protected $_canUseInternal			= true;
	protected $_canUseCheckout			= true;
	protected $_canUseForMultishipping	= false;

 	/** Get checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

   	/** Get quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Get billpay api
     *
     * @return Billpay_Helper_Api
     */
    public function getApi() {
    	return Mage::helper('billpay/api');
    }

  	/** Get billpay logger
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
    public function getCalculation() {
    	return Mage::helper('billpay/calculation');
    }

	/**
	 * @return Billpay_Model_Session
	 */
	protected function getSession() {
		return Mage::getSingleton('billpay/session');
	}

	/**
	 * @return prescore enable option
	 */
	protected function getPrescoreOption() {
		return trim(Mage::getStoreConfig(
				'billpaysettings/account/prescore_option',
				$this->getQuote()->getStoreId())
		);

	}

	/**
	 * Get the title for this payment method
	 *
	 * @return string
	 */
    public function getTitle() {
    	//$title = parent::getTitle();
    	$title = Mage::helper('payment')->__($this->_code);

    	if ($this->getApi()->isFeeChargeEnabled($this->_code, false)) {
    		$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code();
    		$fee = $this->getCalculation()->getChargedFee($this->getCode(), $this->getQuote()->getStoreId(), $this->getQuote()->getGrandTotal(), $country);

    		return $this->buildTitleWithFee($fee);
    	}

    	return $title;
    }

	/**
	 * This method is called by magento to see if the payment method should be displayed in checkout
	 *
	 * @return boolean
	 */
	public function canUseCheckout() {
	    $shippment_completed = $this->getCheckout()->getStepData('shipping_method','complete');
	    $code = $this->getCode();
	    $codes_translation_array = array(
	            'billpay_rat' => 'TRANSACTION_CREDIT_B2C',
	            'billpay_elv' => 'DIRECT_DEBIT_B2C',
	            'billpay_rec' => 'INVOICE_B2C'
	    );
		switch ($this->getPrescoreOption()) {
			case 0:
                $this->getSession()->setPrescore(0);
				return $this->_moduleConfig();
			break;
			case 1:
			    $this->getSession()->setPrescore(1);
			    $this->getSession()->setFraudDetectionCheck(true);
				if($shippment_completed == true){

				     if($this->getSession()->getPrescoreCheck() == false)
				     {
				         $this->getSession()->setPrescoreCheck(true);
				         $this->_prescore();
				     }

				     //check part
				     if($this->getSession()->getPrescoreResult() !=  false)
				     {
				         if(in_array($codes_translation_array[$code], $this->getSession()->getPrescoreResultPaymentAlowed()))
				         {
				             return true;
				         }
				         else
				         {
				             return false;
				         }
				     }
				     else
				     {
				         $this->_canUseCheckout = false;
				         return false;
				     }

				 }
				 else
				 {
				     $this->getSession()->setPrescoreCheck(false);
				     $this->getSession()->setPrescoreResult(false);
				     //$this->getSession()->setFraudDetectionCheck(false);
				     return false;
				 }
			break;
			default: //fallback
				return $this->_moduleConfig();
			break;
		}
	}

	protected function _moduleConfig ()
	{
		$total = $this->getApi()->currencyToSmallerUnit($this->getQuote()->getGrandTotal());
		$storeId = $this->getQuote()->getStoreId();
		$tmp_shpi = Mage::getSingleton('checkout/session')->getQuote();

		if ($this->hasVirtualItems()) {
			$this->getLog()->logDebug('Virtual items found. Hide payment methods');
			return false;
		}

		try {
			$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code();
			$currency = $this->getQuote()->getQuoteCurrencyCode();
			if (!$country) {
				return false;
			}

			$params = $this->getApi()->getModuleConfig($this->getCode());
			if (!$params || (array_key_exists('is_active', $params) && !$params['is_active'])) {
				return false;
			}

			if($this->getCode() == 'billpay_rec' && $this->getApi()->getConfigData('allowed_customer_group', $this->getQuote()->getStoreId(), $this->getMethodCode()) == 'b2b') {
				$staticLimit = $params['static_limit_' . $this->getCode().'_b2b'];
				$serverSideMinOrderValue = $params['min_' . $this->getCode().'_b2b'];
				$allowedKey = 'is_allowed_' . $this->getCode().'_b2b';
			}
			else {
				$staticLimit 				= $params['static_limit_' . $this->getCode()];
				$serverSideMinOrderValue	= $params['min_' . $this->getCode()];
				$allowedKey 				= 'is_allowed_' . $this->getCode();
			}

			if (array_key_exists('is_active', $params) && !$params['is_active']) {
				$this->getLog()->logDebug("Billpay payment methods have been disabled (country: $country, currency: $currency)");
				return false;
			}

			if (array_key_exists($allowedKey, $params) && !$params[$allowedKey]) {
				$this->getLog()->logDebug("Billpay payment method " . $this->getCode() . " has been disabled (country: $country, currency: $currency)");
				return false;
			}

			if ($total > $staticLimit) {
				$this->_canUseCheckout = false;
				$this->getLog()->logDebug('Static limit exceeded (' . $total . ' > ' . $staticLimit . ')');
			}

			if ($total < $serverSideMinOrderValue) {
				$this->_canUseCheckout = false;
				$this->getLog()->logDebug('Server side minimum value deceeded (' . $total . ' < ' . $serverSideMinOrderValue . ')');
			}

			if (Mage::getSingleton('checkout/session')->getHideBillpayPaymentMethod()) {
				$this->_canUseCheckout = false;
				$this->getLog()->logDebug('Credit check failed previously. Hide billpay payment form');
			}
		}
		catch (Exception $e) {
			$this->getLog()->logException($e);
			$this->_canUseCheckout = false;
		}

		return $this->_canUseCheckout;
	}

	protected function _prescore()	{
		//TODO: CUSTOMERGROUP - Curently hardcoded for personal
		//TODO: Exception
		//TODO: USE of global $var
		//TODO: make actuly a check

		$this->getSession()->setCustomerGroup('p');

		$result = $this->getApi()->sendPrescoreRequest();
		return $result;

	}

	public function assignData($data) {
		if (isset($data[$this->getPostIdentifier('day')]) &&
			isset($data[$this->getPostIdentifier('month')]) &&
			isset($data[$this->getPostIdentifier('year')])) {

			$this->getSession()->setSelectedDay($data[$this->getPostIdentifier('day')]);
			$this->getSession()->setSelectedMonth($data[$this->getPostIdentifier('month')]);
			$this->getSession()->setSelectedYear($data[$this->getPostIdentifier('year')]);
		}
		
		$allowedGroup = $this->getApi()->getConfigData('allowed_customer_group', $this->getQuote()->getStoreId(), $this->_code);
		
		if ($data[$this->getPostIdentifier('customer_group')] == 'b2c' || $allowedGroup == 'b2c' ) {
			if (isset($data[$this->getPostIdentifier('gender')]) && $data[$this->getPostIdentifier('gender')]!='')
				$this->getSession()->setSelectedSalutation($data[$this->getPostIdentifier('gender')]);
		} 
		elseif ($data[$this->getPostIdentifier('customer_group')] == 'b2b' || $allowedGroup == 'b2b') {
			if (isset($data[$this->getPostIdentifier('salutation')]) && $data[$this->getPostIdentifier('salutation')]!='')
				$this->getSession()->setSelectedSalutation($data[$this->getPostIdentifier('salutation')]);
		}
		else { // TC & DD
			if (isset($data[$this->getPostIdentifier('gender')]) && $data[$this->getPostIdentifier('gender')]!='')
				$this->getSession()->setSelectedSalutation($data[$this->getPostIdentifier('gender')]);
		}

		$termsAccepted = isset($data[$this->getPostIdentifier('tcaccepted')]);
		$this->getSession()->setTermsAccepted($termsAccepted);
		
		if($allowedGroup == 'b2b' || $data[$this->getPostIdentifier('customer_group')] == 'b2b') {
			$this->getSession()->setCustomerGroup('b');
			$this->getSession()->setCompanyName($data[$this->getPostIdentifier('company_name')]);
			$this->getSession()->setLegalForm($data[$this->getPostIdentifier('legal_form')]);
			$this->getSession()->setTaxNumber($data[$this->getPostIdentifier('tax_number')]);
			$this->getSession()->setRegisterNumber($data[$this->getPostIdentifier('register_number')]);
			$this->getSession()->setHolderName($data[$this->getPostIdentifier('holder_name')]);
		}
		else{
			$this->getSession()->setCustomerGroup('p');
		}
    }


	protected function buildTitleWithFee($fee) {
		//$title = parent::getTitle();
		$title = Mage::helper('payment')->__($this->_code);
		if ($fee) {
			if ($this->getApi()->getConfigData('fee/display_incl_tax_frontend', $this->getQuote()->getStoreId())) {
				$displayFee = $fee[1];
			}
			else {
				$displayFee = $fee[0];
			}

			// Call of method 'formatCurrency' implititly converts price from base to current currency
			return $title . $this->getApi()->__('billpay_title_fee_text1') . Mage::helper('core')->formatCurrency($displayFee, false) . $this->getApi()->__('billpay_title_fee_text2');
			//return $title . $this->getApi()->__('billpay_title_fee_text1') . $this->getQuote()->getStore()->formatPrice($displayFee, false);
		}
		else {
			return $title;
		}
	}

    protected function getPostIdentifier($s) {
    	return $this->_code . '_' . $s;
    }

    private function hasVirtualItems() {
    	if ($this->getQuote()->isVirtual() || $this->getQuote()->getItemVirtualQty() > 0) {
			return true;
		}
    }
}