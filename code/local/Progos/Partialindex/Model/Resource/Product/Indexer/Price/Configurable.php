<?php

class Progos_Partialindex_Model_Resource_Product_Indexer_Price_Configurable
    extends Mage_Catalog_Model_Resource_Product_Indexer_Price_Configurable
{
    

    /**
     * Reindex temporary (price result data) for defined product(s)
     *
     * @param int|array $entityIds
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price_Configurable
     */
    public function reindexEntity($entityIds)
    {
        if(Mage::getStoreConfig('progos_partialindex/index/clearConfigurableIds') && !empty($entityIds)) {
            if(!is_array($entityIds)) {
                $entityIds = array($entityIds);
            }
            Mage::getModel('partialindex/observer')->clearConfigurableIds($entityIds);
        }
        if(Mage::getStoreConfig('progos_partialindex/index/logIds')) {
            if(!is_array($entityIds)) {
                $entityIds = array($entityIds);
            }
            Mage::log('Product Ids: '. Date("Y-m-d H:i:s : "). ' '. implode(',',$entityIds), Zend_log::INFO, 'price_indexer_ids.log');
        }
        return parent::reindexEntity($entityIds);
    }

}
