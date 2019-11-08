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

/*
$customers = Mage::getModel('customer/customer')->getCollection();

foreach ($customers as $_customer){
    $_orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id',$_customer->getId());
    if ($_orders->count()){
        $customer->setIsBuyer();
        $customer->save();
    }
}
*/