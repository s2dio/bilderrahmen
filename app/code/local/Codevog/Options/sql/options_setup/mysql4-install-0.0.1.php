<?php

$installer = $this;
$installer->startSetup();

Mage::register('isSecureArea', 1);
Mage::app()->setUpdateMode(false);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$pageId = 'index';
$stores = Mage::helper('codevog_options')->getActiveStores();

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

Mage::getModel('cms/block')
    ->load('home_description')
    ->delete();

$categoriesBlock = Mage::getModel('cms/block')
    ->setTitle('Home page description')
    ->setIdentifier('home_description')
    ->setStores($stores)
    ->setIsActive(true)
    ->setContent(
        '
            <div class="home-description">
                <h1>Shopping frames cheaply and safely</h1>
                <p>
                    Welcome to the online shop for picture and frames, wooden mouldings and picture hanging systems. You will find a wide selection of frames from a variety of materials, colors and profiles. Make your selection from the frameless pictures carrier, aluminum frame, plastic frame to picture frame made of solid wood. The frame profiles go from classic modern to antique rustic. Benefit from 20 years of experience in timber frame, picture frames and mounts with flexible picture hanging rails or Posterstrips. You will also receive framed pictures, posters, key boxes and jewelry boxes in our store. Of course you can find mounts and accessories required.
                    <strong>Buy all products directly from the manufacturer.</strong>
                    Your data is transmitted by SSL security. More than 20.000 wooden frames are permanently in stock. The production of special sizes is part of our daily business. Upon request, we will produce your strips as blunt cuts or mitred, or as an empty frame complete with glass and rear bord. We will ship your merchandise daily in more than 60 countries. We wish you a pleasant stay and hope to welcome you again soon as possible on PictureframeShop24.
                </p>
            </div>
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
            {{block type="templateslider/slider" template="catalog/product/topsellers.phtml"}}
            {{block type="cms/block" block_id="home_description"}}
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