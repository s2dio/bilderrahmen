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
class Billpay_Block_FormAbstract extends Mage_Payment_Block_Form {

	protected $_errorMessage = null;
	protected $_chargedFee = 0;
	protected $_chargedFeeB2B = 0;

	/**
	 * @return Billpay_Helper_Log
	 */
	protected function getLog() {
		return Mage::helper('billpay/log');
	}

	/**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /**
     * @return Billpay_Helper_Api
     */
    public function getApi() {
    	return Mage::helper('billpay/api');
    }

	/**
	 * @return Billpay_Model_Session
	 */
	public function getSession() {
		return Mage::getSingleton('billpay/session');
	}

	/**
	 * @return Billpay_Helper_Calculation
	 */
	public function getCalculation() {
		return Mage::helper('billpay/calculation');
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


	public function getErrorMessage() {
		return $this->_errorMessage;
	}

    public function showBankAccount() {
    	return false;
    }

    public function getMinYear() {
    	return 1910;
    }
    //TODO: this have be change....
	public function getMaxYear() {
    	return 1993;
    }

    public function getSelectedDay() {
    	return $this->getSession()->getSelectedDay();
    }

	public function getSelectedMonth() {
    	return $this->getSession()->getSelectedMonth();
    }

	public function getSelectedYear() {
    	return $this->getSession()->getSelectedYear();
    }

    public function getSelectedSalutation() {
    	return $this->getSession()->getSelectedSalutation();
    }

	/**
	 * @return boolean
	 */
	public function isLoggedIn() {
		return Mage::getSingleton('customer/session')->isLoggedIn();
	}

    protected function getBillingAddressCountry()
    {
        return strtolower($this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code());
    }

    public function buildTermsOfServiceUrl() {
      	$language =	$this->getApi()->getCurrentLanguage();
		$termsUrl = 'https://www.billpay.de/api/agb';
		$country = $this->getBillingAddressCountry();
		if ($country != 'de')
			$termsUrl .= '-' . $country;

		$termsUrl .= '?lang=' . $language;
    	return $termsUrl;
    }

    public function buildDataProtectionUrl() {
    	$url = $this->buildTermsOfServiceUrl();
    	$url .= '#datenschutz';
    	return $url;
    }

    public function getAcceptTermsHtml()
    {
        $text = '';
        $dataUrl  = $this->buildDataProtectionUrl();
        $tosUrl   =  $this->buildTermsOfServiceUrl();

        if ($this->getApi()->isBillpayInvoicePayment($this->getMethodCode())) {
            $text = $this->__('please_confirm');

        } elseif ($this->getApi()->isBillpayElvPayment($this->getMethodCode())) {
            $iSepaStatus = $this->getApi()->getConfigData(
                'settings/activate_sepa_handling',
                $this->getQuote()->getStoreId()
            );
            if ($iSepaStatus == 1) {
                $text = $this->getCountrySpecificTranslation('please_confirm_sepa_elv');
            } else {
                $text = $this->__('please_confirm_elv');
            }
        }
        // accept terms html block for transaction credit is handled in Billpay_Block_Form_Rat_Abstract


        $html = sprintf($text, $dataUrl, $tosUrl);
        return $html;
    }

    /**
     * @return string
     */
    public function getAdditionalSepaInformationHtml()
    {
        // just display the information when we enabled sepa
        if ($this->getApi()->getConfigData('settings/activate_sepa_handling', $this->getQuote()->getStoreId()) == 0
            || $this->getApi()->isBillpayElvPayment($this->getMethodCode()) === false
        ) {
            return '';
        }

        /** @var Billpay_Block_Info_AdditionalSepaInformation $block */
        $block = $this->getLayout()->createBlock('billpay/info_AdditionalSepaInformation');
        $text = $this->getCountrySpecificTranslation('sepa_additional_information_text_elv');

        $block->setInfoText($text);

        return $block->toHtml();
    }


	/**
	 * Check whether date of birth is set
	 *
	 * @return boolean
	 */
	public function showDobSelect() {
		$dob = $this->getApi()->getDateOfBirth();
		return empty($dob);
	}

	/**
	 * Check whether gender select has to be shown
	 *
	 * @return boolean
	 */
	public function showGenderSelect() {
		$prefix = $this->getApi()->getSalutation();
		return empty($prefix);
	}

	/**
	 * Get markup code for birthday select control
	 *
	 * @return string
	 */
	public function getDobSelectHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_dobSelect');
		$block->setMethodCode($this->getMethodCode());
		$block->setSelectedDay($this->getSelectedDay());
		$block->setSelectedMonth($this->getSelectedMonth());
		$block->setSelectedYear($this->getSelectedYear());
		$block->setMinYear($this->getMinYear());
		$block->setMaxYear($this->getMaxYear());

		return $block->toHtml();
	}

