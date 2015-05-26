<?php
class Loewenstark_Holzrahmen_Model_Email extends Varien_Object{
    
    
    public function sendServiceEmail(){
        $storeId = Mage::app()->getStore();
        $templateId = Mage::getStoreConfig(Loewenstark_Holzrahmen_Model_Config::EMAIL_TEMPLATE, $storeId);
        $mailSubject = Mage::getStoreConfig(Loewenstark_Holzrahmen_Model_Config::EMAIL_SUBJECT, $storeId);
        $reciever = Mage::getStoreConfig(Loewenstark_Holzrahmen_Model_Config::IDENTITY, $storeId);
        
         $reciever = 'christopher.boehm@mage-profis.de';
        $sender['name'] = $this->getName();
        $sender['email'] = $this->getEmail();
        $mail = Mage::getModel('core/email_template')
                ->setDesignConfig(array('area'=>'frontend', 'store'=>Mage::app()->getStore()->getId()))
                ->setTemplateSubject($mailSubject)
                ->sendTransactional($templateId,$sender,$reciever,$this->getName(),$this->getData());
        if($mail){
            return Mage::helper('core')->__('Die Email wurde erfolgreich gesendet');
        }else{
            return Mage::helper('core')->__('Fehler beim senden der Email. Bitte versuchen Sie es später noch einmal oder kontaktieren Sie den Administrator');
        }
        
    }
}

