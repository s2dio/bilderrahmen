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
 * @copyright  2010 Payment Network AG
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Logo.php 3844 2012-04-18 07:37:02Z dehn $
 */


class Paymentnetwork_Pnsofortueberweisung_Model_System_Config_Source_Logo
{
	public function toOptionArray()
	{
		return array(
			'logo_155x50'	   => Mage::helper('pnsofortueberweisung')->__('Logo + Text'),
			'banner_300x100'	   => Mage::helper('pnsofortueberweisung')->__('Banner'),
		);
	}
	
}
