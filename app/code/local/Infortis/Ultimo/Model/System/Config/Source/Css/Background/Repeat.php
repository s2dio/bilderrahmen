<?php

class Infortis_Ultimo_Model_System_Config_Source_Css_Background_Repeat
{
    public function toOptionArray()
    {
		return array(
			array('value' => 'no-repeat',	'label' => Mage::helper('ultimo')->__('no-repeat')),
            array('value' => 'repeat',		'label' => Mage::helper('ultimo')->__('repeat')),
            array('value' => 'repeat-x',	'label' => Mage::helper('ultimo')->__('repeat-x')),
			array('value' => 'repeat-y',	'label' => Mage::helper('ultimo')->__('repeat-y'))
        );
    }
}