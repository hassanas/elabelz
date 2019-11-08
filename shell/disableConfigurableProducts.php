<?php
/**
 * This script will run once and  Disable all those configurable products whose all child's have qty = 0 also disable those child's as well
 * Created by Hassan Ali Shahzad.
 * Date: 6/21/18
 * Time: 1:31 PM
 *
 *
 */
require_once 'abstract.php';

class Mage_Shell_DisableConfigurableProducts extends Mage_Shell_Abstract
{
    public function run()
    {

        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('memory_limit', '-1');
        Mage::register('isSecureArea', true);

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT 
                    cpe.entity_id AS parent_id, sku AS parent_sku,cpei1.value as parent_status_1_for_enable, cpsl.product_id as child_id ,csi.qty as child_qty, cpei.value as child_status_1_for_enable
                FROM
                    catalog_product_entity cpe
                
                    join catalog_product_entity_int cpei1
                    on (cpe.entity_id = cpei1.entity_id AND cpei1.attribute_id = 96 AND cpei1.store_id = 0  )
                    
                    join catalog_product_super_link cpsl
                    on cpe.entity_id = cpsl.parent_id
                    
                    join cataloginventory_stock_item csi
                    on cpsl.product_id = csi.product_id
                    
                    join catalog_product_entity_int cpei
                    on (cpsl.product_id = cpei.entity_id AND cpei.attribute_id = 96 AND cpei.store_id = 0 )
                    
                    where
                    cpe.type_id = 'configurable'
                order by cpe.entity_id";
        if($this->getArg('limit') && $this->getArg('limit') != ""){
            $limit = $this->getArg('limit');
            $sql .= " LIMIT ".$limit;
        }

        $products = $connection->fetchAll($sql);
        $parentProductId = 0;
        $processProduct = array();
        foreach($products as $product){
            // first time assignment
            if($parentProductId == 0){
                $parentProductId = $product['parent_id'];
            }
            if($parentProductId == $product['parent_id']){
                $processProduct[] = $product;
            }
            else{
                // start process
                $sumOfChildqty = array_sum(array_column($processProduct, 'child_qty'));
                if($sumOfChildqty == 0){
                    $idsToDisabled = array();
                    // disabled all children and parent
                    foreach($processProduct as $child){
                        if($child['child_status_1_for_enable'] == 1){
                            $idsToDisabled[] =  $child['child_id'];
                        }

                    }
                    if($child['parent_status_1_for_enable'] == 1){
                        $idsToDisabled[] =  $child['parent_id'];
                    }
                    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

                    if(count($idsToDisabled) > 0) {
                        foreach ($idsToDisabled as $id) {
                            if (!$this->getArg('dryrun')) { //if dryrun dont do anything just log
                                Mage::getModel('catalog/product_status')->updateProductStatus($id, Mage_Core_Model_App::ADMIN_STORE_ID, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                                $this->addProductReindex($id);
                            }
                            Mage::log('Product Status Disabled Id: ' . $id, null, 'product_disabled.log');
                        }
                        Mage::log('Above is parent ' . $id, null, 'product_disabled.log');
                        unset($idsToDisabled);
                    }
                }
                unset($processProduct);
                $processProduct[] = $product;
                $parentProductId  = $product['parent_id'];
            }

        }
        mail('hassan.ali@progos.org'," Completed!", 'Process completed check logs in file product_disabled.log');
    }

    public function addProductReindex( $productId ){
        $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "INSERT INTO {$catalogProductPartialindex} (product_id)
                    SELECT * FROM (SELECT '{$productId}') AS tmp
                    WHERE NOT EXISTS (
                            SELECT product_id FROM {$catalogProductPartialindex} WHERE product_id = '{$productId}'
                    ) LIMIT 1;";
        $write->query($sql);
        return;
    }

}

$obj = new Mage_Shell_DisableConfigurableProducts();
$obj->run();