<?php

class Progos_Partialindex_Model_Observer
{
    const SPECIAL_PRICES = 'special_prices.log';
    const PARTAIL_INDEXER_LOG_FILE = 'partial_indexer.log';
            
    public function addProductReindex(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
	if($product) {
            $productId = $product->getId();
            $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql = "INSERT INTO {$catalogProductPartialindex} (product_id)
                    SELECT * FROM (SELECT '{$productId}') AS tmp
                    WHERE NOT EXISTS (
                            SELECT product_id FROM {$catalogProductPartialindex} WHERE product_id = '{$productId}'
                    ) LIMIT 1;";
            Mage::log($sql,null,'requete.log',true);
            $write->query($sql);
        }
	return;
    }
	
    public function emptyPartialIndex(Varien_Event_Observer $observer)
    {
        $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "TRUNCATE TABLE {$catalogProductPartialindex};";
        $write->query($sql);
	return;
    }

    public function launchPartialReindex()
    {
        try {
            $isActive = (int) Mage::getStoreConfig('progos_partialindex/index/isActive');
            if($isActive) {
                $process = Mage::getSingleton('index/indexer')->getProcessByCode("partialindex_product");
                $process->reindexEverything();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            Mage::log($e->getMessage(), '', self::PARTAIL_INDEXER_LOG_FILE);
        }
        return;
    }
	
    public function cleanPartialIndex(Varien_Event_Observer $observer)
    {
        $entityIds = $observer->getEvent()->getEntityId();
        if(!empty($entityIds)){
            $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql = "DELETE FROM {$catalogProductPartialindex} WHERE product_id IN (".implode(',',$entityIds).")";
            $write->query($sql);
        }
        return;
    }
    
    public function clearConfigurableIds($entityIds) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "DELETE FROM catalog_product_index_price_tmp WHERE entity_id IN (".implode(',',$entityIds).")";
        $write->query($sql);
        return;
    }
    
    public function updateMassAttributes() {
        
        $productIds = $this->_getHelper()->getProductIds();
        if (!is_array($productIds)) {
            return ;
        } else if (!Mage::getModel('catalog/product')->isProductsHasSku($productIds)) {
            return ;
        }
        $this->insertEntityIds($productIds);
        return;
    }
    
    public function addProductId() 
    {
        $productId = Mage::app()->getRequest()->getParam('product_id');
        if(!empty($productId) && is_numeric($productId)) {
            $this->insertEntityIds(array($productId => $productId));
        }
    }
    
    protected function _getHelper() {
        return Mage::helper('adminhtml/catalog_product_edit_action_attribute');
    }

    public function insertEntityIds($entityIds) {
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
        return $this;
    }
    
    protected function launchReindexer($entityIds) 
    {
        Mage::getModel('partialindex/website')->updateWebsiteDate();
        $event = Mage::getModel('index/event');
        $event->setNewData(array(
            'reindex_price_product_ids' => $entityIds 
        ));
        Mage::dispatchEvent('partialindex_reindex_products_before_product_indexer_price', array('entity_id' => $entityIds));
        Mage::getResourceSingleton('catalog/product_indexer_price')->catalogProductMassAction($event);
        return $this;
    }
    
    public function reindexSpecialPriceProducts() 
    {
        try {
            $entityIds = $this->_getProcessedSpecialPriceIds();
            if(!empty($entityIds)) {
                $this->launchReindexer($entityIds);
                Mage::helper('fpccache')->clearCache($entityIds);
            }
            return $this;
        } catch (Exception $e) {
            echo $e->getMessage();
            Mage::log($e->getMessage(), '', self::SPECIAL_PRICES);
        }
    }
    public function startSpecialPriceProducts() 
    {
        try {
            $entityIds = $this->_getProcessedSpecialPriceIds(1);
            if(!empty($entityIds)) {
                $this->launchReindexer($entityIds);
                Mage::helper('fpccache')->clearCache($entityIds);
            }
            return $this;
        } catch (Exception $e) {
            echo $e->getMessage();
            Mage::log($e->getMessage(), '', self::SPECIAL_PRICES);
        }
    }
    
    protected function _getProcessedSpecialPriceIds($start = 0) 
    {
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('entity_id');
        if($start != 1) {
            $now = Mage::getSingleton('core/date')->gmtDate('Y-m-d 00:00:00', strtotime($now." -1 days"));
            $collection->addAttributeToFilter('special_to_date', array('eq'=> $now));
        }
        else {
            $now = Mage::getSingleton('core/date')->gmtDate('Y-m-d 00:00:00');
            $collection->addAttributeToFilter('special_from_date', array('eq'=> $now));
        }
        return $collection->getAllIds();
    }
    
    public function prepareMassAction(Varien_Event_Observer $observer)
    {
        $productGridBlock = $observer->getEvent()->getBlock();
        $productGridBlock->getMassactionBlock()->addItem('partialindexer', array(
            'label'=> Mage::helper('partialindex')->__('Add To Partial Indexer'),
            'url'  => $productGridBlock->getUrl('*/*/massPartialIndexer', array('_current'=>true)),
            'additional' => array(
                    'visibility' => array(
                        'name' => 'partialindexer',
                        'type' => 'text',
                        'class' => 'required-entry',
                        'label' => Mage::helper('partialindex')->__('priority'),
                        'value' => 10
                    )
                )
        )); 
    }
    
    /*
     * name: processProductMissingPriceIndexer
     * description: find missing products in price indexer table and add in partial indexer
     * @return: void
     */
    public function processProductMissingPriceIndexer() {
        
        $attrStatus             = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'status');
        $catalogProduct         = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
        $catalogProductInt      = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_int');
        $catalogProductPrice    = Mage::getSingleton('core/resource')->getTableName('catalog_product_index_price');

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT e.entity_id FROM ".$catalogProduct." as e "
            . "INNER JOIN ".$catalogProductInt." as status ON (e.entity_id = status.entity_id AND status.attribute_id = " . $attrStatus->getId() . ")"
            . "LEFT OUTER JOIN ".$catalogProductPrice." as cpip on (e.entity_id = cpip.entity_id)"
            . "WHERE cpip.entity_id IS NULL AND status.value=1";

        $records = $read->query($sql);
        $entityIds = array();
        foreach($records as $record) {
            $entityIds[] = $record['entity_id'];
        }
        
        $this->insertEntityIds($entityIds);
    }    
}   
