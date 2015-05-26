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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Admin
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Billpay_Model_Session extends Mage_Core_Model_Session_Abstract {

	public function __construct() {
        $this->init('billpay');
    }

    public function setBillpayRates($value) {
    	$this->setData('billpay_rates', $value);
    }

    public function getBillpayRates() {
    	return $this->getData('billpay_rates');
    }

    public function setSelectedSalutation($value) {
    	$this->setData('billpay_selected_salutation', $value);
    }

 	public function getSelectedSalutation() {
    	return $this->getData('billpay_selected_salutation');
    }

 	public function setSelectedDay($value) {
    	$this->setData('billpay_selected_day', $value);
    }

 	public function getSelectedDay() {
    	return $this->getData('billpay_selected_day');
    }

	public function setSelectedMonth($value) {
    	$this->setData('billpay_selected_month', $value);
    }

 	public function getSelectedMonth() {
    	return $this->getData('billpay_selected_month');
    }

	public function setSelectedYear($value) {
    	$this->setData('billpay_selected_year', $value);
    }

 	public function getSelectedYear() {
    	return $this->getData('billpay_selected_year');
    }

	public function setSelectedPhone($value) {
    	$this->setData('billpay_selected_phone', $value);
    }

 	public function getSelectedPhone() {
    	return $this->getData('billpay_selected_phone');
    }

    public function getSelectedDateOfBirth() {
    	return $this->getSelectedYear()
    		. $this->getSelectedMonth()
    		. $this->getSelectedDay();
    }
    public function getMinYear() {
    	return 1910;
    }

    //FIXME: fix max date
	public function getMaxYear() {
    	return 1993;
    }

	public function setRateStep($value) {
    	$this->setData('billpay_rate_step', $value);
    }

 	public function getRateStep() {
    	return $this->getData('billpay_rate_step');
    }

    public function getTransactionId() {
    	return $this->getData('billpay_transaction_id');
    }

    public function setTransactionId($transId) {
    	$this->setData('billpay_transaction_id', $transId);
    }

    public function getModuleConfig($currency, $country = null) {
    	$data = $this->getData('billpay_module_config');
    	if (is_array($data) && array_key_exists($currency, $data)) {
    		$currencyData = $data[$currency];

    		if (is_array($currencyData)) {
	    		if ($country) {
	    			if (array_key_exists($country, $currencyData)) {
	    				return $currencyData[$country];
	    			}
	    		}
	    		else  {
	    			if (array_key_exists('no_country', $currencyData)) {
	    				return $currencyData['no_country'];
	    			}
	    		}
    		}
    	}

    	return null;
    }

    public function setModuleConfig($config, $currency, $country = null) {
    	$data = $this->getData('billpay_module_config');
    	if (!is_array($data)) {
    		$data = array();
    	}

    	if (!array_key_exists($currency, $data)) {
    		$data[$currency] = array();
    	}

    	$currencyData = $data[$currency];

    	if (!$country) {
    		$country = 'no_country';
    	}

   		if (!array_key_exists($country, $currencyData)) {
   			$currencyData[$country] = array();
   		}

   		$data[$currency][$country] = $config;
    	$this->setData('billpay_module_config', $data);
    }

    public function getCalculationResidualAmount() {
    	return $this->getData('billpay_calculation_residual_amount');
    }

    public function setCalculationResidualAmount($residualAmount) {
    	$this->setData('billpay_calculation_residual_amount', $residualAmount);
    }

	public function getBankAccount() {
   		return $this->getData('billpay_bank_account');
   	}

   	public function setBankAccount($bankAccount) {
   		$this->setData('billpay_bank_account', $bankAccount);
   	}

	public function getTermsAccepted() {
   		return $this->getData('billpay_terms_accepted');
   	}

   	public function setTermsAccepted($accepted) {
   		$this->setData('billpay_terms_accepted', $accepted);
   	}

    public function getSurchargeAmount() {
    	return $this->getCalculationValue('surcharge') / 100;
    }

	public function getIntermediateAmount() {
		return $this->getCalculationValue('intermediate') / 100;
    }

    public function getTotalPaymentAmount() {
    	return $this->getCalculationValue('total') / 100;
    }

    public function getInterestRate() {
    	return $this->getCalculationValue('interest') / 100;
    }

    public function getAnualPercentageRate() {
		return $this->getCalculationValue('anual') / 100;
    }

	public function getCalculationBaseAmount() {
		return $this->getCalculationValue('base') / 100;
    }

    public function getTransationFee() {
    	return $this->getCalculationValue('fee') / 100;
    }

	public function getTransationFeeNet() {
    	return $this->getData('billpay_transaction_fee_net');
    }

	public function setTransationFeeNet($value) {
    	return $this->setData('billpay_transaction_fee_net', $value);
    }

	public function getTransationFeeTaxAmount() {
    	return $this->getTransationFee() - $this->getTransationFeeNet();
    }

   	public function getDues() {
		$opt = $this->getCurrentRateOptions();

		if ($opt) {
			$rateOpt = $opt['rateOptions'];
			$term = $this->getBillpayRates();

    		if (array_key_exists($term, $rateOpt)) {
    			return $rateOpt[$term]['dues'];
    		}
		}
    	return false;
    }

    public function setCurrentRateOptions($baseAmount, $rateOptions) {
    	$this->setData('billpay_current_rate_options', array(
    		'baseAmount' => $baseAmount, 'rateOptions' => $rateOptions
    	));
    }

    public function clearCurrentRateOptions() {
    	$this->setData('billpay_current_rate_options', null);
    }

    public function getCurrentRateOptions() {
    	return $this->getData('billpay_current_rate_options');
    }

    public function getCurrentCalculationBaseAmount() {
    	$opt = $this->getCurrentRateOptions();

    	if (!$opt) {
    		return false;
    	}

    	return $opt['baseAmount'];
    }

    public function validateRateOptions($baseAmount) {
    	$opt = $this->getCurrentRateOptions();

    	if (!$opt) {
    		return false;
    	}

    	return $opt['baseAmount'] == $baseAmount;
    }

	private function getCalculationValue($name) {
    	$opt = $this->getCurrentRateOptions();

    	if ($opt) {
    		$rateOpt = $opt['rateOptions'];
    		$term = $this->getBillpayRates();

    		if (array_key_exists($term, $rateOpt)) {
    			return $rateOpt[$term]['calculation'][$name];
    		}
    	}
    	return false;
    }

    public function setCompanyName($value) {
    	$this->setData('billpay_company_name', $value);
    }
    public function getCompanyName() {
    	return $this->getData('billpay_company_name');
    }
    public function setCustomerGroup($value) {
    	$this->setData('billpay_customer_group', $value);
    }
    public function getCustomerGroup() {
    	return $this->getData('billpay_customer_group');
    }
    public function setLegalForm($value) {
    	$this->setData('billpay_legal_form', $value);
    }
    public function getLegalForm() {
    	return $this->getData('billpay_legal_form');
    }
    public function setTaxNumber($value) {
    	$this->setData('billpay_tax_number', $value);
    }
    public function getTaxNumber() {
    	return $this->getData('billpay_tax_number');
    }
    public function setRegisterNumber($value) {
    	$this->setData('billpay_register_number', $value);
    }
    public function getRegisterNumber() {
    	return $this->getData('billpay_register_number');
    }
    public function setHolderName($value) {
    	$this->setData('billpay_holder_name', $value);
    }
    public function getHolderName() {
    	return $this->getData('billpay_holder_name');
    }

    public function setFraudDetectionCheck ($value) {
    	return $this->setData('FraudDetectionCheck', $value);
    }

    public function getFraudDetectionCheck () {
    	return $this->getData('FraudDetectionCheck');
    }

    public function setPrescore ($value) {
        return $this->setData('Prescore', $value);
    }

    public function getPrescore () {
        return $this->getData('Prescore');
    }

    public function setPrescoreCheck ($value) {
        return $this->setData('PrescoreCheck', $value);
    }

    public function getPrescoreCheck () {
        return $this->getData('PrescoreCheck');
    }


    public function setPrescoreResult($value) {
        return $this->setData('PrescoreResult', $value);
    }

    public function getPrescoreResult () {
        return $this->getData('PrescoreResult');
    }

    public function setPrescoreResultPaymentAlowed($value) {
        return $this->setData('PrescoreResultPaymentAlowed', $value);
    }

    public function getPrescoreResultPaymentAlowed () {
        return $this->getData('PrescoreResultPaymentAlowed');
    }


    public function setTermsConfig($value) {
        return $this->setData('TermsConfig', $value);
    }

    public function getTermsConfig () {
        return $this->getData('TermsConfig');
    }

 	public function clearSessionParameters() {
    	$this->setBillpayRates(null);
    	$this->setRateStep(null);
    	$this->setTransactionId(null);
    	$this->setBankAccount(null);
    	$this->setTermsAccepted(null);
    	$this->setTransationFeeNet(null);
    	$this->clearCurrentRateOptions();
    	$this->setCustomerGroup(null);
    	$this->setHolderName(null);
    	$this->setRegisterNumber(null);
    	$this->setTaxNumber(null);
    	$this->setFraudDetectionCheck(null);
    }
}
