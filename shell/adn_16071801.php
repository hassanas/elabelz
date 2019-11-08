<?php
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
die("Script stopped .. !");
/*
 * This script will remove any duplicate order from marketplace_commission table
 * Input: [$order_id ~ int]
*/

$order_id = 644;
$order = Mage::getModel('marketplace/commission')->getCollection()
->addFieldToFilter('product_amt', array("gt" => 0))
->addFieldToFilter('order_id', $order_id);

$count = 1;
foreach ($order as $row) {

	$data = array();
	foreach ($row->getData() as $key => $value) {
		$data[$key] = $value;
	}
	array_shift($data);
	$add = Mage::getModel('marketplace/commission')->setData($data);
	$add->save();
	$row->delete();
	$count++;
}

$order = Mage::getModel('marketplace/commission')->getCollection()
->addFieldToFilter('product_amt', array("eq" => 0))
->addFieldToFilter('order_id', $order_id);
foreach ($order as $row) {
	$row->delete();
}

$order = Mage::getModel('marketplace/commission')->getCollection()
->addFieldToFilter('product_amt', array("gt" => 0))
->addFieldToFilter('order_id', $order_id);
foreach ($order as $row) {
	$row->setIsSellerConfirmation('Yes')->save();
	$row->setIsBuyerConfirmation('Yes')->save();
	$row->setItemOrderStatus('ready')->save();
}

echo $count . " records are updated .. !! .... <br><br>";

