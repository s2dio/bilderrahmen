<?php

class Loewenstark_Galerieschiene_Block_Product_New extends Mage_Catalog_Block_Product_List{
    public function __construct(){
        $this->setTemplate('galerieschiene/list.phtml');
        parent::__construct();
    }
    public function getProductCollection(){
         $todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

      $collection = Mage::getResourceModel('catalog/product_collection');
      $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());

      $collection = $this->_addProductAttributesAndPrices($collection)
         ->addStoreFilter()
         ->addAttributeToFilter('news_from_date', array('date' => true, 'to' => $todayDate))
         ->addAttributeToFilter('news_to_date', array('or'=> array(
            0 => array('date' => true, 'from' => $todayDate),
            1 => array('is' => new Zend_Db_Expr('null')))
         ), 'left')
         ->addAttributeToSort('news_from_date', 'desc');
      
      $collection->getSelect()->limit(5, 0);
      return $collection;
    }
}

