<?php

class Codevog_Options_Block_Checkout_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
    public function isOnCheckoutPage()
    {
        $module = $this->getRequest()->getModuleName();
        $controller = $this->getRequest()->getControllerName();
        return $module == 'checkout' && ($controller == 'onepage' || $controller == 'multishipping');
    }
}