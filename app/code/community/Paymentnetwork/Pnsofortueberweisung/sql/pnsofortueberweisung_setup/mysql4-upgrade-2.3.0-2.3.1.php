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
 * @category   Paymentnetwork
 * @package	Paymentnetwork_Sofortueberweisung
 * @copyright  Copyright (c) 2012 initOS GmbH & Co. KG, 2012 Payment Network AG
 * @author Markus Schneider <markus.schneider@initos.com>
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: mysql4-upgrade-2.3.1.php 3844 2012-04-18 07:37:02Z dehn $
 */

/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer = $this;
$installer->startSetup();

// Magento < 1.5 Table not exist - just prevent exception
try {
    $installer->run("
        INSERT INTO  `{$this->getTable('sales/order_status')}` (
            `status` ,
            `label`
        ) VALUES (
            'unchanged',  '--Unchanged--'
        );
        INSERT INTO  `{$this->getTable('sales/order_status_state')}` (
            `status` ,
            `state` ,
            `is_default`
        ) VALUES (
            'unchanged',  'sofort',  '0'
        );
    ");
} catch ( Exception $e ){
    
}    
 
$installer->endSetup();

