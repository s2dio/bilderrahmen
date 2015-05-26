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
class Billpay_Model_Source_StatusPaidByMethod {
	
	public function toOptionArray() {
		$options =  array();
		$_statuses = Mage::getSingleton('sales/order_config')->getStatuses();
		
		if (is_array($_statuses)) {
			foreach ($_statuses as $_statKey => $_statValue) {
				$options[] = array(
							 'value' => $_statKey,
							 'label' => $_statValue
							 );
			}
		}

		return $options;
	}
}