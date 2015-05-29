<?php

$installer = $this;
$installer->startSetup();

$stores = Mage::helper('codevog_options')->getActiveStores();

Mage::getModel('cms/block')
    ->load('left_contact_block')
    ->delete();

$categoriesBlock = Mage::getModel('cms/block')
    ->setTitle('Left contact block')
    ->setIdentifier('left_contact_block')
    ->setStores($stores)
    ->setIsActive(true)
    ->setContent(
        '
            <div class="lb_contact">
                <h4>free Consulting</h4>
                <div class="lb_phone">+4935872 32888</div>
                <p class="lb_text_11">
                    Support Monday to Friday:
                    <br>
                    08.00 - 16.00 PM
                </p>
                <div class="lb_space"></div>
                <div class="lb_bt small">
                    <a href="{{store url=\'contacts\'}}">Contact</a>
                </div>
            </div>
        ')
    ->save();

$installer->endSetup();