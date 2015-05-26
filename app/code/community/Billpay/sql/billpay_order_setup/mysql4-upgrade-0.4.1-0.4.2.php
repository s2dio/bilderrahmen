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

$installer->addAttribute('order', 'billpay_charged_fee', array('type'=>'decimal'));
$installer->addAttribute('order', 'billpay_charged_fee_net', array('type'=>'decimal'));
$installer->addAttribute('order', 'billpay_charged_fee_refunded', array('type'=>'decimal'));
$installer->addAttribute('order', 'billpay_charged_fee_refunded_net', array('type'=>'decimal'));
$installer->addAttribute('invoice', 'billpay_charged_fee_amount', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_charged_fee_refunded', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_charged_fee_refunded_net', array('type'=>'decimal'));

$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'billpay_charged_fee', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'billpay_charged_fee_net', 'decimal(12,4)');

$installer->endSetup();
