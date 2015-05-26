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
class Billpay_Model_Rat extends Billpay_Model_Abstract {
	protected $_code			= 'billpay_rat';
	protected $_formBlockType	= 'billpay/form_formRat';
	protected $_infoBlockType	= 'billpay/info_infoRat';
	protected $_paymentMethod	= 'rat';

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
		return 'is_rate_allowed';
	}

 	public function getTitle() {
        return Mage::helper('payment')->__($this->_code);
    }

    public function assignData($data) {
    	    $info = $this->getInfoInstance();
	    	$dues = $this->getCalculation()->getSerializedDues($this->getSession()->getDues());
	        $info->setBillpayRateSurcharge($this->getSession()->getSurchargeAmount());
	        $info->setBillpayRateCount($this->getSession()->getBillpayRates());
	        $info->setBillpayRateTotalAmount($this->getSession()->getTotalPaymentAmount());
	        $info->setBillpayRateDues($dues);
	        $info->setBillpayRateInterestRate($this->getSession()->getInterestRate());
	        $info->setBillpayRateAnualRate($this->getSession()->getAnualPercentageRate());
	        $info->setBillpayRateBaseAmount($this->getSession()->getCalculationBaseAmount());
	        $info->setBillpayRateResidualAmount($this->getSession()->getCalculationResidualAmount());
	        $info->setBillpayRateFee($this->getSession()->getTransationFee());
	        $info->setBillpayRateFeeNet($this->getSession()->getTransationFeeNet());
	        $info->setBillpayRateFeeTax($this->getSession()->getTransationFeeTaxAmount());

	    	if (isset($data[$this->getPostIdentifier('account_holder')]) &&
				isset($data[$this->getPostIdentifier('account_number')]) &&
				isset($data[$this->getPostIdentifier('sort_code')])) {

				$bankAccount = array();
				$bankAccount['accountholder'] 	= $data[$this->getPostIdentifier('account_holder')];
				$bankAccount['accountnumber'] 	= $data[$this->getPostIdentifier('account_number')];
				$bankAccount['sortcode'] 		= $data[$this->getPostIdentifier('sort_code')];

				$this->getSession()->setBankAccount($bankAccount);
			}

	    	if (isset($data[$this->getPostIdentifier('phone')])) {
				$this->getSession()->setSelectedPhone($data[$this->getPostIdentifier('phone')]);
			}

			parent::assignData($data);
    }
}