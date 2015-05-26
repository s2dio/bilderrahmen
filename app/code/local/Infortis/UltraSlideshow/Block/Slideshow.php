<?php
class Infortis_UltraSlideshow_Block_Slideshow extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
	public function getImgUrls()
	{
		$h = Mage::helper('ultraslideshow');
		
		//Get slides path (relative to 'media'), trim slashes. If path specified: append to 'media' and append slash.
		$slidesUrl = $mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
		$dir = trim($h->getCfg('general/directory'), "/");
		if ($dir != '')
			$slidesUrl .= $dir . "/";
		
		//Get filenames separated with commas
		$fileNames = explode(",", $h->getCfg('general/files'));
		$fileUrls = array();
		foreach ($fileNames as $filename)
		{
			if ( ($trimmedFilename = trim($filename)) != '' )
				$fileUrls[] = $slidesUrl . $trimmedFilename;
		}

		return $fileUrls;
	}
	
	public function getStaticBlockIds()
	{
		$h = Mage::helper('ultraslideshow');
		
		$blockIdsString = $h->getCfg('general/blocks');
		$blockIds = explode(",", str_replace(" ", "", $blockIdsString));
		return $blockIds;
	}
}