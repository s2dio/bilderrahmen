<?php

class Infortis_Ultimo_Model_System_Config_Source_Design_Font_Size_Basic
{
    public function toOptionArray()
    {
		return array(
			array('value' => '12px',	'label' => Mage::helper('ultimo')->__('12 px')),
			array('value' => '13px',	'label' => Mage::helper('ultimo')->__('13 px')),
            array('value' => '14px',	'label' => Mage::helper('ultimo')->__('14 px'))
        );
    }
}