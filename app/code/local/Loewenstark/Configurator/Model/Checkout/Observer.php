<?php
class Loewenstark_Configurator_Model_Checkout_Observer
{
    function disableCsrf($observer)
    {
        $key = Mage::getSingleton('core/session')->getFormKey();
        $observer->getEvent()->getRequest()->setParam('form_key', $key);
    }
}


