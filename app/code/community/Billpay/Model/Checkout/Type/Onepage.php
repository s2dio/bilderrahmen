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
class Billpay_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage {
	
	/**
	 * FALLS DIESE KLASSE AUFGRUND EINES KONFLIKTS (VERURSACHT Z.B. DURCH EIN ANDERES ADDON) NICHT AKTIV IST,
	 * MUSS DIE KOMPLETTE METHODE IN DIE AKTIVE KLASSE KOPIERT WERDEN. SOLLTE DIE METHODE IN DER AKTIVEN KLASSE
	 * BEREITS VORHANDEN SEIN, MUSS DIE 'Mage::dispatchEvent'-ANWEISUNG ANS ENDE DER BEREITS VORHANDENEN METHODE 
	 * KOPIERT WERDEN. OB DIESE KLASSE AKTIV IST, KANN NACH INSTALLATION DES BILLPAY-ADDONS MIT HILFE DES 
	 * DIAGNOSESKRIPTS, WELCHES UNTER DER FOLGENDEN URL VERFUEGBAR IST, UEBERPRUEFT WERDEN.
	 * 
	 * http://<Ihr-Magento-Shop>/billpay/diagnostics/checkRewrites
	 */
    public function savePayment($data) {
    	$result = parent::savePayment($data);
   		
   		Mage::dispatchEvent('billpay_after_save_payment', array(
   			'data'=>$data,
   			'useHTMLFormat'=>false,
    		'expectedDaysTillShipping'=>0
   		));
    	
        return $result;
    }
   
}
