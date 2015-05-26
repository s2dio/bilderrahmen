<?php
class Infortis_Brands_Block_Brands extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
	
	public function getCurrentProductObject()
	{
		return Mage::registry('current_product');
	}
}