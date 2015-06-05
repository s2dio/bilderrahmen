<?php

class Codevog_Options_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MAX_PERCENTS = 100;
    private $activeStores = array(6, 7, 8, 9, 10);

    public function canEmailToFriend()
    {
        return Mage::getModel('sendfriend/sendfriend')->canEmailToFriend();
    }

    public function discountLabel($product)
    {
//        if($this->isProductOnSale($product))
//        {
//            return '<span class="discount-label">'.$this->__('Discounts').'</span>';
//        }

        return false;
    }

    public function newLabel($product)
    {
//        if($this->isProductNew($product))
//        {
//            return '<span class="new-label">'.$this->__('New').'</span>';
//        }

        return false;
    }

    public function productLabels($product)
    {
        return $this->discountLabel($product).$this->newLabel($product);
    }

    protected function checkProductDate($from, $to)
    {
        $date = new Zend_Date();
        $today = strtotime($date->__toString());

        if ($from && $today < $from)
            return false;

        if ($to && $today > $to)
            return false;

        if (!$to && !$from)
            return false;

        return true;
    }

    protected function isProductNew($product)
    {
        $from = strtotime($product->getData('news_from_date'));
        $to = strtotime($product->getData('news_to_date'));

        return $this->checkProductDate($from, $to);
    }

    protected function isProductOnSale($product)
    {
        if($product->getSpecialPrice() && $product->getSpecialPrice() < $product->getPrice())
            return true;

        return false;
    }

    public function getProductDiscount($product, $prefix = '-', $suffix = '%')
    {
        if($this->isProductOnSale($product))
        {
            $maxPercent = self::MAX_PERCENTS;

            $price = $product->getPrice();
            $specialPrice = $product->getSpecialPrice();

            $percent = ($maxPercent - (($specialPrice * $maxPercent) / $price));
            $percent = ($percent > 0 && $percent < 1) ? '~1' : (int)$percent;

            return ($percent && $percent < $maxPercent) ? '<span class="discount-percent">'.$prefix.$percent.$suffix.'</span>' : false;
        }

        return false;
    }

    public function stateRequired()
    {
        return Mage::getStoreConfig('general/region/display_all');
    }

    public function getActiveStores()
    {
        return $this->activeStores;
    }

    public function isHomePage()
    {
        $routeName = Mage::app()->getRequest()->getRouteName();
        $identifier = Mage::getSingleton('cms/page')->getIdentifier();

        return ($routeName == 'cms' && $identifier == 'index') ? true : false;
    }
}
