<?php

$installer = $this;
$installer->startSetup();

$stores = Mage::helper('codevog_options')->getActiveStores();

Mage::getModel('cms/block')
    ->load('bottom_home_block')
    ->delete();

$categoriesBlock = Mage::getModel('cms/block')
    ->setTitle('Bottom home page block')
    ->setIdentifier('bottom_home_block')
    ->setStores($stores)
    ->setIsActive(true)
    ->setContent(
        '
            <div class="lb_bg_benefits">
                <h4>Benefit from our advantages</h4>
                <div class="lb_benefit">
                    <h3>Cheap shipping cost</h3>
                    <p>
                        <a href="#">
                            Flat shipping cost
                            <br>
                            in 60 contries
                        </a>
                    </p>
                </div>
                <div class="lb_benefit bg_b_2">
                    <h3>Made in Germany</h3>
                    <p>
                        Direct from the Producer
                        <br>
                        no trade
                    </p>
                </div>
                <div class="lb_benefit bg_b_3">
                    <h3>Fast delivery</h3>
                    <p>
                        We ship with DHL or DPD
                        <br>
                        within 24 hour
                    </p>
                </div>
                <div class="lb_benefit bg_b_4">
                    <h3>Secure Shopping</h3>
                    <p>
                        Your data will be transfer
                        <br>
                        encrypted
                    </p>
                </div>
                <div class="lb_benefit bg_b_5">
                    <h3>Now with Movie</h3>
                    <p>
                        Production from frames
                        <br>
                        and poster strips
                    </p>
                </div>
                <div class="lb_benefit bg_b_6">
                    <h3>Order form</h3>
                    <p>
                        You can order by Fax
                        <br>
                        with order form
                    </p>
                </div>
                <div class="lb_benefit bg_b_7">
                    <h3>Info-Blog</h3>
                    <p>
                        <a target="_blank" href="#">
                            All News on
                            <br>
                            Bilderrahmen-News.de
                        </a>
                    </p>
                </div>
                <div class="lb_benefit bg_b_8">
                    <h3>Info-Forum</h3>
                    <p>
                        <a target="_blank" href="#">
                            In our Support-Forum
                            <br>
                            you found help
                        </a>
                    </p>
                </div>
            </div>
        ')
    ->save();

Mage::getModel('cms/block')
    ->load('top_footer_block')
    ->delete();

$categoriesBlock = Mage::getModel('cms/block')
    ->setTitle('Top footer block')
    ->setIdentifier('top_footer_block')
    ->setStores($stores)
    ->setIsActive(true)
    ->setContent(
        '
            <div class="top-footer-block">
                <h3>Convenient and easy to pay</h3>
                <div class="lb_cash">
                    <a href="#"></a>
                </div>
            </div>
        ')
    ->save();

$installer->endSetup();