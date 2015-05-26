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
class Billpay_Helper_Attachment extends Mage_Payment_Helper_Data {
	
  	/**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * @return Billpay_Helper_Log
     */
    public function getLog() {
    	return Mage::helper('billpay/log');
    }
	
	/**
	 * @return string
	 */
	public function getTempPath($storeId) {
		$tmpPath = Mage::getStoreConfig('billpaysettings/settings/temp_path', $storeId);
		
		if (!$tmpPath) {
			return '';
		}
		
		if (!substr($tmpPath, 0, 1) === '/' && strpos($tmpPath, ':') === false) {
			$tmpPath = BP . '/' . $tmpPath;
		}
		
    	if (substr($tmpPath, -1, 1) != '/') {
    		$tmpPath .= '/';
    	}
		
		return $tmpPath;
	}
	
	public function getAttachmentFilename($type, $orderId, $storeId) {
		$fileName = $storeId ? (string)$storeId . '_' : '';
		return $orderId . '_' . $type;
	}
	
	public function getFullAttachmentPath($type, $orderId, $storeId) {
		$tmpPath = $this->getTempPath($storeId);
		$fileName = $this->getAttachmentFilename($type, $orderId, $storeId);
		
		return $tmpPath . $fileName . ".pdf";
	}
	
	public function savePdfDocuments($captureRequest, $orderId, $storeId) {
    	$pdfBase64_1 = $captureRequest->get_standard_information_pdf();
    	$pdfBase64_2 = $captureRequest->get_email_attachment_pdf();
    	
    	$this->savePdfDocument($pdfBase64_1, 1, $orderId, $storeId);
    	$this->savePdfDocument($pdfBase64_2, 2, $orderId, $storeId);
    }
    
    private function savePdfDocument($base64encoded, $type, $orderId, $storeId) {
    	if ($base64encoded) {
    		$filePath = $this->getFullAttachmentPath($type, $orderId, $storeId);
			$pdfString = base64_decode($base64encoded);
			
			try {
				$fh = fopen($filePath, "w");
				fwrite($fh, $pdfString);
				fclose($fh);
				
				$this->getLog()->logDebug('Successfully saved pdf document: ' . $filePath);
				
				//$this->getSession()->setLastPdfDocumentPath($filePath);
				$this->getCheckout()->setData('billpay_last_pdf_document_path', $filePath);
			}
			catch(Exception $e) {
				$this->getLog()->logError('Can not save temporary pdf documents: ' . $filePath);
				$this->getLog()->logException($e);
				
				$errorMessage =  $this->__('internal_error_occured');
	            throw new Exception($errorMessage);
			}
    	}
    	else {
    		$this->getLog()->logError("Attachment of type $type not found.");
    		
    		$errorMessage =  $this->__('internal_error_occured');
	        throw new Exception($errorMessage);
    	}
    }
	
	
}