<?php

class Codevog_Options_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MAX_PERCENTS = 100;
    private $activeStores = array(6, 7, 8, 9, 10);

    const SHOW_TAX_INFO = 'catalog/frontend/display_taxinfo';
    const SHOW_SHIPPING_COSTS = 'catalog/frontend/display_shippingcosts';
    const SHIPPING_URL = 'tax/display/shippingurl';

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

    protected static function _getShippingLink()
    {
        $displayShipping = Mage::getStoreConfig(self::SHOW_SHIPPING_COSTS);

        if ($displayShipping == '0') {
            return '';
        } else if ($displayShipping == 'incl') {
            $pattern = Mage::helper('core')->__('Incl. <a href="%1$s">shipping</a>');
        } else {
            $pattern = Mage::helper('core')->__('Excl. <a href="%1$s">shipping</a>');
        }

        $value = Mage::getUrl(Mage::getStoreConfig(self::SHIPPING_URL));
        $shippingLink = sprintf($pattern, $value);

        return $shippingLink;
    }


    public static function getTaxInfo($product)
    {
        if ($product->getCanShowPrice() === false) {
            return null;
        }

        $productTypeId = $product->getTypeId();
        if ($productTypeId == 'combined') {
            return null;
        }

        Mage::getStoreConfigFlag(self::SHOW_TAX_INFO);

        $ignoreTypeIds = array('virtual', 'downloadable');
        $taxInfo = self::_getTaxInfo($product);
        $shippingLink = self::_getShippingLink();
        if (in_array($productTypeId, $ignoreTypeIds) || empty($shippingLink)) {
            $result = '<span class="tax-details">' . $taxInfo . '</span>';
        } else {
            $result = '<span class="tax-details">' . $taxInfo . ', ' . $shippingLink . '</span>';
        }

        return $result;
    }

    protected static function _getTaxInfo($product)
    {
        $showPercentage = true;
        $tax = Mage::helper('tax');
        $helper = Mage::helper('codevog_options');

        if ($product->getTypeId() == 'bundle') {
            $showPercentage = false;
        }
        if ($showPercentage) {
            $taxPercent = $product->getTaxPercent();
            $locale = Mage::app()->getLocale()->getLocaleCode();

            $taxPercent = Zend_Locale_Format::getNumber($taxPercent, array('locale' => $locale));
            if ($taxPercent == 0) {
                $showPercentage = false;
            }
        }

        if ($showPercentage && Mage::getStoreConfigFlag(self::SHOW_TAX_INFO)) {
            if ($tax->displayPriceIncludingTax()) {
                $taxInfo = sprintf($helper->__('Incl. %1$s%% VAT'), $taxPercent);
            } else {
                $taxInfo = sprintf($helper->__('Excl. %1$s%% VAT'), $taxPercent);
            }
        } else {
            if ($tax->displayPriceIncludingTax()) {
                $taxInfo = $helper->__('Incl. VAT');
            } else {
                $taxInfo = $helper->__('Excl. VAT');
            }
        }

        return $taxInfo;
    }

}
