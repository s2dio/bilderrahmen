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

$installer->addAttribute('quote_payment', 'billpay_rate_surcharge', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_total_amount', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_count', array('type'=>'int'));
$installer->addAttribute('quote_payment', 'billpay_rate_dues', array('type'=>'text'));
$installer->addAttribute('quote_payment', 'billpay_rate_interest_rate', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_anual_rate', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_base_amount', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_residual_amount', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_fee', array('type'=>'decimal'));

$installer->addAttribute('order_payment', 'billpay_rate_surcharge', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_total_amount', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_count', array('type'=>'int'));
$installer->addAttribute('order_payment', 'billpay_rate_dues', array('type'=>'text'));
$installer->addAttribute('order_payment', 'billpay_rate_interest_rate', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_anual_rate', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_base_amount', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_residual_amount', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_fee', array('type'=>'decimal'));

$installer->addAttribute('invoice', 'billpay_rate_surcharge', array('type'=>'decimal'));
$installer->addAttribute('invoice', 'billpay_rate_total_amount', array('type'=>'decimal'));

$installer->addAttribute('creditmemo', 'billpay_rate_surcharge_refunded', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_rate_total_amount_refunded', array('type'=>'decimal'));

if (Mage::getVersion() >= 1.1) {
    $installer->startSetup();    
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_surcharge', 'decimal(12,4)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_total_amount', 'decimal(12,4)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_count', 'int(10)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_dues', 'text');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_interest_rate', 'decimal(12,4)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_anual_rate', 'decimal(12,4)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_base_amount', 'decimal(12,4)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_residual_amount', 'decimal(12,4)');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'), 'billpay_rate_fee', 'decimal(12,4)');
    
    $installer->endSetup();
}

$installer->endSetup();
