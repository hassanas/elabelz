<?php

class Progos_Partialindex_Model_Resource_Category_Indexer_Product extends Mage_Catalog_Model_Resource_Category_Indexer_Product
{
   
    /**
     * Add product association with root store category for products which are not assigned to any another category
     *
     * @param int | array $productIds
     * @return Mage_Catalog_Model_Resource_Category_Indexer_Product
     */
    protected function _refreshRootRelations($productIds)
    {        
        parent::_refreshRootRelations($productIds);
        return $this;
    }
}
