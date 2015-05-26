<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @package    Billpay
 * @author 	   Jan Wehrs <jan.wehrs@billpay.de>
 * @copyright  Copyright (c) 2009 Billpay GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Billpay_CheckController extends Mage_Core_Controller_Front_Action {

	private static $_MODULE_NAME = 'Billpay';

	/**
	 * Get Api helper
	 *
	 * @return Billpay_Helper_Api
	 */
    public function getApi() {
        return Mage::helper('billpay/api');
    }

	public function indexAction() {
		$mode = $this->getApi()->getConfigData('account/transaction_mode');

		if ($mode == Billpay_Helper_Api::TRANSACTION_MODE_TEST) {

			try {
				$xml = simplexml_load_file(BP . '/app/code/community/Billpay/etc/config.xml');
			}
			catch (Exception $e) {
				echo 'error loading config.xml';
				die;
			}

			$rewriteNodes = $xml->xpath('//rewrite');
			$version = $xml->xpath('//version');
			$tmp_path = $xml->xpath('//temp_path');

			echo '<table><tr>';
			echo '<th style=\'width:300px\'>Path</th>';
			echo '<th style=\'width:400px\'>Rewrite Name</th>';
			echo '<th style=\'width:400px\'>Active Class</th>';
			echo '<th style=\'width:100px\'>Rewrite Status</th>';
			echo '</tr>';

			foreach ($rewriteNodes as $n) {
				$nParent = $n->xpath('..');
				$module = (string)$nParent[0]->getName();
				$nParent2 = $nParent[0]->xpath('..');
				$component = (string)$nParent2[0]->getName();
				$pathNodes = $n->children();

				foreach ($pathNodes as $pathNode) {
					$path = (string) $pathNode->getName();
					$completePath = $module . '/' . $path;

					echo '<tr>';
					$rewriteClassName = (string) $pathNode;
					if (substr($rewriteClassName, 0, 7) == self::$_MODULE_NAME) {
						echo '<td>' . $completePath . '</td>';
						echo '<td>' . $rewriteClassName . '</td>';

						if ($component == 'models') {
							$instance = Mage::getSingleton($completePath);
						}
						else if ($component == 'blocks') {
							$instance = $this->getLayout()->createBlock($completePath);
						}
						else {
							echo 'cannot handle ' .$component;
						}

						echo '<td>' . get_class($instance) . '</td>';

						if ($instance instanceof $rewriteClassName) {
							echo '<td>Ok</td>';
						}
						else {
							echo '<td style=\'color:red;font-weight:bold\'>Not Ok</td>';
						}
					}
					echo '</tr>';
				}
			}

			echo '</table>';
			echo "<br /><hr /><br />";

			$tmp_path = $_SERVER['DOCUMENT_ROOT'] . "/" . (string)$tmp_path[0];
			if(file_exists($tmp_path) && is_dir($tmp_path) && is_writable($tmp_path))
			{
			     $tmp_path_chek = "OK";
			}
			else
			{
			   if(!file_exists($tmp_path))
			   {
			       if(mkdir($tmp_path, 0777,true))
			       {
			           $tmp_path_chek = "OK";
			       }
			       else
			       {
			           $tmp_path_chek = "<span style='color:red;font-weight:bold'>Not Ok</span>";
			       }

			   }
			   else
			   {
			           $tmp_path_chek = "<span style='color:red;font-weight:bold'>Not Ok</span>";
			   }
			}
			echo "<table><thead><tr><th style='width:200px'>Module Version</th><th style='width:200px'>Temp dir check</th></tr></thead>";
			echo "<tbody><tr><td>".(string)$version[0]."</td><td>$tmp_path_chek</td></tr></tbdoy>";
			echo '</table><pre>';
			//print_r($_SERVER[DOCUMENT_ROOT]);

		}
		else {
			Mage::app()->getResponse()->setHttpResponseCode(404);
		}
	}

}