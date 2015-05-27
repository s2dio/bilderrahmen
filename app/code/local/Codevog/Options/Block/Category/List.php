<?php

class Codevog_Options_Block_Category_List extends Mage_Core_Block_Template
{
    protected $columnCount = 4;

    public function _construct()
    {
        $this->setTemplate('catalog/category/list.phtml');
        return parent::_construct();
    }

    public function getCategories()
    {
        $categoryId = $this->getCurrentCategoryId();
        if($categoryId)
            return Mage::getModel('catalog/category')->getCategories($categoryId);

        return false;
    }

    public function getCurrentCategoryId()
    {
        return (Mage::registry('current_category') && ($categoryId = Mage::registry('current_category')->getId())) ? $categoryId : false;
    }

    public function getCategoryImageUrl($category)
    {
        $imagePath = Mage::getBaseDir('media').'/catalog/category/';

        $image = ($category->getThumbnail()) ? $category->getThumbnail() : $category->getImage();
        if(!$image)
            return $this->getPlaceholder();
        else
            if(!file_exists($imagePath.$image))
                return $this->getPlaceholder();

        $imageUrl = Mage::getBaseUrl('media').'catalog/category/';

        return $imageUrl.$image;
    }

    public function getPlaceholder()
    {
        $placeholderUrl = Mage::getBaseUrl('media').'catalog/product/placeholder/';
        $placeholderName = Mage::getStoreConfig('catalog/placeholder/thumbnail_placeholder');

        return $placeholderUrl.$placeholderName;
    }

    public function getColumnCount()
    {
        return $this->columnCount;
    }

}