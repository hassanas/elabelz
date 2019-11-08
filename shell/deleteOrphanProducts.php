<?php
/**
 * This script will run once and delete all those simple products which are not associated with any parent product
 * Created by Hassan Ali Shahzad.
 * Date: 11/05/2018
 * Time: 13:09
 * 
 */

require_once 'abstract.php';


class Mage_Shell_DeleteOrphanProducts extends Mage_Shell_Abstract
{

    public function run()
    {
        Mage::register('isSecureArea', true);
        try {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT 
                        entity_id,type_id, sku, cpr.parent_id
                    FROM
                        catalog_product_entity cpe
                            LEFT JOIN
                        catalog_product_relation cpr ON cpr.child_id = cpe.entity_id
                    WHERE
                            cpr.parent_id IS NULL
                            AND cpe.type_id != 'configurable'
                            AND cpe.updated_at <= '2017-12-31 23:59:59'";
            $orphanProducts = $connection->fetchAll($sql);

            foreach ($orphanProducts as $orphanProduct) {
                if (empty($orphanProduct['entity_id'])) continue;

                $product = Mage::getModel('catalog/product')->load($orphanProduct['entity_id']);
                if($this->getArg('withimages')){
                    $items = Mage::getModel("catalog/product_attribute_media_api")->items($product->getId());
                    foreach ($items as $item) {
                        Mage::log('File: ' . $item['file'], null, 'orphan_product_remove.log');
                        $MediaDir = Mage::getConfig()->getOptions()->getMediaDir();
                        $MediaCatalogDir = $MediaDir . DS . 'catalog' . DS . 'product';
                        $DirImagePath = str_replace("/", DS, $item['file']);
                        $DirImagePath = $DirImagePath;
                        // remove file from Dir
                        $io = new Varien_Io_File();
                        $io->rm($MediaCatalogDir . $DirImagePath);
                        Mage::getModel("catalog/product_attribute_media_api")->remove($product->getId(), $item['file']);
                    }
                }
                $product->delete();
                Mage::log('Deleted Product ID: ' . $product->getId(), null, 'orphan_product_remove.log');

            }
            echo "Process Completed";
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log($e->getMessage(), null, 'orphan_product_remove.log');
        }

        Mage::unregister('isSecureArea');
    }
}

$obj = new Mage_Shell_DeleteOrphanProducts();
$obj->run();