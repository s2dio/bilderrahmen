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
 * Class Billpay_Block_FormContent
 * @method void setShowGenderSelect()
 * @method void setShowDobSelect()
 * @method void setShowBankAccount()
 * @method void setGenderSelectHtml()
 * @method void setSalutationSelectHtml()
 *
 * @todo finish docblock
 */
class Billpay_Block_FormContent extends Mage_Core_Block_Template {
	
	protected function _construct() {
		$this->setTemplate('billpay/form_content.phtml');
		parent::_construct();
	}
	
}