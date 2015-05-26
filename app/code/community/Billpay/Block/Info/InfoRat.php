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
class Billpay_Block_Info_InfoRat extends Billpay_Block_InfoAbstract {
	
	protected function _construct() {
		parent::_construct();
		$this->setTemplate('billpay/info/info_rat.phtml');
	}
	
	public function format($value, $includeContainer) {
		$paymentInfo = $this->getInfo();
		if ($paymentInfo instanceof Mage_Sales_Model_Quote_Payment) {
			return Mage::getSingleton('checkout/session')->getQuote()->getStore()->formatPrice($value);
		}
		else {
			// Quick fix: method does not exist in older magento versions
			if (method_exists($this->getOrder(), "formatPricePrecision")) {
				return $this->getOrder()->formatPricePrecision($value, 2, false);	
			}
			else {
				return $this->getOrder()->formatPrice($value, false);
			}
		}
	}

	public function toPdf() {
		$this->setTemplate('billpay/pdf/info_rat.phtml');
		return $this->toHtml();
	}
	
	public function hasOrder() {
		return !is_null($this->getOrder());
	}
	
	public function getSurcharge() {
		return $this->format($this->getInfo()->getBillpayRateSurcharge(), false);
	}
	
	public function getRateCount() {
		return $this->getInfo()->getBillpayRateCount();
	}
	
	public function getTotalPaymentAmount() {
		return $this->format($this->getInfo()->getBillpayRateTotalAmount(), false);
	}
	
	public function getGrandTotal() {
		return $this->format($this->getOrder()->getGrandTotal(), false);
	}
	
	public function getCalculationBaseAmount() {
		return $this->format($this->getInfo()->getBillpayRateBaseAmount(), false);
	}
	
	public function getCalculationResidualAmount() {
		return $this->format($this->getInfo()->getBillpayRateResidualAmount(), false);
	}
	
	public function getInterestRate() {
		return $this->getInfo()->getBillpayRateInterestRate();
	}
	
	public function getFormula() {
		return Mage::helper('billpay/calculation')
			->getRateSurchargeFormula(
				$this->format($this->getInfo()->getBillpayRateBaseAmount(), true), 
				$this->getInterestRate(),
				$this->getRateCount()
			);
	}
	
	public function getAnualPercentageRate() {
		return Zend_Locale_Format::toNumber($this
			->getInfo()
			->getBillpayRateAnualRate(), 
			array('precision' => 2));
	}
	
	public function getTransactionFee() {
		return $this->format($this->getInfo()->getBillpayRateFee(), false);
	}
	
	public function getDues() {
		$data = array();
		$val = trim($this->getInfo()->getBillpayRateDues());
		
		if (!empty($val)) {
			$dues = explode(',', $val);
			$pos = 0;
			
	        foreach ($dues as $due) {
	        	$parts = explode(':', $due);
	        	
	        	$date = trim($parts[0]);
	       		$dateFormatted = empty($date) ? '' :
	        		Mage::helper('core')->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
	        	$value  	   = $this->format($parts[1]/100, false);
	        
	        	$data[] = array(
	        		'pos' => ++$pos, 
	        		'date' => $dateFormatted, 
	        		'value' => $value
	        	);
	        }
		}
		
		return $data; 
	}
	
	public function getFirstRate() {
		$dues = explode(',', $this->getInfo()->getBillpayRateDues());
		$parts = explode(':', $dues[0]);
		return $this->format($parts[1]/100, false);
	}
	
	public function getFirstRateDate() {
		$dues = explode(',', $this->getInfo()->getBillpayRateDues());
		$parts = explode(':', $dues[0]);
		return Mage::helper('core')->formatDate($parts[0]);
	}
	
	public function getFollowingRate() {
		$dues = explode(',', $this->getInfo()->getBillpayRateDues());
		$parts = explode(':', $dues[1]);
		return $this->format($parts[1]/100, false);
	}
	
	public function getFollowingRateDate() {
		$dues = explode(',', $this->getInfo()->getBillpayRateDues());
		$parts = explode(':', $dues[1]);
		return Mage::helper('core')->formatDate($parts[0]);
	}
}