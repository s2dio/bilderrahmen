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
class Billpay_Model_Elv extends Billpay_Model_Abstract {
	protected $_code			= 'billpay_elv';
	protected $_formBlockType	= 'billpay/formElv';
	protected $_infoBlockType	= 'billpay/infoElv';
	protected $_paymentMethod	= 'elv';

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
		return 'is_direct_debit_allowed';
	}

	public function assignData($data) {
		if (isset($data[$this->getPostIdentifier('account_holder')]) &&
			isset($data[$this->getPostIdentifier('account_number')]) &&
			isset($data[$this->getPostIdentifier('sort_code')])) {


			$bankAccount = array();
			$bankAccount['accountholder'] 	= $data[$this->getPostIdentifier('account_holder')];
			$bankAccount['accountnumber'] 	= $data[$this->getPostIdentifier('account_number')];
			$bankAccount['sortcode'] 		= $data[$this->getPostIdentifier('sort_code')];

			$this->getSession()->setBankAccount($bankAccount);
		}

		parent::assignData($data);
	}
}