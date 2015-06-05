<?php

$installer = $this;
$installer->startSetup();

$stores = Mage::helper('codevog_options')->getActiveStores();
$configModel = new Mage_Core_Model_Config();
$logo = 'Copyright 2015 &copy; Neumann Bilderrahmen';
$scope = 'stores';

foreach($stores as $store) {
    $configModel->saveConfig('design/footer/copyright', $logo, $scope, $store);
}

$footerContent = '
    <div class="lb_footer">
        <div class="lb_overflow">
            <div class="lb_footer_box">
            <h2>Information</h2>
            <ul>
                <li>
                    <a href="#">Verpackungsrichtlinie</a>
                </li>
                <li>
                    <a href="#">AGB</a>
                </li>
                <li>
                    <a href="#">Impressum</a>
                </li>
                <li>
                    <a href="#">Widerruf</a>
                </li>
                <li>
                    <a href="#">Datenschutz</a>
                </li>
            </ul>
            </div>
        <div class="lb_footer_box">
            <h2>Service</h2>
            <ul>
                <li>
                    <a href="#">FAQs - Häufige Fragen</a>
                </li>
                <li>
                    <a href="#">Newsletter</a>
                </li>
                <li>
                    <a href="#">Liefer- & Versandkosten</a>
                </li>
                <li>
                    <a href="#">Zahlungsarten</a>
                </li>
                <li>
                    <a href="{{store url=\'catalog/seo_sitemap/category\'}}">Sitemap</a>
                </li>
            </ul>
        </div>
        <div class="lb_footer_box">
            <h2>Über uns</h2>
            <ul>
                <li>
                    <a href="#">Unser Unternehmen</a>
                </li>
                <li>
                    <a href="{{store url=\'checkout/onepage\'}}">Bestellen per Bestellschein</a>
                </li>
                <li>
                    <a href="{{store url=\'contacts\'}}">Kontakt</a>
                </li>
                <li>
                    <a href="#">Partnerlinks</a>
                </li>
                <li>
                    <a target="_blank" href="#">Info-Forum</a>
                </li>
            </ul>
        </div>
        <div class="lb_footer_box">
            <h2>Social Media</h2>
            <div class="lb_social">
                <a target="_blank" href="#">
                    <img alt="facebook" src="{{media url=\'wysiwyg/facebook.png\'}}">
                </a>
                <a target="_blank" href="#">
                    <img alt="twitter" src="{{media url=\'wysiwyg/twitter.png\'}}">
                </a>
                <a target="_blank" href="#">
                    <img alt="googleplus" src="{{media url=\'wysiwyg/googleplus.png\'}}">
                </a>
                <a target="_blank" href="#">
                    <img alt="youtube" src="{{media url=\'wysiwyg/youtube.png\'}}">
                </a>
                <a target="_blank" href="#">
                    <img alt="xing" src="{{media url=\'wysiwyg/xing.png\'}}">
                </a>
                </div>
                <img alt="Europa fördert Sachsen - EFRE" src="{{media url=\'wysiwyg/efre.jpg\'}}">
            </div>
        </div>
    </div>';

Mage::getModel('cms/block')
    ->load('bilderrahmen_footer_links')
    ->delete();

$categoriesBlock = Mage::getModel('cms/block')
    ->setTitle('Bilderrahmen footer links')
    ->setIdentifier('bilderrahmen_footer_links')
    ->setStores($stores)
    ->setIsActive(true)
    ->setContent($footerContent)
    ->save();

$installer->endSetup();