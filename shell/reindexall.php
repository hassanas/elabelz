<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 9/8/16
 * Time: 3:06 PM
 */

require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();

function logMsg($msg, $display = true)
{
    if($display === true) echo $msg;
    Mage::log("aprgrp_stock_update_9am:".$msg);
}

logMsg("Reindexing all now...\n");
$indexCollection = Mage::getModel('index/process')->getCollection();
foreach ($indexCollection as $index) {
    /* @var $index Mage_Index_Model_Process */
    $index->reindexAll();
}
logMsg("Reindexing completed!\n");
