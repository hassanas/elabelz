<?php
class Progos_Partialindex_Model_Resource_Product_Index extends Mage_Core_Model_Mysql4_Abstract
{
    
    const PARTAIL_INDEXER_LOG_FILE = 'partial_indexer.log';
    
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('partialindex/product_index','product_id');
    }

    /**
     * List products to reindex
     *
     * @return $this
     */
    public function listProducts(Progos_Partialindex_Model_Product_Index $object) 
    {
        $connection = $this->_getReadAdapter();

        $fields = array('*');

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), $fields);

        $data = $connection->fetchRow($select);

        if($data) $object->setData($data);

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * Reindex Rewrite
     *
     * @return Progos_Partialindex_Model_Resource_Product_Index
     */
    public function reindexAll()
    {
        $this->_reindexUpdatedProducts();
        return $this;
    }
	/**
     * Ids of products which have been created, updated or deleted
     *
     * @return array
     */
    protected function _getProcessedProductIds()
    {
        $maxLimit = $this->_getProductLimit();
        $orderBy =  Mage::getStoreConfig('progos_partialindex/index/setOrder');
        $collection = Mage::getModel('partialindex/product_index')->getCollection()->distinct(true);
        $collection = $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('product_id as product_id');
        //$collection = $collection->order('sort_order DESC');

        if($orderBy) {
           $collection = $collection->order('sort_order ASC');
        }
        else {
           $collection = $collection->order('sort_order DESC');
        }
        if($maxLimit > 0){
           $collection = $collection->limit($maxLimit);
        }

        $connection = $this->_getReadAdapter();
        return $connection->fetchCol($collection);
    }
    /**
     * 
     * @return mixed
     */
    protected function _getProductLimit() 
    {
        $isActive = (int) Mage::getStoreConfig('progos_partialindex/timepriceindex/enableTimeRange');
        $maxLimit =  (int) Mage::getStoreConfig('progos_partialindex/index/maxProductReindexed');
        if($isActive) {
            $crntTime = Mage::getSingleton('core/date')->date('G') * 60 + Mage::getSingleton('core/date')->date('i');
            $frmTime = (int) Mage::getStoreConfig('progos_partialindex/timepriceindex/setFromTime');
            $toTime = (int) Mage::getStoreConfig('progos_partialindex/timepriceindex/setToTime');
            if($crntTime >= $frmTime && $crntTime <= $toTime) {
                $maxLimit = (int) Mage::getStoreConfig('progos_partialindex/timepriceindex/maxProductToReindexInTime');
            }
        }
        return $maxLimit;
    }
	/**
     * Partially reindex newly created and updated products
     *
     * @return Progos_Partialindex_Model_Resource_Product_Index
     */
    protected function _reindexUpdatedProducts()
    {
        $entityIds = $this->_getProcessedProductIds();
        if(empty($entityIds)) {
            return $this;
        }
	
        $event = Mage::getModel('index/event');
        $event->setNewData(array(
            'reindex_price_product_ids' => $entityIds, // for product_indexer_price
            'reindex_stock_product_ids' => $entityIds, // for indexer_stock
            'product_ids'               => $entityIds, // for category_indexer_product
            'reindex_eav_product_ids'   => $entityIds  // for product_indexer_eav
        ));
        $this->setLogForPartialIndexer('partial indexer started to update website date');
        Mage::getModel('partialindex/website')->updateWebsiteDate();
        $this->setLogForPartialIndexer('partial indexer ended to update website date');
        // Index our product entities.
        $this->setLogForPartialIndexer('partial indexer started the stock indexer');
        Mage::dispatchEvent('partialindex_reindex_products_before_indexer_stock', array('entity_id' => $entityIds));
        Mage::getResourceSingleton('cataloginventory/indexer_stock')->catalogProductMassAction($event);
        $this->setLogForPartialIndexer('partial indexer ended the stock indexer');
        
        if((int)Mage::getStoreConfig('progos_partialindex/index/priceindexer')) {
            $this->setLogForPartialIndexer('partial indexer started the price indexer');
            Mage::dispatchEvent('partialindex_reindex_products_before_product_indexer_price', array('entity_id' => $entityIds));
            Mage::getResourceSingleton('catalog/product_indexer_price')->catalogProductMassAction($event);
            $this->setLogForPartialIndexer('partial indexer ended the price indexer');
        }
        $this->setLogForPartialIndexer('partial indexer started the category indexer');
        Mage::dispatchEvent('partialindex_reindex_products_before_category_indexer_product', array('entity_id' => $entityIds));
        Mage::getResourceSingleton('catalog/category_indexer_product')->catalogProductMassAction($event);
        $this->setLogForPartialIndexer('partial indexer ended the category indexer');
        
        $this->setLogForPartialIndexer('partial indexer started the eav indexer');
        Mage::dispatchEvent('partialindex_reindex_products_before_product_indexer_eav', array('entity_id' => $entityIds));
        Mage::getResourceSingleton('catalog/product_indexer_eav')->catalogProductMassAction($event);
        $this->setLogForPartialIndexer('partial indexer ended the eav indexer');
        
        $this->setLogForPartialIndexer('partial indexer started the fulltetx indexer');
        Mage::dispatchEvent('partialindex_reindex_products_before_fulltext', array('entity_id' => &$entityIds));
        Mage::getResourceSingleton('catalogsearch/fulltext')->rebuildIndex(null, $entityIds);
        $this->setLogForPartialIndexer('partial indexer ended the fulltetx indexer');
        
        $this->setLogForPartialIndexer('partial indexer started the url rewrite indexer');
        Mage::dispatchEvent('partialindex_reindex_products_before_urlrewrite', array('entity_id' => $entityIds));
        /* @var $urlModel Mage_Catalog_Model_Url */
        $urlModel = Mage::getSingleton('catalog/url');

        $urlModel->clearStoreInvalidRewrites(); // Maybe some products were moved or removed from website
        foreach ($entityIds as $productId) {
                $urlModel->refreshProductRewrite($productId);
        }
        $this->setLogForPartialIndexer('partial indexer ended the url rewrite indexer');
        

        if((bool)Mage::getStoreConfig('progos_partialindex/index/enableProductFlatIndexer')) {
            $this->setLogForPartialIndexer('partial indexer started the flat indexer');
            Mage::dispatchEvent('partialindex_reindex_products_before_flat', array('entity_id' => $entityIds));
            Mage::getSingleton('catalog/product_flat_indexer')->saveProduct($entityIds);
            $this->setLogForPartialIndexer('partial indexer ended the flat indexer');
        }
        
        $this->clearCache($entityIds);
        
        Mage::dispatchEvent('partialindex_reindex_products_after', array('entity_id' => $entityIds));
        $this->setLogForPartialIndexer('partial indexer ended the update process table');
        return $this;
    }
    
    protected function setLogForPartialIndexer($message) 
    {
        Mage::log($message, null , self::PARTAIL_INDEXER_LOG_FILE);
        return $this;
    }
    
    private function clearCache($entityIds)
    {
        Mage::helper('fpccache')->clearCache($entityIds);
    }
}