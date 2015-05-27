<?php

$installer = $this;
$installer->startSetup();

Mage::register('isSecureArea', 1);
Mage::app()->setUpdateMode(false);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$pageId = 'index';
$stores = array(6, 7, 8, 9, 10);

Mage::getModel('cms/block')
    ->load('home_category')
    ->delete();

$categoriesBlock = Mage::getModel('cms/block')
    ->setTitle('Category in home page')
    ->setIdentifier('home_category')
    ->setStores($stores)
    ->setIsActive(true)
    ->setContent(
        '
            <p>{{block type="featuredcategories/display" template="sfc_featuredcategories/display.phtml"}}</p>
        ')
    ->save();


Mage::getModel('cms/page')
    ->load($pageId, 'identifier')
    ->delete();

$homePage = Mage::getModel('cms/page');

$homePageData = array(
    'title' => 'Home Bilderrahmen',
    'root_template' => 'two_columns_left',
    'identifier' => $pageId,
    'is_active' => true,
    'stores' => $stores,
    'content' => '
        <p>
            {{widget type="slider/slider" slider_id="home_page_slider"}}
            {{widget type="cms/widget_block" template="cms/widget/static_block/default.phtml" block_id="'.$categoriesBlock->getId().'"}}
        </p>',
);

$homePage->setData($homePageData)
    ->save();

$configModel = new Mage_Core_Model_Config();
foreach($stores as $store) {
    $configModel->saveConfig('web/default/cms_home_page', $pageId, 'stores', $store);
}

$categoryIds = array(
    101 => 'kat-klapprahmen.jpg',
    102 => 'kat-ovalrahmen.jpg',
    103 => 'kat-passepartout.jpg',
    104 => 'kat-posterschienen.jpg',
    105 => 'kat-zubehoer.jpg',
    83  => 'Alu-Rahmen.jpg',
    84  => 'Aufsteller.jpg',
    85  => 'Barockrahmen.jpg',
    86  => 'Bilderleisten.jpg',
    93  => 'Holzrahmen_1.jpg',
    94  => 'kat-digitale-bilderrahmen.jpg',
    95  => 'Galerieschienen.jpg'
);
foreach($categoryIds as $categoryId => $image) {
    $category = Mage::getModel('catalog/category')->load($categoryId);
    $category->setImage($image)
        ->setIsFeaturedCategory(1)
        ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
        ->save();
}

$installer->endSetup();