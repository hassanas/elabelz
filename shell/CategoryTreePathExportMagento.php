<?php

define('MAGENTO', realpath(dirname(__FILE__)));
require_once MAGENTO . '/../app/Mage.php';
Mage::app();

$category = Mage::getModel ('catalog/category');
$tree = $category->getTreeModel();
$tree->load();

$ids = $tree->getCollection()->getAllIds();

$categories = array();

$x = 0;

if ($ids) {

	$file = "var/import/catwithid.csv";
	file_put_contents($file,'"id","name","path","path_ids","url"' . PHP_EOL);

	foreach ( $ids as $id ) {

		$url_key = Mage::helper('catalog/category')->getCategoryUrlPath($category->getUrlPath(), true);

		$path = explode('/', $categories[$id]['path']);
        $fpath = '';

        foreach ($path as $pathId) {
            $fpath .= $categories[$pathId]['name'] . '/';
        }

        $path_ids = implode(',', $path);

	  	$string = '"' . $id . '","' . $category->load($id)->getName() . '","' . $fpath . '","' . $path_ids . '"' . $url_key . '"' . PHP_EOL;
		
		file_put_contents($file,$string,FILE_APPEND);

	}
}
