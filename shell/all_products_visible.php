<?php
//error_reporting(E_ALL | E_STRICT);
define('MAGENTO_ROOT', getcwd());
$mageFilename = MAGENTO_ROOT . '/../app/Mage.php';
require_once $mageFilename;
Mage::app();
$ids = Mage::getModel('catalog/product')->getCollection()->getAllIds();
Mage::getSingleton('catalog/product_action')->updateAttributes(
    $ids,
    array('status'=>1, 'visibility'=>4, 'seller_product_status' => "Approved"),
    0
);
