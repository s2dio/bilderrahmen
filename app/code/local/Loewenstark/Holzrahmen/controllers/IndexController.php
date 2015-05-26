<?php

class Loewenstark_Holzrahmen_IndexController extends Mage_Core_Controller_Front_Action{
    
    public function sendAction(){
        $params = $this->getRequest()->getParams();
        $emailModel = new Loewenstark_Holzrahmen_Model_Email();
        $emailModel->setData($params);
        $msg = $emailModel->sendServiceEmail();
        echo json_encode($msg);
    }
}
