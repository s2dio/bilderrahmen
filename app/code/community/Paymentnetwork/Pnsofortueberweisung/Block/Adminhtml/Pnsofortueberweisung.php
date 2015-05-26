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
 * @author Markus Schneider <markus.schneider[at]initos.com>
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Pnsofortueberweisung.php 3844 2012-06-06 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Adminhtml_Pnsofortueberweisung extends Mage_Adminhtml_Block_Widget_Grid_Container {
	public function __construct() {
		$this->_controller = 'adminhtml_pnsofortueberweisung';
		$this->_blockGroup = 'pnsofortueberweisung';
		$this->_headerText = Mage::helper('pnsofortueberweisung')->__('Orders with Rechnung by SOFORT');
		parent::__construct();
		$this->_removeButton('add');
	}
}