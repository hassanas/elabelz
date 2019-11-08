<?php
/**
 * User: Naveed Abbas
 * Date: 03/28/18
 */
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
$model = Mage::getModel('emapi/emapi');
$count = $model->placeOrders();
echo 'Total of '.$count.' web orders were successfully updated.';