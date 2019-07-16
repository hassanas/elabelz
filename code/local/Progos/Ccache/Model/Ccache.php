<?php
class Progos_Ccache_Model_Ccache extends Mage_Core_Model_Abstract
{
    const WARMUP_PRODUCT_ENABLE = 'ccache/warmup_product/enable';
    const WARMUP_CATEGORY_ENABLE = 'ccache/warmup_category/enable';
    const WARMUP_MANUFACTURER_ENABLE = 'ccache/warmup_manufacturer/enable';
    /**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ccache/ccache');
    }
    
    /**
     * Retrive id by type id
     *
     * @param   int $id
     * @return  int|false
     */
    public function getIdByTypeId($id)
    {
        return $this->_getResource()->getIdByTypeId($id);
    }
    
    /**
     * Retrive id by type id
     *
     * @param   int $id
     * @param   string $type
     * @return  int|false
     */
    public function getIdWithTypeAndTypeId($id, $type)
    {
        return $this->_getResource()->getIdWithTypeAndTypeId($id, $type);
    }
    
    public function getCacheCollection() 
    {
        return Mage::getModel('ccache/ccache')->getCollection();
    }
            
    /*
     * @get fpc model
     */
    public function getFpc()
    {
        return Mage::getSingleton('fpc/fpc');
    }
    
    /*
     * check fpc is active
     * @return bool
     */
    public function isActive()
    {
        return $this->getFpc()->isActive();
    }      
    
    /**
     * 
     */
    public function clearProductsCache()
    {
        try {
            if($this->isActive()) {
                $entityIds = array();
                $count =  (Mage::getStoreConfig('ccache/settings/productcount') > 0 ? Mage::getStoreConfig('ccache/settings/productcount') : 10);
                $collection = $this->getCacheCollection()->addFieldToFilter('count' , array('gteq' => $count));
                $collection->addFieldToFilter('type' , 'product');
                $lestiFpc = $this->getFpc();
                foreach ($collection as $record) {
                    $lestiFpc->clean(sha1('product_' . $record->getTypeId()));
                    $lestiFpc->clean(sha1('api_products_productasso_product_' . $record->getTypeId()));
                    $lestiFpc->clean(sha1('productdetailtags_' . $record->getTypeId()));
                    $entityIds[] = $record->getTypeId();
                    $record->setCount(0);
                    $record->save($record->getId());
                }
                if(self::WARMUP_PRODUCT_ENABLE && !empty($entityIds)) {
                    Mage::getModel('ccache/warmup')->insertIds($entityIds, 'warmup_products', 'product_id');
                }
            }
        } catch (Exception $e) {
            Mage::log('Clear Product Cache.. '. $e->getMessage(), null, 'clear_cache.log');
        }
    }
    
    /**
     * 
     */
    public function clearCategoriesCache()
    {
        try {
            if($this->isActive()) {
                $entityIds = array();
                $count =  (Mage::getStoreConfig('ccache/settings/categorycount') > 0 ? Mage::getStoreConfig('ccache/settings/categorycount') : 10);
                $collection = $this->getCacheCollection()->addFieldToFilter('count' , array('gteq' => $count));
                $collection->addFieldToFilter('type' , 'category');
                $lestiFpc = $this->getFpc();
                foreach ($collection as $record) {
                    $lestiFpc->clean(sha1('category_' . $record->getTypeId()));
                    $lestiFpc->clean(sha1('api_products_productsfilter_category_' . $record->getTypeId()));
                    $lestiFpc->clean(sha1('categoryapi2tags_' . $record->getTypeId()));
                    $lestiFpc->clean(sha1('categoryapi2filtertags_' . $record->getTypeId()));
                    $entityIds[] = $record->getTypeId();
                    $record->setCount(0);
                    $record->save($record->getId());
                }
                if(self::WARMUP_CATEGORY_ENABLE && !empty($entityIds)) {
                    Mage::getModel('ccache/warmup')->insertIds($entityIds, 'warmup_categories', 'category_id');
                }
            }
        } catch (Exception $e) {
            Mage::log('Clear Category Cache.. '. $e->getMessage(), null, 'clear_cache.log');
        }
    }
    
    /**
     * 
     */
    public function clearManufacturerCache()
    {
        try {
            if($this->isActive()) {
                $entityIds = array();
                $count =  (Mage::getStoreConfig('ccache/settings/manufacturercount') > 0 ? Mage::getStoreConfig('ccache/settings/manufacturercount') : 10);
                $collection = $this->getCacheCollection()->addFieldToFilter('count' , array('gteq' => $count));
                $collection->addFieldToFilter('type' , 'manufacturer');
                $lestiFpc = $this->getFpc();
                foreach ($collection as $record) {
                    $lestiFpc->clean(sha1('brand_view_' . $record->getTypeId()));
                    $entityIds[] = $record->getTypeId();
                    $record->setCount(0);
                    $record->save($record->getId());
                }
                if(self::WARMUP_MANUFACTURER_ENABLE && !empty($entityIds)) {
                    Mage::getModel('ccache/warmup')->insertIds($entityIds, 'warmup_manufacturers', 'manufacturer_id');
                }
            }
        } catch (Exception $e) {
            Mage::log('Clear manufacturer Cache.. '. $e->getMessage(), null, 'clear_cache.log');
        }
    }
}