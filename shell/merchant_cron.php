<?php

//Load Magento API
ini_set('memory_limit','-1');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('app/Mage.php'); //Path to Magento
umask(0);
Mage::app();
//First we load the model
$model = Mage::getModel('progos_merchandising/cron');

//Then execute the task
$model->savePendingProductPositions();