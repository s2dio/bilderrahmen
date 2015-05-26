<?php

class Infortis_Ultimo_Model_System_Config_Source_Css_Background_Positionx
{
    public function toOptionArray()
    {
		return array(
			array('value' => 'left',	'label' => Mage::helper('ultimo')->__('left')),
            array('value' => 'center',	'label' => Mage::helper('ultimo')->__('center')),
            array('value' => 'right',	'label' => Mage::helper('ultimo')->__('right'))
        );
    }
}