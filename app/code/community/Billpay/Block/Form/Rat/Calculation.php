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
class Billpay_Block_Form_Rat_Calculation extends Billpay_Block_Form_Rat_Abstract {

	private $_validationError = null;
	private $_calculationBaseAmount = null;
	private $_cartTotalGross = null;
	private $_calculationResidualAmount = null;
	private $_rateNumber;
	private $_interestRate;
	private $_anualPercentageRate;
	private $_surchargeAmount;
	private $_intermediateAmount;
	private $_totalPaymentAmount;
	private $_transactionFee;
	private $_dues;
	private $_totalsCollected = false;

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

	public function getSelectedRateNumber() {
		return $this->_rateNumber;
	}

	public function getHasValidationError() {
		return !is_null($this->_validationError);
	}

	public function getValidationError() {
		return $this->_validationError;
	}

	public function getInterestRate() {
		return $this->_interestRate;
	}

	public function getAnualPercentageRate($format = true) {
		$s = Zend_Locale_Format::toNumber($this->_anualPercentageRate, array('precision' => 2));
		if ($format) {
			return $s . '%';
		}
		else {
			return $s;
		}
	}

	protected function _construct() {
		$this->_rateNumber = trim($this->getSession()->getBillpayRates());

		if (!$this->getCalculation()->isIntVal($this->_rateNumber) || $this->_rateNumber <= 0) {
			$this->_validationError = Mage::helper('sales')->__('billpay_invalid_ratecount');
		}
		else {
			$baseAmount = $this->getCalculationBaseAmount(false);
			$baseAmount = $this->getApi()->currencyToSmallerUnit($baseAmount);

			if (!$this->getSession()->validateRateOptions($baseAmount)) {
				$options = $this->performApiCall();

				if ($options) {
					$this->getSession()->setCurrentRateOptions($baseAmount, $options);

					// Calculate fee net amount and store it in session
	    			$feeAmountNet = $this->calculateFeeAmountNet();
	    			$this->getSession()->setTransationFeeNet($feeAmountNet);
				}
			}

			$this->_interestRate 		= $this->getSession()->getInterestRate();
			$this->_anualPercentageRate = $this->getSession()->getAnualPercentageRate();
			$this->_surchargeAmount		= $this->getSession()->getSurchargeAmount();
			$this->_intermediateAmount	= $this->getSession()->getIntermediateAmount();
			$this->_totalPaymentAmount	= $this->getSession()->getTotalPaymentAmount();
			$this->_dues				= $this->getSession()->getDues();
			$this->_transactionFee		= $this->getSession()->getTransationFee();


		}
		$this->setTemplate('billpay/form/rat/calculation.phtml');
		parent::_construct();
	}

	private function performApiCall() {
		try {
			$baseAmount 		= $this->getCalculationBaseAmount(false);
			$cartTotalGross 	= $this->getCartTotalGross();

			$result = $this->getApi()->sendCalculateRatesRequest($baseAmount, $cartTotalGross);
			return $result['options'];
		}
		catch (Exception $e) {
			$this->_validationError = $e->getMessage();
			return false;
		}
	}

	private function calculateFeeAmountNet() {
		$taxClassId = $this->getApi()->getConfigData('transaction_fee_tax_class', $this->getQuote()->getStoreId(), Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD);
        $feeTaxAmount = $this->getCalculation()->getTaxAmount($this->getSession()->getTransationFee(), $taxClassId, $this->getQuote()->getStoreId());
	    $feeAmountNet = $this->getSession()->getTransationFee() - $feeTaxAmount;
		return $feeAmountNet;
	}

	public function getCalculationBaseAmount($format = true) {
		$quote = $this->getQuote();
		if (!$this->_calculationBaseAmount) {

			$this->_collectTotals($quote);

			$this->_calculationBaseAmount = $this->getCalculation()
				->getCalculationBaseAmount($quote);
		}

		if ($format) {
			return $quote->getStore()
				->formatPrice($this->_calculationBaseAmount, false);
		}
		else {
			return $this->_calculationBaseAmount;
		}
	}

	public function getCartTotalGross() {
		$quote = $this->getQuote();
		if (!$this->_cartTotalGross) {

			$this->_collectTotals($quote);

			$this->_cartTotalGross = $quote
				->getShippingAddress()
				->getGrandTotal();
		}
		return $this->_cartTotalGross;
	}


