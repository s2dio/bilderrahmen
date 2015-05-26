<?php

class Loewenstark_Galerieschiene_Block_Product_Bestseller extends Mage_Catalog_Block_Product_List{
    
    public function __construct(){
        $this->setTemplate('galerieschiene/list.phtml');
        parent::__construct();
    }
    public function getProductCollection(){
        $storeId = Mage::app()->getStore()->getId();

        $products = Mage::getResourceModel('reports/product_collection');
        
        $products->addOrderedQty()
                ->addAttributeToSelect('*')
                ->addOrderedQty()
                ->setStoreId($storeId)
                ->addStoreFilter($storeId)
                ->setOrder('ordered_qty', 'desc')
                ->setPageSize(10); 
        $products->getSelect()->limit(5, 0);
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
        return $products;
    }
}

