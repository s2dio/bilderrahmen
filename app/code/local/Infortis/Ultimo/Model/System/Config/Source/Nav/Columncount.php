<?php

class Infortis_Ultimo_Model_System_Config_Source_Nav_ColumnCount
{
    public function toOptionArray()
    {
        return array(
			array('value' => 4, 'label' => Mage::helper('ultimo')->__('4 Columns')),
            array('value' => 5, 'label' => Mage::helper('ultimo')->__('5 Columns')),
			array('value' => 6, 'label' => Mage::helper('ultimo')->__('6 Columns')),
			array('value' => 7, 'label' => Mage::helper('ultimo')->__('7 Columns')),
			array('value' => 8, 'label' => Mage::helper('ultimo')->__('8 Columns'))
        );
    }
}