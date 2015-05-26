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
class Billpay_Block_Form_Rat_RateInfo extends Billpay_Block_Form_Rat_Abstract {

	private $_error = null;
	
	/**
	 * @return Billpay_Model_Session
	 */
	private function getSession() {
		return Mage::getSingleton('billpay/session');
	}
	
  	/**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
	
    /**
     * @return array
     */
	public function getDues() {
		return $this->getSession()->getDues();
	}
	
	public function getHasError() {
		if (!$this->getCheckout()->getQuoteId()) {
			$this->_error = 'checkout not active!';   // TODO: to language file
		}
		else if (!is_array($this->getSession()->getDues())) {
			$this->_error = 'no dues present!';   // TODO: to language file
		}
		return !is_null($this->_error);
	}
	
	public function getError() {
		return 'haha';	
	}
	
}