<?php

$installer = $this;
$installer->startSetup();

$stores = Mage::helper('codevog_options')->getActiveStores();
$configModel = new Mage_Core_Model_Config();
$logo = '{{secure_base_url}}skin/frontend/market/default/images/bilderrahmenshop24.jpg';
$scope = 'stores';

foreach($stores as $store) {
    $configModel->saveConfig('design/header/logo_src', $logo, $scope, $store);
}

$installer->endSetup();