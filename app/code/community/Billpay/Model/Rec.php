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
class Billpay_Model_Rec extends Billpay_Model_Abstract {
	protected $_code			= 'billpay_rec';
	protected $_formBlockType	= 'billpay/form';
	protected $_infoBlockType	= 'billpay/info';
	protected $_paymentMethod	= 'rec';

	protected $_isGateway				= false;
	protected $_canAuthorize			= false;
	protected $_canCapture				= false;
	protected $_canCapturePartial		= true;
	protected $_canRefund				= false;
	protected $_canVoid					= false;
	protected $_canUseInternal			= true;
	protected $_canUseCheckout			= true;
	protected $_canUseForMultishipping	= false;

	protected function getModuleConfigAllowedKey() {
		return 'is_invoice_allowed';
	}

	/**
	 * Get the title for this payment method
	 *
	 * @return string
	 */
    public function getTitle() {
		$allowedCustomerGroup = $this->getConfigData('allowed_customer_group', $this->getQuote()->getStoreId());

		$country 	= $this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code();
		$currency 	= $this->getQuote()->getQuoteCurrencyCode();
		$countryISO2 	= $this->getQuote()->getBillingAddress()->getCountryModel()->getIso2Code();

		if($allowedCustomerGroup != 'both') {
			$isB2B	 		= $allowedCustomerGroup == 'b2b';

			$fee = $this->getCalculation()->getChargedFee($this->getCode(), $this->getQuote()->getStoreId(), $this->getQuote()->getGrandTotal(), $countryISO2, $isB2B);
			return $this->buildTitleWithFee($fee);
		}
		else {
			$moduleConfig = $this->getSession()->getModuleConfig($currency, $country);

			if ($moduleConfig) {
				$show 		= $this->getCalculation()->showPaymentMethod($this->getCode(), $this->getQuote(), $moduleConfig, false);
				$showB2B 	= $this->getCalculation()->showPaymentMethod($this->getCode(), $this->getQuote(), $moduleConfig, true);

				$fee 	= $this->getCalculation()->getChargedFee($this->getCode(), $this->getQuote()->getStoreId(), $this->getQuote()->getGrandTotal(), $countryISO2, false);
				$feeB2B = $this->getCalculation()->getChargedFee($this->getCode(), $this->getQuote()->getStoreId(), $this->getQuote()->getGrandTotal(), $countryISO2, true);

				if ($show && !$showB2B && $fee) {
					return $this->buildTitleWithFee($fee);
				}
				else if (!$show && $showB2B && $feeB2B) {
					return $this->buildTitleWithFee($feeB2B);
				}
			}

			return $this->buildTitleWithFee(false);
		}
	}
}