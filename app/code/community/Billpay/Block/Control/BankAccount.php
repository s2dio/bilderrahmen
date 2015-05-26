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
class Billpay_Block_Control_BankAccount extends Mage_Core_Block_Template {
	
	/**
	 * @return Billpay_Helper_Api
	 */
	private function getApi() {
		return Mage::helper('billpay/api');
	}
	
   /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * custom constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $bSepaActive = $this->getApi()->getConfigData('settings/activate_sepa_handling', $this->getQuote()->getStoreId());
        if ($bSepaActive == 1) {
            $this->setTemplate('billpay/control/bank_account_sepa.phtml');
        } else {
            $this->setTemplate('billpay/control/bank_account.phtml');
        }
		parent::_construct();
	}
	
	public function getFieldWidth() {
		$checkoutType = $this->getApi()->getConfigData('settings/checkout_type', $this->getQuote()->getStoreId());
		
		if ($checkoutType == 'onestepcheckout') {
    		return 100;
    	}
    	return 227;
	}
	
}