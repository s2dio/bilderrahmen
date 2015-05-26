<?php

class Infortis_Ultimo_Model_System_Config_Source_Element_Display
{
    public function toOptionArray()
    {
		return array(
			array('value' => 0, 'label' => Mage::helper('ultimo')->__('Don\'t Display')),
            array('value' => 1, 'label' => Mage::helper('ultimo')->__('Display On Hover')),
            array('value' => 2, 'label' => Mage::helper('ultimo')->__('Display'))
        );
    }
}