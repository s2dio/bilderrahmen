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
$installer = $this;

$installer->startSetup();

$installer->addAttribute('order', 'billpay_account_holder', array('type'=>'varchar'));
$installer->addAttribute('order', 'billpay_account_number', array('type'=>'varchar'));
$installer->addAttribute('order', 'billpay_bank_code', array('type'=>'varchar'));
$installer->addAttribute('order', 'billpay_bank_name', array('type'=>'varchar'));
$installer->addAttribute('order', 'billpay_invoice_duedate', array('type'=>'varchar'));
$installer->addAttribute('order', 'billpay_invoice_reference', array('type'=>'varchar'));

$installer->endSetup();
