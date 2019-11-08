<?php
/**
 * User: imran
 * Date: 6/14/16
 * Time: 4:32 PM
 */
/**
 * This file shall run every hour from 11:15AM GST every hour to update substract the sales
 * It won't update any price, even if the price is included just ignore it
 */
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();

$resource = Mage::getSingleton('core/resource');
$write    = $resource->getConnection('core_write');
$write->query('SET foreign_key_checks = 0');

function logMsg($msg, $display = true)
{
    if ($display === true) echo $msg;
    Mage::log("aprgrp_stock_update_hr:" . $msg);
}

function settleUpStock($productId, $isConfigProduct = false, $qty = 0)
{
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
    $stockItem = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($productId);
    $stockItemId = $stockItem->getId();
    $stockItem->setData('manage_stock', 1);
    if ($isConfigProduct === false) {
        $newStock = $stockItem->getQty() + ($qty);
        $stockItem->setData('qty', $newStock);
    }
    if ($qty >= 1) {
        $stockItem->setData('is_in_stock', 1);
    } else {
        $stockItem->setData('is_in_stock', 0);
    }
    $stockItem->save();
}

$stockCsvFile = __DIR__ . '/../var/seller/apprgrp/stock_hr.csv';
if (!file_exists($stockCsvFile)) {
    logMsg("Place stock_hr.csv in apprgrp/stock_hr.csv in order to run this script.\n");
    exit();
}
$csvDataArray = array_map('str_getcsv', file($stockCsvFile));
foreach ($csvDataArray as $row => $rdata) {
    try {
        //skip first row
        if ($row == 0) continue;
        logMsg("Processing: " . print_r($rdata, true) . "\n");
        $products = Mage::getSingleton('catalog/product')
            ->getCollection()
            ->addAttributeToFilter('supplier_sku', array('eq', $rdata[0]));
        //apparel csv format barcode/sku, qty, price, location
        foreach ($products as $product) {
            logMsg("Updating stock for " . $product->getSku() . "...");
            settleUpStock($product->getId(), false, $rdata[1]);
            logMsg("done\n");
        }
    } catch (Exception $e) {
        logMsg("EXCEPTION>>>" . $e->getMessage()."\n");
        logMsg($e->getTraceAsString()."\n");
    }
}
$write->query('SET foreign_key_checks = 1');