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
class Billpay_Block_Form_Rat_Abstract extends Mage_Core_Block_Template {

    /**
     * @return Billpay_Helper_Api
     */
    public function getApi() {
        return Mage::helper('billpay/api');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    protected function getBillingAddressCountry()
    {
        return strtolower($this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code());
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
	 * @return Billpay_Helper_Log
	 */
	public function getLog() {
		return Mage::getSingleton('billpay/log');
	}
	
	/**
	 *
	 * @return string
	 */
	public function getTermsUrl() {
		$mode = trim(Mage::getStoreConfig('billpaysettings/account/transaction_mode', $this->getQuote()->getStoreId()));
		if ($mode == 'live')
			$termsUrl = 'https://www.billpay.de/s/agb/';
		else
			$termsUrl = 'https://www.billpay.de/s/agb-beta/';
	
		$countryIso3Code = $this->getBillingAddressCountry();
		if($countryIso3Code != 'deu')
			$termsUrl .= $countryIso3Code . '/';
	
		$securityKey = trim(Mage::getStoreConfig('billpaysettings/account/security_key', $this->getQuote()->getStoreId()));
		$fileName    = md5(substr(md5((string)$securityKey), 0, 10)) . '.html';
	
		return $termsUrl . $fileName;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getPrivacyUrl() {
		$privacyUrl      = 'https://www.billpay.de/api/ratenkauf/datenschutz';
		$countryIso2Code = strtolower($this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code());
		if ($countryIso2Code != 'de')
			$privacyUrl .= '-' . $countryIso2Code;
	
		return $privacyUrl;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getTransactionCreditTermsOfPayment(){
		$termsOfPaymentUrl = 'https://www.billpay.de/api/ratenkauf/zahlungsbedingungen';
		$countryIso2Code = strtolower($this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code());
		if ($countryIso2Code != 'de')
			$termsOfPaymentUrl .= '-' . $countryIso2Code;
		
		return $termsOfPaymentUrl;
	}

    public function getAcceptTermsHtml()
    {
        $iSepaStatus = $this->getApi()->getConfigData(
            'settings/activate_sepa_handling',
            $this->getQuote()->getStoreId()
        );

        if ($iSepaStatus == 1) {
            $text = $this->getCountrySpecificTranslation('please_confirm_sepa_rate');
            $text = sprintf($text, $this->getTermsUrl(), $this->getTransactionCreditTermsOfPayment(), $this->getPrivacyUrl());
        } else {
            $text = $this->__('billpay_rate_onepage_please_confirm1')
                  . $this->__('billpay_rate_onepage_please_confirm2')
                  . '<a href="javascript:void(0)" onclick="showBillpayRateTermsPopup(\'' . $this->getTermsUrl() . '\')">'
                  . $this->__('billpay_rate_please_confirm2') . '</a>&#44'
                  . '<a href="javascript:void(0)" onclick="showBillpayRateDetailsPopup(\'' . $this->getTransactionCreditTermsOfPayment() . '\')">'
                  . $this->__('billpay_rate_info_overview') . '</a>'
                  . $this->__('billpay_rate_onepage_please_confirm3')
                  . '<a href="javascript:void(0)" onclick="showBillpayRatePrivacyPopup(\'' . $this->getPrivacyUrl() . '\')">'
                  . $this->__('billpay_rate_please_confirm4') . '</a>';
        }

        return $text;
    }

    /**
     * @return string
     */
    public function getAdditionalSepaInformationHtml()
    {
        // just display the information when we enabled sepa
        if ($this->getApi()->getConfigData('settings/activate_sepa_handling', $this->getQuote()->getStoreId()) == 0) {
            return '';
        }

        /** @var Billpay_Block_Info_AdditionalSepaInformation $block */
        $block = $this->getLayout()->createBlock('billpay/info_AdditionalSepaInformation');
        $text = $this->getCountrySpecificTranslation('sepa_additional_information_text_rate');

        $block->setInfoText($text);

        return $block->toHtml();
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
        if ($sCountryCode != 'deu') {
            $sTextToTranslate .= '_' . $sCountryCode;
        }
        $sTranslatedText = $this->__($sTextToTranslate);
        if ($sTranslatedText == $sTextToTranslate) {
            $sTranslatedText = $this->__($sIdentifier);
        }

        return $sTranslatedText;
    }
}