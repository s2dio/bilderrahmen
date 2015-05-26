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
class Billpay_Block_Form_FormRat extends Billpay_Block_FormAbstract {

	protected function _construct() {
		
		if ($this->getApi()->isOneStepCheckout($this->getQuote()->getStoreId())) {
			$this->setTemplate('billpay/form_onestep_rat.phtml');
		} else {
			$this->setTemplate('billpay/form/form_rat.phtml');	
		}
		parent::_construct();
	}

 	public function showBankAccount() {
    	return true;
    }

    public function getRateStep1Html() {
    	$this->getSession()->setRateStep('step1');

		$html = $this->getLayout()
			->createBlock('billpay/form_rat_step1')
			->setTemplate('billpay/form/rat/step1.phtml')
			->toHtml();

		return $html;
    }

    public function isFeeCharged() {
    	return false;
    }

	public function isFeeChargeEnabled() {
		return false;
	}

	public function isPaymentSelected() {
		if (count($this->getParentBlock()->getMethods()) == 1) {
			return true;
		}

		return parent::isPaymentSelected();
	}

	public function getAvailableTerms() {
		$currency = $this->getQuote()->getQuoteCurrencyCode();
		$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code();
		switch ($this->getPrescoreOption()) {
		    case 0:
		        $config = $this->getSession()->getModuleConfig($currency, $country);

		        if (!$config || !array_key_exists('terms', $config)) {
		            $this->getLog()->logError('Terms for hire purchase payment not found in session');
		            throw new Exception('No terms for rate payment for found in session');
		        }
		        break;
		    case 1:
		        $config['terms'] = $this->getSession()->getTermsConfig();
		        break;
		    default: //fallback
        		$config = $this->getSession()->getModuleConfig($currency, $country);

        		if (!$config || !array_key_exists('terms', $config)) {
        			$this->getLog()->logError('Terms for hire purchase payment not found in session');
        			throw new Exception('No terms for rate payment for found in session');
        		}
		        break;
		}

		return $config['terms'];
	}

	public function getBillpayRates() {
		if ($this->getSession()->getBillpayRates() > 0) {
			$this->getSession()->setRateStep('calculation');
		}

		return $this->getSession()->getBillpayRates();
	}

	public function getCalculationHtml() {
		$html = $this->getLayout()
			->createBlock('billpay/form_rat_calculation')
			->setTemplate('billpay/form/rat/calculation.phtml')
			->toHtml();

		return $html;
	}

    /**
     * we use iso3 code in tc
     * @return string
     */
    protected function getBillingAddressCountry()
    {
        return strtolower($this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code());
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
        $countryIso2Code = $this->getBillingAddressCountry();
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
}