	/**
	 * Get markup code for gender select control
	 *
	 * @return string
	 */
	public function getGenderSelectHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_genderSelect');
		$block->setMethodCode($this->getMethodCode());
		$block->setSelectedSalutation($this->getSelectedSalutation());

		return $block->toHtml();
	}

	/**
	 * Get markup code for salutation select control
	 *
	 * @return string
	 */
	public function getSalutationSelectHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_salutationSelect');
		$block->setMethodCode($this->getMethodCode());
		$block->setSelectedSalutation($this->getSelectedSalutation());

		return $block->toHtml();
	}

	/**
	 * Get markup code for bank account input control
	 *
	 * @return string
	 */
	public function getBankAccountHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_bankAccount');
		$block->setMethodCode($this->getMethodCode());
		$block->setFullName($this->getApi()->getFullName());

		return $block->toHtml();
	}

	/**
	 * Get markup code for info box that is shown in test and sandbox mode
	 *
	 * @return string
	 */
	public function getTestModeInfoBoxHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_testmodeInfoBox');
		$block->setTransactionMode($this->getApi()->getTransactionMode($this->getQuote()->getStoreId()));
		return $block->toHtml();
	}

	public function getFraudDetectionHtml() {
		return $this->getLayout()
			->createBlock('billpay/control_fraudDetection')
			->toHtml();
	}

	public function showFeeChargeInfoText() {
		if((($this->getAllowedCustomerGroup() == 'b2c' && $this->isFeeCharged()) ||
			($this->getAllowedCustomerGroup() == 'b2b' && $this->isFeeChargedB2B())) &&
			$this->getAllowedCustomerGroup() != 'both') {
				return true;
		}
	}

	public function getFeeChargeInfoValue() {
		if ($this->getAllowedCustomerGroup() != 'both') {
			if ($this->isFeeChargeEnabled() && $this->getAllowedCustomerGroup() == 'b2c') {
				return $this->getChargedFee();
			}
			else if ($this->isFeeChargeB2BEnabled() && $this->getAllowedCustomerGroup() == 'b2b') {
				return $this->getChargedFeeB2B();
			}
		}
	}

    public function getSelectedCompanyName() {
    	if ($this->getSession()->getCompanyName()) {
    		return $this->getSession()->getCompanyName();
    	}
    	return $this->getQuote()->getBillingAddress()->getCompany();
    }
    
    /**
     * 
     * @return string
     */
    public function getOneStepCheckoutSelectedCompanyName() {
    	return $this->getQuote()->getBillingAddress()->getCompany();
    }
    
    /**
     * 
     * @param $genderWidget Object of: Mage_Customer_Block_Widget_Gender
     * @return boolean
     */
    public function showOneStepCheckoutGenderSelect($genderWidget){
    	// present and enable
    	if (is_object($genderWidget) && $genderWidget->isEnabled()){
    		if(!$this->isLoggedIn()) {
    			return false; // date field enable and not login
    		}
    		else {
    			// when login and not address present
    			$customerHasAddresses = $this->helper('customer')->customerHasAddresses();
    			if (!$customerHasAddresses)
    				return false; 
    			
    			if($this->getApi()->getSalutation()) {
    				return false; // date field with date
    			}
    		}
    	}
    	
    	return true;
    }

    /**
     * 
     * @param $dobWidget Object of: Mage_Customer_Block_Widget_Dob
     * @return boolean
     */
    public function showOneStepCheckoutDobSelect($dobWidget) {
    	// present and enable
	    if (is_object($dobWidget) && $dobWidget->isEnabled()){
	    	if(!$this->isLoggedIn()) {
	    		return false; // date field enable and not login
	    	}
	    	else {
	    		$customerHasAddresses = $this->helper('customer')->customerHasAddresses();
	    		if (!$customerHasAddresses)
	    			return false;
	    		
		    	if($this->getApi()->getDateOfBirth()) {
					return false; // date field with date
		    	}
			}
		}
		
		return true;
    }
  
	public function getSelectedLegalForm() {
    	return $this->getSession()->getLegalForm();
    }

	public function getTaxNumber() {
		if ($this->getSession()->getTaxNumber()) {
			return $this->getSession()->getTaxNumber();
		}
		else if ($this->isLoggedIn()) {
			$taxNumber = trim($this->getQuote()->getCustomer()->getTaxvat());
			return $taxNumber;
		}
		else {
			$taxNumber = trim($this->getQuote()->getCustomerTaxvat());
			return $taxNumber;
		}
	}

	public function getRegisterNumber() {
		return $this->getSession()->getRegisterNumber();
	}

	public function getHolderName() {
		return $this->getSession()->getHolderName();
	}

	public function getContactPerson() {
		$fullName = $this->getApi()->getFullName();

		$salutation = $this->getSession()->getSelectedSalutation();
		if (!$salutation) {
			$salutation = $this->getApi()->getSalutation();
		}

		if ($salutation) {
			$fullName = trim($salutation).' '.trim($fullName);
		}

		return $fullName;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getOneStepCheckoutContactPerson() {
		return $this->getApi()->getFullName();
	}

	
	/**
	 * Get markup code for the main form content
	 *
	 * @return string
	 */
	public function getFormContentHtml() {
        /** @var Billpay_Block_FormContent $block */
		$block = $this->getLayout()->createBlock('billpay/formContent');
		$block->setShowGenderSelect($this->showGenderSelect());
		$block->setShowDobSelect($this->showDobSelect());
		$block->setShowBankAccount($this->showBankAccount());
		$block->setGenderSelectHtml($this->getGenderSelectHtml());
		$block->setSalutationSelectHtml($this->getSalutationSelectHtml());
		$block->setDobSelectHtml($this->getDobSelectHtml());
		$block->setBankAccountHtml($this->getBankAccountHtml());
		$block->setTestModeInfoBoxHtml($this->getTestModeInfoBoxHtml());
		$block->setFeeChargeEnabled($this->isFeeChargeEnabled());
		$block->setFeeChargeB2BEnabled($this->isFeeChargeB2BEnabled());
		$block->setFeeCharged($this->isFeeCharged());
		$block->setFeeChargedB2B($this->isFeeChargedB2B());
		$block->setChargedFee($this->getChargedFee());
		$block->setChargedFeeB2B($this->getChargedFeeB2B());
		$block->setMethodCode($this->getMethodCode());
		$block->setAllowedCustomerGroup($this->getAllowedCustomerGroup());
		$block->setB2BEnabled($this->getB2BEnabled());
		$block->setLegalFormSelectHtml($this->getLegalFormSelectHtml());
		$block->setSelectedCustomerGroup($this->getSelectedCustomerGroup());
		$block->setSelectedCompanyName($this->getSelectedCompanyName());
		$block->setCustomerGroupVisible($this->getCustomerGroupVisible(0));
		$block->setCustomerGroupChecked($this->getCustomerGroupChecked(1));
		$block->setAcceptTermsHtml($this->getAcceptTermsHtml());
        $block->setAdditionalSepaInformationHtml($this->getAdditionalSepaInformationHtml());
		$block->setShowFeeChargeInfoText($this->showFeeChargeInfoText());
		$block->setFeeChargeInfoValue($this->getFeeChargeInfoValue());
		$block->setTaxNumber($this->getTaxNumber());
		$block->setRegisterNumber($this->getRegisterNumber());
		$block->setHolderName($this->getHolderName());
		$block->setContactPerson($this->getContactPerson());
		$block->setFraudDetectionHtml($this->getFraudDetectionHtml());

		return $block->toHtml();
	}


	/**
	 * Check billpay api if customer data is valid
	 *
	 * @return boolean
	 */
	public function isValid() {
		try {
			$this->getApi()->sendValidationRequest($this->getMethodCode(), true);
		}
		catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}

		return true;
	}

	/**
	 * Check if payment method is already set
	 *
	 * @return boolean
	 */
	public function isPaymentSelected() {
		$payment = $this->getQuote()->getPayment()->getMethod();
		$shippingComplete = $this->getCheckout()->getData('steps/shipping_method/complete');
		return $payment === $this->getMethodCode() && $shippingComplete;
	}

	/**
	 * Check whether a fee for this payment has to be charged
	 *
	 * @return boolean
	 */
	public function isFeeCharged() {
		if ($this->isFeeChargeEnabled()) {
			$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code();
			$fee = $this->getCalculation()->getChargedFee($this->getMethodCode(), $this->getQuote()->getStoreId(), $this->getQuote()->getGrandTotal(), $country);

			if (!$fee) {
				return false;
			}
			else if ($this->getApi()->getConfigData('fee/display_incl_tax_frontend', $this->getQuote()->getStoreId())) {
				$this->_chargedFee = $fee[1];
	    	}
	    	else {
				$this->_chargedFee = $fee[0];
	    	}
			return $this->_chargedFee > 0;
		}
		return false;
	}

	/**
	 * Check whether a fee for this payment has to be charged
	 *
	 * @return boolean
	 */
	public function isFeeChargedB2B() {
		if ($this->isFeeChargeB2BEnabled()) {
			$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code();
			$fee = $this->getCalculation()->getChargedFee($this->getMethodCode(), $this->getQuote()->getStoreId(), $this->getQuote()->getGrandTotal(), $country, true);
			if (!$fee) {
				return false;
			}
			else if ($this->getApi()->getConfigData('fee/display_incl_tax_frontend', $this->getQuote()->getStoreId())) {
    		$this->_chargedFeeB2B = $fee[1];
	    	}
	    	else {
				$this->_chargedFeeB2B = $fee[0];
	    	}
			return $this->_chargedFeeB2B > 0;
		}
		return false;
	}

	/**
	 * Get the fee to be charged
	 *
	 * @return float
	 */
	public function getChargedFee() {
		return $this->getQuote()->getStore()->convertPrice($this->_chargedFee, true, true);
	}

	/**
	 * Get the fee to be charged
	 *
	 * @return float
	 */
	public function getChargedFeeB2B() {
		return $this->getQuote()->getStore()->convertPrice($this->_chargedFeeB2B, true, true);
	}

	/**
	 * Check whether the fee charge feature is enabled
	 *
	 * @return boolean
	 */
	public function isFeeChargeEnabled() {
		return $this->getCalculation()->isFeeChargeEnabled($this->getMethodCode(), $this->getQuote()->getStoreId(), false);
	}

	/**
	 * Check whether the fee charge feature is enabled
	 *
	 * @return boolean
	 */
	public function isFeeChargeB2BEnabled() {
		return $this->getCalculation()->isFeeChargeEnabled($this->getMethodCode(), $this->getQuote()->getStoreId(), true);
	}

    public function isLightCheckout() {
    	$checkoutType = $this->getApi()
    		->getConfigData(
    			'settings/checkout_type',
    			$this->getQuote()->getStoreId()
    		);

    	return $checkoutType == 'lightcheckout';
    }


    public function getLeftMargin() {
    	$checkoutType = $this->getApi()->getConfigData('settings/checkout_type', $this->getQuote()->getStoreId());

		if ($checkoutType == 'lightcheckout') {
	    	return 15;
		}
		else {
			return 0;
		}
    }


   	/**
	 * Get markup code for company name input control
	 *
	 * @param unknown_type $selectCssStyle
	 * @return string
	 */
    public function getLegalFormSelectHtml($selectCssStyle = 'width:250px; margin-right:15px;') {
    	$block = $this->getLayout()->createBlock('billpay/control_legalFormSelect');
		$block->setMethodCode($this->getMethodCode());
		$block->setSelectedLegalForm($this->getSelectedLegalForm());
		$block->setLegalFormSelectCssStyle($selectCssStyle);
		
		return $block->toHtml();
    }
    
    public function getAllowedCustomerGroup() {
        $t = $this->getApi()->getConfigData('allowed_customer_group',
									    		$this->getQuote()->getStoreId(),
									    		$this->getMethodCode());
        return $t;
    }

    public function getB2BEnabled() {
    	if($this->getAllowedCustomerGroup() == 'b2b' || $this->getAllowedCustomerGroup() == 'both') {
  			$currency = $this->getQuote()->getQuoteCurrencyCode();
  			$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code();
			$params = $this->getSession()->getModuleConfig($currency, $country);

			return $this->getCalculation()->showPaymentMethod($this->getMethodCode(), $this->getQuote(), $params, true);
    	}
		return false;
    }

     /**
	 * Get preselected customer group. returns true if b2b or false if b2c
	 *
	 * @return boolean
	 */
    public function getSelectedCustomerGroup() {
        if(!$this->getApi()->isBillpayInvoicePayment($this->getMethodCode()) || $this->getAllowedCustomerGroup()=='b2c') {
			return false;
		}
    	else if($this->getAllowedCustomerGroup()=='b2b') {
			return true;
		}
		else if ($this->getSession()->getCustomerGroup() == 'b') {
			return true;
		}
    	else if ($this->getSession()->getCustomerGroup() == 'p') {
			return false;
		}
    	else {
    		if ($this->isLoggedIn()) {
				$taxNumber = trim($this->getQuote()->getCustomer()->getTaxvat());
			} else {
				$taxNumber = trim($this->getQuote()->getCustomerTaxvat());
			}

			$company = $this->getQuote()->getBillingAddress()->getCompany();

    		if ($taxNumber || $company || in_array($this->getQuote()->getCustomerGroupId(), array(2, 3))) {
    			return true;
    		}
    	}
    	return false;
    }

    /**
     * @param $sIdentifier
     *
     * @return string
     */
    protected function getCountrySpecificTranslation($sIdentifier)
    {
        $sTextToTranslate = $sIdentifier;
        $sCountryCode = $this->getBillingAddressCountry();
        if ($sCountryCode != 'de') {
            $sTextToTranslate .= '_' . $sCountryCode;
        }
        $sTranslatedText = $this->__($sTextToTranslate);
        if ($sTranslatedText == $sTextToTranslate) {
            $sTranslatedText = $this->__($sIdentifier);
        }

        return $sTranslatedText;
    }
}