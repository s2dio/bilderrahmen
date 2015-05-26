<?php

class Infortis_CloudZoom_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getCfg($optionString)
    {
        return Mage::getStoreConfig('cloudzoom/' . $optionString);
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
	public function createImgUrl($t, $prod, $w, $h, $imgVersion='image', $f=NULL)
	{
		$imgUrl = '';
		if ($h <= 0)
			$imgUrl = $t->helper('catalog/image')->init($prod, $imgVersion, $f)
				->constrainOnly(TRUE)
				->keepAspectRatio(TRUE)
				->keepFrame(FALSE)
				->resize($w);
		else $imgUrl = $t->helper('catalog/image')->init($prod, $imgVersion, $f)->resize($w, $h);
		return $imgUrl;
	}
	
	/**
	 * Check if module is enabled.
	 * @return bool
	 */
	public function isCloudZoomEnabled()
	{
		return (bool) $this->getCfg('general/enable');
	}
	
	/**
	 * Check if lightbox is enabled
	 * @return bool
	 */
	public function useLightbox()
	{
		return (bool) $this->getCfg('lightbox/enable');
	}
	
	/**
	 * Check if cloud zoom is enabled
	 * @return bool
	 */
	public function useCloudZoom()
	{
		if ($this->getCfg('general/enable') && $this->getCfg('general/use_cloud_zoom'))
			return true;
		else
			return false;
	}
	
	/**
	 * Check if cloud zoom position equals 'inside'
	 * @return bool
	 */
	public function isPositionInside()
	{
		return ($this->getCfg('general/position') == 'inside');
	}
	
	/**
	 * Get string with Cloud Zoom options
	 * @return string
	 */
	public function getCloudZoomOptions()
	{		
		//Get Cloud Zoom config
		$position       = $this->getCfg('general/position');
		$lensOpacity    = intval($this->getCfg('general/lens_opacity')) / 100;
		$zoomWidth      = intval($this->getCfg('general/zoom_width'));
		$zoomHeight     = intval($this->getCfg('general/zoom_height'));
		$tintColor      = trim($this->getCfg('general/tint_color'));
		$tintOpacity    = intval($this->getCfg('general/tint_opacity')) / 100;
		$softFocus		= intval($this->getCfg('general/soft_focus'));
		$smoothMove		= intval($this->getCfg('general/smooth_move'));
		
        //Create Cloud Zoom config array
        $cfg = array(
            "position:'{$position}'",
            "showTitle:false",
            "lensOpacity:{$lensOpacity}",
            "smoothMove:{$smoothMove}",
        );
        
        if ($zoomWidth) {
            $cfg[] = "zoomWidth:{$zoomWidth}";
        }
        if ($zoomHeight) {
            $cfg[] = "zoomHeight:{$zoomHeight}";
        }
    
        //Right and bottom: move 10px (+ 2 * 1px border). Left and top: move -10px (- 2 * 1px border).
        if ($position == 'inside') {
            $cfg[] = 'adjustX:0,adjustY:0';
        } elseif ($position == 'right') {
            $cfg[] = 'adjustX:15,adjustY:-6';
        } elseif ($position == 'bottom') {
            $cfg[] = 'adjustX:-6,adjustY:10';
        } elseif ($position == 'left') {
            $cfg[] = 'adjustX:-12,adjustY:-6';
        } elseif ($position == 'top') {
            $cfg[] = 'adjustX:-6,adjustY:-12';
        }

        if ($tintColor) {
            $cfg[] = "tint:'{$tintColor}',tintOpacity:{$tintOpacity}";
        }
        if ($softFocus) {
            $cfg[] = "softFocus:{$softFocus}";
        }
		
		return implode($cfg, ',');
	}
}
