<?php

Mage::register('isSecureArea', 1);
Mage::app()->setUpdateMode(false);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$installer = $this;

$installer->startSetup();

$setup = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');
$setup->startSetup();

$setup->addAttribute('catalog_category', 'top_menu', array(
    'group'                     => 'General Information',
    'input'                     => 'select',
    'source'                    => 'eav/entity_attribute_source_boolean',
    'type'                      => 'int',
    'label'                     => 'Top menu',
    'backend'                   => '',
    'visible'                   => true,
    'required'                  => false,
    'wysiwyg_enabled'           => false,
    'visible_on_front'          => false,
    'is_html_allowed_on_front'  => false,
    'default'                   => 1,
    'sort_order'                => '999',
    'global'                    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$topCategories = array(86, 87, 93, 95, 110);

foreach($topCategories as $categoryId) {
    $category = Mage::getModel('catalog/category')->load($categoryId);
    $category->setTopMenu(Mage_Eav_Model_Entity_Attribute_Source_Boolean::VALUE_YES)
        ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
        ->save();
}

$setup->endSetup();

$installer->endSetup();