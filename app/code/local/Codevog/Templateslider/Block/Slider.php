<?php


class Codevog_Templateslider_Block_Slider
    extends Mage_Catalog_Block_Product_Abstract
    implements Mage_Widget_Block_Interface
{

	/**
	 * Get latest products collection
	 *
	 * @return Mage_Catalog_Model_Resource_Product_Collection|Object
	 */
	public function getLatestCollection()
	{
		$todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

		$collection = Mage::getResourceModel('catalog/product_collection');
		$collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());

		$collection = $this->_addProductAttributesAndPrices($collection)
			->addStoreFilter()
			->addAttributeToFilter('news_from_date', array('or'=> array(
				0 => array('date' => true, 'to' => $todayDate),
				1 => array('is' => new Zend_Db_Expr('null')))
			), 'left')
			->addAttributeToFilter('news_to_date', array('or'=> array(
				0 => array('date' => true, 'from' => $todayDate),
				1 => array('is' => new Zend_Db_Expr('null')))
			), 'left')
			->addAttributeToFilter(
				array(
					array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
					array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
				)
			)
			->addAttributeToSort('news_from_date', 'desc');

		return $collection;
	}

	/**
	 * Get on sale products collection
	 *
	 * @return Mage_Catalog_Model_Resource_Product_Collection|Object
	 */
	public function getSaleCollection()
	{
		$todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

		$collection = Mage::getResourceModel('catalog/product_collection');
		$collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());

		$collection = $this->_addProductAttributesAndPrices($collection)
			->addStoreFilter()
			->addAttributeToFilter('special_from_date', array('or'=> array(
				0 => array('date' => true, 'to' => $todayDate),
				1 => array('is' => new Zend_Db_Expr('null')))
			), 'left')
			->addAttributeToFilter('special_to_date', array('or'=> array(
				0 => array('date' => true, 'from' => $todayDate),
				1 => array('is' => new Zend_Db_Expr('null')))
			), 'left')
			->addAttributeToFilter(
				array(
					array('attribute' => 'special_from_date', 'is'=>new Zend_Db_Expr('not null')),
					array('attribute' => 'special_to_date', 'is'=>new Zend_Db_Expr('not null'))
				)
			)
			->addAttributeToSort('special_from_date', 'desc');

		return $collection;
	}


    public function getProductsWithSpecialPrice()
    {
        $_productCollection = Mage::getModel('catalog/product')->getCollection();
        $_productCollection->addAttributeToSelect(array(
            'image',
            'name',
            'short_description'
        ))
            ->addFieldToFilter('visibility', array(
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
            ))
            ->addFinalPrice()
            ->getSelect()
            ->where('price_index.final_price < price_index.price');
        return $_productCollection;
    }



    public function getTopSellerCollection($size = 10)
    {
        $storeId    = Mage::app()->getStore()->getId();

        $products = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect(array('name', 'price', 'small_image')) //edit to suit tastes
            ->setStoreId($storeId)
//            ->addStoreFilter($storeId)
            ->setOrder('ordered_qty', 'desc'); //best sellers on top
        if($size)
            $products->setPageSize($size);

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($products);
        
        return $products;
    }

    public function getLastViewedCollection($size = 10)
    {
        return Mage::getSingleton('Mage_Reports_Block_Product_Viewed')->getItemsCollection()->setPageSize($size);
    }

}
