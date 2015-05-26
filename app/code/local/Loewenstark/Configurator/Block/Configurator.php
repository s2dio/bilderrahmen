<?php

class Loewenstark_Configurator_Block_Configurator extends Mage_Core_Block_Template{
    
    public function getGleiterUrl(){
       $id = Mage::getStoreConfig('home/products/product_gleiter');
       if(!$id)return false;
       return Mage::getModel('catalog/product')->load($id)->getProductUrl();
    }
    public function getProducts($art = 'gleiter'){
        $id = Mage::getStoreConfig('home/products/product_'.$art);
       if(!$id)return false;
       $relatedIds = Mage::getModel('catalog/product')->load($id)->getRelatedProductCollection()
        ->setOrder('position', Varien_Db_Select::SQL_ASC)
        ->addStoreFilter();
       $related = array();
       foreach($relatedIds as $id){
           $related[] = Mage::getModel('catalog/product')->load($id->getEntityId());
       }
       return $related;
    }
    public function getOeseUrl(){
       $id = Mage::getStoreConfig('home/products/product_oese');
       if(!$id)return false;
       return Mage::getModel('catalog/product')->load($id)->getProductUrl();
    }
}
