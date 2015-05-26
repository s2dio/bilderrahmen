<?php

class Infortis_Ultimo_Helper_Template extends Mage_Core_Helper_Abstract
{
	/**
	 * Get HTML for "Add to" links.
	 *
	 * @param product object
	 * @param URL of the "Add to compare" link
	 * @param additional CSS class name
	 * @return string
	 */
	public function getCategoryAddtoLinks($_product, $_compareUrl, $class = '')
	{
		$html = '';

		if (Mage::helper('wishlist')->isAllow())
		{
			$html .= '<li><a href="' . Mage::helper('wishlist')->getAddUrl($_product) . '" class="link-wishlist" title="' . $this->__('Add to Wishlist') . '">' . $this->__('Add to Wishlist') . '</a></li>';
		}
		
		if ($_compareUrl)
		{
			$html .= '<li><a href="' . $_compareUrl . '" class="link-compare" title="' . $this->__('Add to Compare') . '">' . $this->__('Add to Compare') . '</a></li>';
		}
		
		//If any link rendered
		if (!empty($html))
		{
			//TRICKY: HTML is not appended here, but reorganized
			$html = '<ul class="add-to-links clearer ' . $class .'">' . $html . '</ul>';
		}

		return $html;
	}
	
//	public function getCategoryAddtoLinksOnimage($gc, $_product, $_compareUrl, $class = '')
//	{
//		$html = '';
//		
//		if ($gc['display_addtolinks'] != 0 && $gc['addtolinks_simple'])
//		{
//			if ($gc['display_addtolinks'] == 1) //Display on hover
//				$html = $this->getCategoryAddtoLinks($_product, $_compareUrl, 'addto-onimage' . ' ' . $class);
//			else //Always display
//				$html = $this->getCategoryAddtoLinks($_product, $_compareUrl, 'addto-onimage');
//		}
//		
//		return $html;
//	}
	
}
