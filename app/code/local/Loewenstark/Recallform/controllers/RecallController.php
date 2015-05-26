<?php

class Loewenstark_Recallform_RecallController extends Mage_Core_Controller_Front_Action{
    public function recallAction(){
        $this->loadLayout(false);
        $this->renderLayout();
    }
    public function sendrecallAction(){
        $request = $this->getRequest()->getParams();
        $formData = $request['recall'];
        /*
         * SEND FORM DATA IRGENDWO HIN...AN IRGENDWEN
         */
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($formData));
    }
}