	public function getCalculationResidualAmount() {
		$quote = $this->getQuote();
		if (!$this->_calculationResidualAmount) {

			$this->_collectTotals($quote);

			$res = $this->getCalculation()->getCalculationResidualAmount($quote);

			$this->_calculationResidualAmount = $quote->getStore()
				->formatPrice($res, false);

			$this->getSession()->setCalculationResidualAmount($this->_calculationResidualAmount);
		}
		return $this->_calculationResidualAmount;
	}

	public function getTotalSurcharge($format = true) {
		if ($format) {
			return $this->getQuote()->getStore()
				->formatPrice($this->_surchargeAmount, false);
		}
		else {
			return $this->_surchargeAmount;
		}
	}

	public function getIntermediateAmount($format = true) {
		if ($format) {
			return $this->getQuote()->getStore()
				->formatPrice($this->_intermediateAmount, false);
		}
		else {
			return $this->_intermediateAmount;
		}
	}

	public function getTotalPaymentAmount($format = true) {
		if ($format) {
			return $this->getQuote()->getStore()
				->formatPrice($this->_totalPaymentAmount, true);
		}
		else {
			return $this->_totalPaymentAmount;
		}
	}

	public function getRatePrice() {
		foreach ($this->_dues as $due) {
			if ($due['type'] === 'following') {
				return $this->getQuote()->getStore()
				->formatPrice($due['value'] / 100);
			}
		}
		return 0;
	}

	public function getFirstRatePrice() {
		foreach ($this->_dues as $due) {
			if ($due['type'] === 'first') {
				return $this->getQuote()->getStore()
				->formatPrice($due['value'] / 100);
			}
		}
		return 0;
	}

	public function getFormula() {
		return Mage::helper('billpay/calculation')
			->getRateSurchargeFormula(
				$this->getCalculationBaseAmount(true),
				$this->getInterestRate(),
				$this->getSelectedRateNumber()
			);
	}

	public function getTransactionFee() {
		return $this->getQuote()->getStore()
				->formatPrice($this->_transactionFee, false);
	}

	private function _collectTotals($quote) {
		if ($this->_totalsCollected == false) {

			// we need to do this temporarily in order to remove billpay fees
			$ratePayment = Mage::getSingleton('billpay/rat');
			$quote->getPayment()->setMethod($ratePayment->getCode());
			$quote->collectTotals();
			$this->_totalsCollected = true;
		}
	}
	
	public function getShowDobSelect() {
		$dob = $this->getApi()->getDateOfBirth();
		return empty($dob);
	}
	
	/**
	 * Check whether gender select has to be shown
	 * 
	 * @return boolean
	 */
	public function getShowGenderSelect() {
		$prefix = $this->getApi()->getSalutation();
		return empty($prefix);
	}
	
	/**
	 * Check whether phone input text field has to be shown
	 * 
	 * @return boolean
	 */	
	public function getShowPhoneInputField() {
		$phone = $this->getApi()->getPhone();
		return empty($phone);
	}
	
	public function getMethodCode() {
		return Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD;
	}
	
	/**
	 * Get markup code for gender select control
	 * 
	 * @return string
	 */
	public function getGenderSelectHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_genderSelect');
		$block->setMethodCode(Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD);
		$block->setSelectedSalutation($this->getSession()->getSelectedSalutation());
		
		return $block->toHtml();
	}
	
	/**
	 * Get markup code for birthday select control
	 * 
	 * @return string
	 */
	public function getDobSelectHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_dobSelect');
		$block->setMethodCode(Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD);
		$block->setSelectedDay($this->getSession()->getSelectedDay());
		$block->setSelectedMonth($this->getSession()->getSelectedMonth());
		$block->setSelectedYear($this->getSession()->getSelectedYear());
		$block->setMinYear($this->getSession()->getMinYear());
		$block->setMaxYear($this->getSession()->getMaxYear());
		
		return $block->toHtml();
	}
	
	/**
	 * Get markup code for bank account input control
	 * 
	 * @return string
	 */
	public function getBankAccountHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_bankAccount');
		$block->setMethodCode(Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD);
		$block->setFullName($this->getApi()->getFullName());
				
		return $block->toHtml();
	}
	
	public function getPhoneInputHtml() {
		$block = $this->getLayout()->createBlock('billpay/control_phoneInput');
		$block->setMethodCode(Billpay_Helper_Api::$BILLPAY_PAYMENT_RAT_METHOD);
		$block->setSelectedPhone($this->getSession()->getSelectedPhone());
		
		return $block->toHtml();
	}
	

}