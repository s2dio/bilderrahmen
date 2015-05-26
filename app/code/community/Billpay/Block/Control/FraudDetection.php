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
class Billpay_Block_Control_FraudDetection extends Mage_Core_Block_Template {
	
	/**
	 * 
	 * @var string
	 */
	private $_cdnServer = 'https://h.online-metrix.net/tags';
	
	/**
	 * 
	 * @var string
	 */
	private $_orgId = 'ulk99l7b'; 
	
	
	/**
	 * @return Billpay_Helper_Api
	 */
	public function getApi() {
		return Mage::helper('billpay/api');
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getCDNServer() {
		return $this->_cdnServer;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getOrgId() {
		return $this->_orgId;
	}
	
	/**
	 * @return Billpay_Model_Session
	 */
	protected function getSession() {
		return Mage::getSingleton('billpay/session');
	}
	
	/**
	 * 
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct() {
		if($this->getSession()->getFraudDetectionCheck() != true)
		{
			$this->setTemplate('billpay/control/fraud_detection.phtml');
			$this->getSession()->setFraudDetectionCheck(true);
		}
		parent::_construct();
	}
}