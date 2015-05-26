<?php

class Infortis_Ultimo_Model_System_Config_Source_Css_Background_Positiony
{
    public function toOptionArray()
    {
		return array(
			array('value' => 'top',		'label' => Mage::helper('ultimo')->__('top')),
            array('value' => 'center',	'label' => Mage::helper('ultimo')->__('center')),
            array('value' => 'bottom',	'label' => Mage::helper('ultimo')->__('bottom'))
        );
    }
}