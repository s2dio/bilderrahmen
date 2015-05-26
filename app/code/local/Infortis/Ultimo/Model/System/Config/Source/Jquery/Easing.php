<?php

class Infortis_Ultimo_Model_System_Config_Source_Jquery_Easing
{
    public function toOptionArray()
    {
        return array(
			//Ease in-out
			array('value' => 'easeInOutSine',	'label' => Mage::helper('ultimo')->__('easeInOutSine')),
			array('value' => 'easeInOutQuad',	'label' => Mage::helper('ultimo')->__('easeInOutQuad')),
			array('value' => 'easeInOutCubic',	'label' => Mage::helper('ultimo')->__('easeInOutCubic')),
			array('value' => 'easeInOutQuart',	'label' => Mage::helper('ultimo')->__('easeInOutQuart')),
			array('value' => 'easeInOutQuint',	'label' => Mage::helper('ultimo')->__('easeInOutQuint')),
			array('value' => 'easeInOutExpo',	'label' => Mage::helper('ultimo')->__('easeInOutExpo')),
			array('value' => 'easeInOutCirc',	'label' => Mage::helper('ultimo')->__('easeInOutCirc')),
			array('value' => 'easeInOutElastic','label' => Mage::helper('ultimo')->__('easeInOutElastic')),
			array('value' => 'easeInOutBack',	'label' => Mage::helper('ultimo')->__('easeInOutBack')),
			array('value' => 'easeInOutBounce',	'label' => Mage::helper('ultimo')->__('easeInOutBounce')),
			//Ease out
			array('value' => 'easeOutSine',		'label' => Mage::helper('ultimo')->__('easeOutSine')),
			array('value' => 'easeOutQuad',		'label' => Mage::helper('ultimo')->__('easeOutQuad')),
			array('value' => 'easeOutCubic',	'label' => Mage::helper('ultimo')->__('easeOutCubic')),
			array('value' => 'easeOutQuart',	'label' => Mage::helper('ultimo')->__('easeOutQuart')),
			array('value' => 'easeOutQuint',	'label' => Mage::helper('ultimo')->__('easeOutQuint')),
			array('value' => 'easeOutExpo',		'label' => Mage::helper('ultimo')->__('easeOutExpo')),
			array('value' => 'easeOutCirc',		'label' => Mage::helper('ultimo')->__('easeOutCirc')),
			array('value' => 'easeOutElastic',	'label' => Mage::helper('ultimo')->__('easeOutElastic')),
			array('value' => 'easeOutBack',		'label' => Mage::helper('ultimo')->__('easeOutBack')),
			array('value' => 'easeOutBounce',	'label' => Mage::helper('ultimo')->__('easeOutBounce')),
			//Ease in
			array('value' => 'easeInSine',		'label' => Mage::helper('ultimo')->__('easeInSine')),
			array('value' => 'easeInQuad',		'label' => Mage::helper('ultimo')->__('easeInQuad')),
			array('value' => 'easeInCubic',		'label' => Mage::helper('ultimo')->__('easeInCubic')),
			array('value' => 'easeInQuart',		'label' => Mage::helper('ultimo')->__('easeInQuart')),
			array('value' => 'easeInQuint',		'label' => Mage::helper('ultimo')->__('easeInQuint')),
			array('value' => 'easeInExpo',		'label' => Mage::helper('ultimo')->__('easeInExpo')),
			array('value' => 'easeInCirc',		'label' => Mage::helper('ultimo')->__('easeInCirc')),
			array('value' => 'easeInElastic',	'label' => Mage::helper('ultimo')->__('easeInElastic')),
			array('value' => 'easeInBack',		'label' => Mage::helper('ultimo')->__('easeInBack')),
			array('value' => 'easeInBounce',	'label' => Mage::helper('ultimo')->__('easeInBounce')),
			//No easing
			array('value' => 'null',			'label' => Mage::helper('ultimo')->__('No easing'))
        );
    }
}
