<?php
/**
 * User: imran
 * Date: 6/14/16
 * Time: 4:32 PM
 */
/**
 * This file shall run on 9:31AM every day to update whole stock of apparel group
 * It'll change both stock and the price, but the price changed here would be offer price not the actual price
 * Plus the price of the parent product would be changed not the child's (if parent exist)
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
    if($display === true) echo $msg;
    Mage::log("appr_config_stock_fix_tmp:".$msg);
}

function settleUpStock($productId, $isConfigProduct=false, $qty=0)
{
    $stockItem = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($productId);
    $stockItemId = $stockItem->getId();
    $stockItem->setData('manage_stock', 1);
    if ($isConfigProduct === false) {
        if($stockItem->getQty() <= 0) {
            $newStock = $stockItem->getQty() + ($qty);
        }
        $stockItem->setData('qty', $newStock);
    }
    if ($qty >= 1 || $newStock >= 1) {
        logMsg("Changed to in Stock\n");
        $stockItem->setData('is_in_stock', 1);
    } else {
        $stockItem->setData('is_in_stock', 0);
    }
    $stockItem->save();
}

$stockCsvFile = __DIR__.'/../var/seller/apprgrp/stock_9am.csv';
if(!file_exists($stockCsvFile)) {
    echo "Place stock_9am.csv in apprgrp/stock_9am.csv in order to run this script.\n";
    exit();
}

$csvDataArray = array_map('str_getcsv', file($stockCsvFile));
foreach($csvDataArray as $row=>$rdata) {
    //skip first row
    if($row == 0) continue;
    logMsg("Processing: ".print_r($rdata, true)."\n");
    $products = Mage::getSingleton('catalog/product')
        ->getCollection()
        ->addAttributeToFilter('supplier_sku',array('eq', $rdata[0]));
    //apparel csv format barcode/sku, qty, price, location
    foreach($products as $product) {
        try {
            logMsg("Updating stock for ".$product->getSku()."...");
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            settleUpStock($product->getId(), false, $rdata[1]);
            $priceProduct = $product;
            if($product->getTypeId() == "simple"){
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                settleUpStock($parent->getId(), true, $rdata[1]);
                if($parent) {
                    $priceProduct = $parent;
                }
            }
            if ($priceProduct->getPrice() % $rdata[2] != 1) {//only update the price if the value is different
                logMsg("PRICE UPDATED\n");
                $priceProduct->setSpecialPrice(($rdata[2]));

                $priceProduct->setSpecialFromDate(date('Y-m-d'));
                $priceProduct->setSpecialFromDateIsFormated(true);

                $priceProduct->setSpecialToDate(date('Y-m-d', strtotime('+1 day')));
                $priceProduct->setSpecialToDateIsFormated(true);

                $priceProduct->save();
            }
            logMsg("done\n");
        } catch (Exception $e) {
            logMsg("EXCEPTION>>>" . $e->getMessage()."\n");
            logMsg($e->getTraceAsString()."\n");
        }
    }
}

$write->query('SET foreign_key_checks = 1');

logMsg("Reindexing all now...\n");
$indexCollection = Mage::getModel('index/process')->getCollection();
foreach ($indexCollection as $index) {
    /* @var $index Mage_Index_Model_Process */
    $index->reindexAll();
}
logMsg("Reindexing completed!\n");
