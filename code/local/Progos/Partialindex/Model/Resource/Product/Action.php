<?php
class Progos_Partialindex_Model_Resource_Product_Action extends Mage_Catalog_Model_Resource_Product_Action
{
    public function updateAttributes($entityIds, $attrData, $storeId)
    {
        parent::updateAttributes($entityIds, $attrData, $storeId);
        if(Mage::app()->getRequest()->getActionName()=='massStatus') {
            $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            foreach ($entityIds as  $productId) {
            $sql = "INSERT INTO {$catalogProductPartialindex} (product_id)
                        SELECT * FROM (SELECT '{$productId}') AS tmp
                        WHERE NOT EXISTS (
                                SELECT product_id FROM {$catalogProductPartialindex} WHERE product_id = '{$productId}'
                        ) LIMIT 1;";
                    $write->query($sql);
            }
        }
        return $this;
    }
}
