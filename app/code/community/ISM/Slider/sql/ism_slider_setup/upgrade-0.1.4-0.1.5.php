<?php

$installer = $this;
$installer->startSetup();

$stores = array(6, 7, 8, 9, 10);

$slides = array(
    array('title' => 'Titel Hier', 'image' => '1432632160_Banner_Sofa_en.jpg', 'description' => ' steht die Beschreibung', 'position' => 50, 'is_active' => 1),
    array('title' => 'Titel Hier', 'image' => '1432635278_Banner_Barockrahmen_en.jpg', 'description' => ' steht die Beschreibung', 'position' => 50, 'is_active' => 1),
);

Mage::getModel('slider/slider')
    ->setTitle('Home')
    ->setIdentifier('home_page_slider')
    ->setWidth(0)
    ->setHeight(0)
    ->setDuration(0)
    ->setFrequency(0)
    ->setAutoslide(1)
    ->setControls(1)
    ->setPagination(1)
    ->setIsActive(1)
    ->setStores($stores)
    ->setSlides($slides)
    ->save();

$installer->endSetup();
