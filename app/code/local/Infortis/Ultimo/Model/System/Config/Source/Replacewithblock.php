<?php

class Infortis_Ultimo_Model_System_Config_Source_ReplaceWithBlock
{
    public function toOptionArray()
    {
		return array(
			array('value' => 0, 'label' => Mage::helper('ultimo')->__('Disable Completely')),
            array('value' => 1, 'label' => Mage::helper('ultimo')->__('Don\'t Replace With Static Block')),
            array('value' => 2, 'label' => Mage::helper('ultimo')->__('If Empty, Replace With Static Block')),
			array('value' => 3, 'label' => Mage::helper('ultimo')->__('Replace With Static Block'))
        );
    }
}