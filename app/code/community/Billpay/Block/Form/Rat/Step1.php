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
class Billpay_Block_Form_Rat_Step1 extends Billpay_Block_Form_Rat_Abstract {
	
	protected function _construct() {
		$this->setTemplate('billpay/form/rat/step1.phtml');
		parent::_construct();
	}
	
	/**
	 * @return Billpay_Model_Session
	 */
	private function getSession() {
		return Mage::getSingleton('billpay/session');
	}
	
	public function getAvailableTerms() {
		$currency = $this->getQuote()->getQuoteCurrencyCode();
		$country = $this->getQuote()->getBillingAddress()->getCountryModel()->getIso3Code();
		$config = $this->getSession()->getModuleConfig($currency, $country);
		
		if (!$config || !array_key_exists('terms', $config)) {
			$this->getLog()->logError('Terms for hire purchase payment not found in session');
			throw new Exception('No terms for rate payment for found in session');
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
	
}