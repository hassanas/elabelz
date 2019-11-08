<?php

chdir(dirname(__FILE__));

ini_set('memory_limit', '1024M');
ini_set('display_errors', 1);

require '../app/bootstrap.php';
require '../app/Mage.php';

Mage::app('admin')->setUseSessionInUrl(false);

umask(0);

/*
* This script will Update all categories and will set them to Is Anchor = Yes 
*/
$resource = Mage::getResourceModel('catalog/category');
$categories = Mage::getResourceModel('catalog/category_collection');

foreach($categories as $category) {
    $category->setStoreId(0);    // 0 for default scope (All Store Views)
    $category->setData('is_anchor', 1);
    $resource->saveAttribute($category, 'is_anchor');
    echo '.';
}