<?php
/**
 * User: Naveed
 * Date: 02/16/18
 */
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();

$model = Mage::getModel('ecoprocessor/ecoprocessor');
$count = $model->placeOrders( $weborderIds );
echo 'Total of '.$count.' web orders were successfully updated.';