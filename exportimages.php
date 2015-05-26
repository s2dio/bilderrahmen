<?php 
$adress='http://holzrahmen24.de/media/catalog/product';
foreach(file('images-list.txt') as $row){
	$row = trim($row);
	$path = dirname(str_replace($adress,'', $row));
	mkdir('media/catalog/product'.$path,0755, true);
	echo 'cd media/import'.$path."\n";
	shell_exec('cd media/catalog/product'.$path.' && wget -c '.$row);
	echo $row."\n";
	

}

