<?php

class Infortis_Ultimo_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Patterns directory URL
	 *
	 * @var array
	 */
	protected $_patternsUrl;
	
	/**
	 * Background images directory URL
	 *
	 * @var array
	 */
	protected $_bgImagesUrl;
	
	/**
	 * Path to the directory with automatically generated CSS
	 *
	 * @var string
	 */
	protected $_generatedCssPath;
	
	/**
	 * Create paths
	 */
	public function __construct()
	{
	$this->_patternsUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .'wysiwyg/infortis/ultimo/_patterns/default/';
	$this->_bgImagesUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .'wysiwyg/infortis/ultimo/_backgrounds/';
	$this->_generatedCssPath = 'skin/frontend/ultimo/default/css/_css/';
	}
	
	/**
	 * Get selected group from theme's main configuration array
	 *
	 * @return array
	 */
	public function getCfgGroup($group, $storeId = NULL)
    {
		if ($storeId)
			return Mage::getStoreConfig('ultimo/' . $group, $storeId);
		else
			return Mage::getStoreConfig('ultimo/' . $group);
    }
	
	/**
	 * Get theme's design configuration array
	 *
	 * @return array
	 */
	public function getDesignCfgSection($storeId = NULL)
    {
		if ($storeId)
			return Mage::getStoreConfig('ultimo_design', $storeId);
		else
			return Mage::getStoreConfig('ultimo_design');
    }
	
	/**
	 * Get theme's layout configuration array
	 *
	 * @return array
	 */
	public function getLayoutCfgSection($storeId = NULL)
    {
		if ($storeId)
			return Mage::getStoreConfig('ultimo_layout', $storeId);
		else
			return Mage::getStoreConfig('ultimo_layout');
    }
	
	/**
	 * Get theme's basic settings (single option)
	 *
	 * @return string
	 */
	public function getCfg($optionString)
    {
        return Mage::getStoreConfig('ultimo/' . $optionString);
    }
	
	/**
	 * Get theme's design settings (single option)
	 *
	 * @return string
	 */
	public function getDesignCfg($optionString)
    {
        return Mage::getStoreConfig('ultimo_design/' . $optionString);
    }
	
	/**
	 * Get theme's layout settings (single option)
	 *
	 * @return string
	 */
	public function getLayoutCfg($optionString, $storeCode = NULL)
    {
        return Mage::getStoreConfig('ultimo_layout/' . $optionString, $storeCode);
    }
	
	/**
	 * Get patterns directory path
	 *
	 * @return string
	 */
	public function getPatternsUrl()
    {
        return $this->_patternsUrl;
    }
	
	/**
	 * Get background images directory path
	 *
	 * @return string
	 */
	public function getBgImagesUrl()
    {
        return $this->_bgImagesUrl;
    }
	
	/**
	 * Get automatically generated CSS directory path
	 *
	 * @return string
	 */
	public function getGeneratedCssPath()
    {
        return $this->_generatedCssPath;
    }

	
	
		
	/**
	 * Get image URL for given product
	 *
	 * @param $t
	 * @param Mage_Catalog_Model_Product $prod Product
	 * @param int $w Image width
	 * @param int $h Image height
	 * @param string $imgVersion Image version: normal, small or thumbnail
	 * @param $f Specific file
	 * @return string
	 */
	public function getImgUrl($t, $prod, $w, $h, $imgVersion='image', $f=NULL)
	{
		$imgUrl = '';
		if ($h <= 0)
			$imgUrl = $t->helper('catalog/image')->init($prod, $imgVersion, $f)
				->constrainOnly(true)
				->keepAspectRatio(true)
				->keepFrame(false)
				->resize($w);
		else $imgUrl = $t->helper('catalog/image')->init($prod, $imgVersion, $f)->resize($w, $h);
		return $imgUrl;
	}
	
	/**
	 * Returns true, if color is specified and the value doesn't equal "transparent"
	 *
	 * @param string $color color code
	 * @return bool
	 */
	public function isColor($color)
	{
		if ($color && $color != 'transparent')
			return true;
		else
			return false;
	}

	/**
	 * Get HTML of all child blocks with given ID
	 *
	 * @param $block Current block object
	 * @param string $staticBlockId ID of static blocks
	 * @param bool $auto Automatically align static blocks vertically
	 * @return string HTML output
	 */
	function getFormattedBlocks($block, $staticBlockId, $auto = true)
	{
		//Get HTML output of 6 static blocks with ID $staticBlockId<X>, where <X> is a number from 1 to 6
		$colCount = 0; //Number of existing static blocks
		$colHtml = array(); //Static blocks content
		$html = ''; //Final HTML output
		for ($i = 1; $i < 7; $i++)
		{
			if ($tmp = $block->getChildHtml($staticBlockId . $i))
			{
				$colHtml[] = $tmp;
				$colCount++;
			}
		}
		
		if ($colHtml)
		{
			$gridClass = '';
			$gridClassBase = 'grid12-';
			$gridClassPersistent = 'persistent-grid2-1';
			
			//Get grid unit class
			if ($auto)
			{
				//Grid units per static block
				$n = (int) (12 / $colCount);
				$gridClass = $gridClassBase . $n;
			}
			else
				$gridClass = $gridClassBase . '2';
			
			//Change persistent grid unit to 3 columns
			if ($colCount == 5 || $colCount % 3 == 0)
			{
				$gridClassPersistent = 'persistent-grid3-1';
			}
			
			for ($i = 0; $i < $colCount; $i++)
			{
				$classString = $gridClassPersistent .' '. $gridClass . ($i==0?' alpha':'') . ($i==$colCount-1?' omega':'');
				$html .= '<div class="'. $classString .'">';
				$html .= '	<div class="section-space std">'. $colHtml[$i] .'</div>';
				$html .= '</div>';
			}
		}
		return $html;
	}
}
