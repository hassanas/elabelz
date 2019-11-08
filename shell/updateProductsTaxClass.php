<?php
require_once 'abstract.php';

/**
 *  One Time usage:
 *  This script will update all products which are not assigned any Tax class to taxable(id:2)
 *
 * Created by Hassan Ali Shahzad
 * Date: 14/14/2017
 * Time: 10:50
 */
class Progos_Shell_UpdateProductsTaxClass extends Mage_Shell_Abstract
{
    public function run()
    {
        ini_set('memory_limit', '4096M');
        $productIds = Mage::getResourceModel('catalog/product_collection')->getAllIds();
        try {
            Mage::getSingleton('catalog/product_action')->updateAttributes(
                $productIds,
                array('tax_class_id' => 2),
                0
            );
        } catch (Exception $e) {
            Mage::log( $e->getMessage() , null, 'update_product_tax.log');
        }
    }
}
$updateProductsTaxClass = new Progos_Shell_UpdateProductsTaxClass();
$updateProductsTaxClass->run();
echo "Process Finished\n";