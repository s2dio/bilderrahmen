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
class Billpay_Block_Form_Rat_Step2 extends Billpay_Block_Form_Rat_Abstract {
	
	protected function _construct() {
		$this->setTemplate('billpay/form/rat/step2.phtml');
		parent::_construct();
	}
	
	/**
	 * @return Billpay_Helper_Api
	 */
	private function getApi() {
		return Mage::helper('billpay/api');
	}
	
	/**
	 * @return Billpay_Model_Session
	 */
	private function getSession() {
		return Mage::getSingleton('billpay/session');
	}
	
	/**
	 * Check whether date of birth is set
	 *
	 * @return boolean
	 */
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