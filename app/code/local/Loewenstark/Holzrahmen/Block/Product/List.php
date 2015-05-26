<?php

class Loewenstark_Holzrahmen_Block_Product_List extends FireGento_GermanSetup_Block_Catalog_Product_Price {
    
    
    
    public function getTaxDetailsHtml(){
        $htmlTemplate = $this->getLayout()->createBlock('core/template')
            ->setTemplate('germansetup/price_info2.phtml')
            ->setFormattedTaxRate($this->getFormattedTaxRate())
            ->setIsIncludingTax($this->isIncludingTax())
            ->setIsIncludingShippingCosts($this->isIncludingShippingCosts())
            ->setIsShowShippingLink($this->isShowShippingLink())
            ->toHtml();

        echo $htmlTemplate;
    }
}

