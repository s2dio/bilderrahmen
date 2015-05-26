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

/**
 * Class Billpay_Block_FormElv
 */
class Billpay_Block_FormElv extends Billpay_Block_FormAbstract {
	
	protected function _construct() {
		$ajaxFormActivated 			= $this->getApi()->getConfigData('settings/activate_ajax_form', $this->getQuote()->getStoreId());
		
		if ($this->getApi()->isOneStepCheckout($this->getQuote()->getStoreId())) {
			$this->setTemplate('billpay/form_onestep.phtml');
		}
		else if ($ajaxFormActivated) {
			$this->setTemplate('billpay/form_elv_ajax.phtml');
		}
		else {
			$this->setTemplate('billpay/form.phtml');
		}
		
		parent::_construct();
	}
	
 	public function showBankAccount() {
    	return true;
    }
}