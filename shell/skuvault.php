<?php
/*
 * Hassan Ali Shahzad
 * Used to ping crone function for testing
 * */
error_reporting(E_ALL);
require_once __DIR__ . '/../app/Mage.php';
// calling model cron
$obj = new Progos_Skuvault_Model_Cron();
$obj->productBrandSyncWithSkuvaultSupplier();
