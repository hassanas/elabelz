<?php
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
$resource = Mage::getSingleton('core/resource');
$connection = $resource->getConnection('core_write');

try {
    $q = "UPDATE `catalog_product_super_attribute_label` SET value='Color' WHERE value='Array';";
    if ($effectedRows = $connection->exec($q)) {
        echo "Updated $effectedRows rows.\n";
    } else {
        echo "Nothing to update.\n";
    }
} catch(Exception $e) {
    Mage::logException($e);
}