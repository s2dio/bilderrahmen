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
class Billpay_CheckoutController extends Mage_Core_Controller_Front_Action {
	
	private function setResponse($type, $name, $templatePath, $modelPath) {
		$this->loadLayout();
		
		$block = $this->getLayout()->createBlock(
			$type,
			$name,
			array('template' => $templatePath)
		);
		
		$block->setData(
			'method',
			Mage::getSingleton($modelPath)
		);
		
		$result = array();
    	$result['goto_section'] = 'payment';
        $result['update_section'] = array(
        	'name' => 'payment-method',
            'html' => $block->toHtml()
        );

        $this->getResponse()->setBody(Zend_Json::encode($result));
	}
	
	public function formAction() {
		$this->setResponse('billpay/form', 'billpay_form', 'billpay/form_ajax2.phtml', 'billpay/rec');
	}
	
	public function formElvAction() {
		$this->setResponse('billpay/formElv', 'billpay_formElv', 'billpay/form_ajax2.phtml', 'billpay/elv');
	}
	
}