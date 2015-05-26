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
class Billpay_Block_Sales_Order_Totals_Surcharge extends Mage_Core_Block_Template {

	private $_order;
	private $_source;
	private $_config;
	
	private $_showFormula = true;
	
    /**
     * @return Billpay_Helper_Api
     */
    public function getApi() {
    	return Mage::helper('billpay/api');
    }
    
    public function setShowFormula($value) {
    	$this->_showFormula = $value;
    }
    
	/**
	 * @return string
	 */
	public function format($value) {
		return $this->getOrder()->formatPricePrecision($value, 2, false);
	}
    
    /**
	 * @return Mage_Sales_Model_Order
     */
    public function getOrder() {
    	if (!$this->_order) {
    		$parent 		= $this->getParentBlock();
        	$this->_order   = $parent->getOrder();
    	}
    	return $this->_order;
    }
    
    public function getSource() {
    	if (!$this->_source) {
    		$parent 		= $this->getParentBlock();
        	$this->_source   = $parent->getSource();
    	}
    	return $this->_source;
    }
    
	protected function _construct() {
        $this->_config = Mage::getSingleton('tax/config');
    }
    
    public function initTotals() {
   		if($this->getOrder()) {
	        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
	        if($this->getApi()->isBillpayRatPayment($paymentMethod)) {
	        	$totalPaymentAmount = $this->getTotalPaymentAmount();
	        	
	        	if ($totalPaymentAmount > 0) {
	        		
	        		$after = 'grand_total';
	        		if ($this->_config->displaySalesTaxWithGrandTotal($this->getOrder()->getStore())) {
	        			$after = 'grand_total_incl';
	        		}
	        		
	        		$suchargeLabel = $this->__('billpay_rate_calculation_interest_add');
	        		if ($this->_showFormula) {
	        			$suchargeLabel .= '<br >' . 
		        			Mage::helper('billpay/calculation')
		        				->getRateSurchargeFormula(
		        					$this->format($this->getOrder()->getGrandTotal(), true),
		        					$this->getInterestRate(),
		        					$this->getRateCount()
		        				);
	        		}
	        		
		        	$surchargeTotal = new Varien_Object(array(
			            'code'  => 'billpay_surcharge',
			            'field'  => 'billpay_surcharge',
			            'strong'=> true,
			            'value' => $this->getSurcharge(),
			            'label' => $suchargeLabel
			        ));
			        $this->getParentBlock()->addTotal($surchargeTotal, $after);
			        
			        $label = $this->__('billpay_rate_transaction_fee');
			        
			        $feeTaxAmount = $this->getTransationFee() - $this->getTransationFeeNet();
			        if ($feeTaxAmount > 0) {
				        $label .= '<br >('.$this->__('billpay_rate_included_tax').': ' . $this->format($feeTaxAmount) . ')';
			        }
			        
			        $transactionFee = new Varien_Object(array(
			            'code'  => 'billpay_transation_fee',
			            'field'  => 'billpay_transation_fee',
			            'strong'=> true,
			            'value' => $this->getTransationFee(),
			            'label' => $label
			        ));
			        $this->getParentBlock()->addTotal($transactionFee, 'billpay_surcharge');
			        
			        $totalAmount = new Varien_Object(array(
			            'code'  => 'billpay_total',
			            'field'  => 'billpay_total',
			            'strong'=> true,
			            'value' => $totalPaymentAmount,
			            'label' => $this->__('billpay_rate_calculation_partial_price')
			        ));
			        $this->getParentBlock()->addTotal($totalAmount, 'billpay_transation_fee');
			        
			        
	        	}
	        }
	        /*else if ($this->getApi()->isBillpayInvoicePayment($paymentMethod) ||
	        	$this->getApi()->isBillpayElvPayment($paymentMethod)) {
	        	
	        		if ($this->getOrder()->getBillpayChargedFee() > 0) {
	        			$feeTotal = new Varien_Object(array(
				            'code'  => 'billpay_fee',
				            'field'  => 'billpay_fee',
				            'strong'=> false,
				            'value' => $this->getOrder()->getBillpayChargedFee(),
				            'label' => $this->__($paymentMethod . '_step_fee_text')
				        ));
				        $this->getParentBlock()->addTotalBefore($feeTotal, 'grand_total');
	        		}
	        }*/
        }
        return $this;
    }
    
    public function getSurcharge() {
    	if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateSurcharge();
    	}
    	else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
    		$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateSurcharge();
    	}
    }
    
 	public function getTotalPaymentAmount() {
 		if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateTotalAmount();
    	}
 		else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
 			$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateTotalAmount();
    	}
    }
    
    public function getInterestRate() {
    	return $this->getOrder()
    		->getPayment()
    		->getMethodInstance()
    		->getInfoInstance()
    		->getBillpayRateInterestRate();
    }
    
    public function getRateCount() {
    	return $this->getOrder()
    		->getPayment()
    		->getMethodInstance()
    		->getInfoInstance()
    		->getBillpayRateCount();
    }
    
    public function getTransationFee() {
    	if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateFee();
    	}
 		else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
 			$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateFee();
    	}
    }
    
	public function getTransationFeeNet() {
    	if ($this->getSource() instanceof Mage_Sales_Model_Order) {
	    	return $this->getOrder()
	    		->getPayment()
	    		->getMethodInstance()
	    		->getInfoInstance()
	    		->getBillpayRateFeeNet();
    	}
 		else if ($this->getSource() instanceof Mage_Sales_Model_Order_Creditmemo ||
 			$this->getSource() instanceof Mage_Sales_Model_Order_Invoice) {
    		return $this->getSource()->getBillpayRateFeeNet();
    	}
    }
    
}