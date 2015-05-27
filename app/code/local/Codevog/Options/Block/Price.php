<?php

class Codevog_Options_Block_Price extends Mage_Catalog_Block_Product_Abstract
    implements Mage_Widget_Block_Interface
{
    public function getPriceHtml($product, $template = '', $afterInfo = false, $showTax = false)
    {
        $this->setTemplate('catalog/product/price'.$template.'.phtml');
        $this->setProduct($product);
        $priceHtml = $this->toHtml();

        return $priceHtml;
    }
}