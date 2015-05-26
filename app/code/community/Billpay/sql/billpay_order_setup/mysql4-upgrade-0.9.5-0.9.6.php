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

$installer->addAttribute('creditmemo', 'billpay_rate_surcharge', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_rate_fee', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_rate_total_amount', array('type'=>'decimal'));

$installer->addAttribute('quote_payment', 'billpay_rate_fee_net', array('type'=>'decimal'));
$installer->addAttribute('quote_payment', 'billpay_rate_fee_tax', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_fee_net', array('type'=>'decimal'));
$installer->addAttribute('order_payment', 'billpay_rate_fee_tax', array('type'=>'decimal'));

$installer->addAttribute('invoice', 'billpay_rate_fee_net', array('type'=>'decimal'));
$installer->addAttribute('invoice', 'billpay_rate_fee_tax', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_rate_fee_net', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'billpay_rate_fee_tax', array('type'=>'decimal'));

$installer->removeAttribute('creditmemo', 'billpay_rate_fee_refunded');
$installer->removeAttribute('creditmemo', 'billpay_rate_total_amount_refunded');
$installer->removeAttribute('creditmemo', 'billpay_rate_surcharge_refunded');

$installer->endSetup();

