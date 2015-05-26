<?php

class Loewenstark_Galerieschiene_Model_System_Config_Source_Category extends Varien_Object {

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray() {

        
        $attributeSetId = Mage::getModel('eav/entity_attribute_set')
                ->load('sets', 'attribute_set_name')
                ->getAttributeSetId();

//Load product model collecttion filtered by attribute set id
        $products = Mage::getModel('catalog/product')
                ->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addAttributeToSelect('name')
                ->addFieldToFilter('type_id', 'configurable') 
                ->addFieldToFilter('attribute_set_id', $attributeSetId);

//process your product collection as per your bussiness logic
        if(count($products) > 0){
            $options[0] = array(
                 'value' => '',
                 'label' => ''
             );
             foreach ($products as $p) {
                 $options[] = array(
                     'value' => $p->getId(),
                     'label' => $p->getName()
                 );

             }
        }else{
            $options[0] = array(
                 'value' => '',
                 'label' => 'Keine Konfigurierbaren Artikel vorhanden'
             );
        }
        return $options;
    }

}
