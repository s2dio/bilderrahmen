<?php

$installer = $this;
$installer->startSetup();

$stores = Mage::helper('codevog_options')->getActiveStores();
$configModel = new Mage_Core_Model_Config();
$theme = 'market';
$scope = 'stores';

foreach($stores as $store) {
    $configModel->saveConfig('design/package/name', $theme, $scope, $store);
    $configModel->saveConfig('design/theme/locale', $theme, $scope, $store);
    $configModel->saveConfig('design/theme/template', $theme, $scope, $store);
    $configModel->saveConfig('design/theme/skin', $theme, $scope, $store);
    $configModel->saveConfig('design/theme/layout', $theme, $scope, $store);
    $configModel->saveConfig('design/theme/default', $theme, $scope, $store);
}

$installer->endSetup();