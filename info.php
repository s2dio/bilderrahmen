<?php
chdir(dirname(__FILE__));
require dirname(__FILE__).'/app/Mage.php';
Mage::app('admin');

$connection = Mage::getSingleton('core/resource')->getConnection('core_read');

print_r($row);
$sql = "UPDATE core_config_data set value=\"http://www.galerieschiene.net/\" where config_id=5"; 
$connection->query($sql);


$sql = "SELECT * FROM core_config_data WHERE value LIKE \"http%\"";
$row = $connection->fetchAll($sql);
echo '<pre>'.print_r($row,1)
?>
