<?php

$store_id = 0;
$action = "view";

// bootstrap magento (default store)
require_once __DIR__ . '/../app/Mage.php';
umask(0);
$time_start = microtime(true);
Mage::app()->setCurrentStore($store_id); // TODO - 3

//echo 'loading store id: ' . $store_id . '<br />';


// set script timeout to 20 minutes
set_time_limit(9600);

// grab ALL configurable products in the store
$configurables = Mage::getModel('catalog/product')->getCollection()
    ->addAttributeToFilter('type_id', 'configurable')
    ->addAttributeToSelect('sku');

// store all simple products with prices different from the parent product
$mismatch = array();

$counter = 0;
foreach($configurables as $configurable) {
    // grab ALL simple product ids associated with the current configurable product
    foreach($configurable->getTypeInstance()->getUsedProductIds() as $id) {
        $simple = Mage::getModel('catalog/product')->load($id);

        if (!empty($simple->getPrice()) && $simple->getPrice() > 0.00) {
            echo ++$counter." - Updating... simple: {$simple->getSku()} ==> 0.00 ";
            try{
                //now remove it from simple product
                $simple->setPrice(0.00);
                $simple->addAttributeUpdate('visibility', false, $store_id);//change visibility to false
                $simple->setSpecialPrice('');
                $simple->setSpecialFromDate('');
                $simple->setSpecialToDate('');
                $simple->save();
            echo "updated!\n";
            } catch(Exception $e) {
                echo $e->getMessage()."\n";
            }
        }
    }
}
$time_end = microtime(true);
$execution_time = ($time_end - $time_start) / 60;//in minutes
echo "All done!!! in $execution_time minutes \n";

//print_r($mismatch);


