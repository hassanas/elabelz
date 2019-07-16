<?php
/**
* @author Gul Muhammad <gul.muhamamd@progos.org>
 */
class Progos_FpcCache_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    const LESTI_FPC_LOG = "fpc_cache.log";
    const XML_PATH_CACHEABLE_CONTROLLERS = 'fpccache/api/controllers';
    const XML_PATH_CACHEABLE_ACTIONS = 'fpccache/api/actions';
    const WARMUP_PRODUCT_ENABLE = 'ccache/warmup_product/enable';
    const WARMUP_CATEGORY_ENABLE = 'ccache/warmup_category/enable';
    const WARMUP_MANUFACTURER_ENABLE = 'ccache/warmup_manufacturer/enable';
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
     * @param type $entityIds
     * @return void
     */
    public function clearCache($entityIds)
    {
        try {
            if($this->isActive()) {
                $this->clearProductPageCache($entityIds);
                $this->clearCategoryPageCache($entityIds);
                $this->clearBrandPageCache($entityIds);
            }
        } catch (Exception $e)
        {
            Mage::log($e->getMessage(), '', self::LESTI_FPC_LOG);
        }
    }
    /**
     * 
     * @param type $entityIds
     * @return void
     */
    public function clearProductPageCache($entityIds) 
    {
        $isActive = (int)Mage::getStoreConfig('progos_partialindex/index/clearProductCache');
        $isMobileActive = (int)Mage::getStoreConfig('progos_partialindex/index/clearMobileProductCache');
        $cache = true;
        if($isActive && $isMobileActive) {
            $lestiFpc = $this->getFpc();
            foreach ($entityIds as $id) {
                $lestiFpc->clean(sha1('product_' . $id));
                $lestiFpc->clean(sha1('api_products_productasso_product_' . $id));
                $lestiFpc->clean(sha1('productdetailtags_' . $id));
            }
            $cache = false;
            if(self::WARMUP_PRODUCT_ENABLE) {
                Mage::getModel('ccache/warmup')->insertIds($entityIds, 'warmup_products', 'product_id');
            }
        } elseif($isActive) {
            $lestiFpc = $this->getFpc();
            foreach ($entityIds as $id) {
                $lestiFpc->clean(sha1('product_' . $id));
            }
        } elseif($isMobileActive) {
            $lestiFpc = $this->getFpc();
            foreach ($entityIds as $id) {
                $lestiFpc->clean(sha1('api_products_productasso_product_' . $id));
                $lestiFpc->clean(sha1('productdetailtags_' . $id));
            }
        }
        
        if($cache && Mage::getStoreConfig('ccache/settings/enable') && Mage::getStoreConfig('ccache/settings/product')) {
            $this->addDataToCacheTable($entityIds, 'product');
        }
    }
    /**
     * 
     * @param type $entityIds
     * @return void 
     */
    public function clearCategoryPageCache($entityIds) 
    {
        $isActive = (int)Mage::getStoreConfig('progos_partialindex/index/clearCategoryCache');
        $isMobileActive = (int)Mage::getStoreConfig('progos_partialindex/index/clearMobileCategoryCache');
        $cache = true;
        if($isActive && $isMobileActive) {
            $catIds = $this->getCategoriesIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($catIds as $id) {
                $lestiFpc->clean(sha1('category_' . $id));
                $lestiFpc->clean(sha1('api_products_productsfilter_category_' . $id));
                $lestiFpc->clean(sha1('categoryapi2tags_' . $id));
                $lestiFpc->clean(sha1('categoryapi2filtertags_' . $id));
            }
            $cache = false;
            if(self::WARMUP_CATEGORY_ENABLE) {
                Mage::getModel('ccache/warmup')->insertIds($entityIds, 'warmup_categories', 'category_id');
            }
        } elseif($isActive) {
            $catIds = $this->getCategoriesIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($catIds as $id) {
                $lestiFpc->clean(sha1('category_' . $id));
            }
        } elseif($isMobileActive) {
            $catIds = $this->getCategoriesIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($catIds as $id) {
                $lestiFpc->clean(sha1('api_products_productsfilter_category_' . $id));
                $lestiFpc->clean(sha1('categoryapi2tags_' . $id));
                $lestiFpc->clean(sha1('categoryapi2filtertags_' . $id));
            }
        }
        
        if($cache && Mage::getStoreConfig('ccache/settings/enable') && Mage::getStoreConfig('ccache/settings/category')) {
            $catIds = $this->getCategoriesIds($entityIds);
            $this->addDataToCacheTable($catIds, 'category');
        }
    }
    /**
     * 
     * @param type $entityIds
     * @return array
     */
    public function getCategoriesIds($entityIds) 
    {
        $categoryProductTable = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $ids = implode(',', $entityIds);
        $sql = "SELECT DISTINCT category_id FROM {$categoryProductTable} WHERE product_id IN ( {$ids} )";
        return $read->fetchCol($sql);
    }
    /**
     * @return array
     */
    public function getCachableControllersActions()
    {
        $configs = trim(Mage::getStoreConfig(self::XML_PATH_CACHEABLE_ACTIONS));

        if ($configs) {
            return array_unique(array_map('trim', explode(',', $configs)));
        }

        return array();
        
    }
    
    /**
     * 
     * @return array
     */
    public function getCachableControllers()
    {
        $configs = trim(Mage::getStoreConfig(self::XML_PATH_CACHEABLE_CONTROLLERS));

        if ($configs) {
            return array_unique(array_map('trim', explode(',', $configs)));
        }

        return array();
        
    }
    
    /**
     * clear brand page cache
     * @param type $entityIds
     * @return void 
     */
    public function clearBrandPageCache($entityIds) 
    {
        $isActive = (int)Mage::getStoreConfig('progos_partialindex/index/clearBrandCache');
        if($isActive) {
            $brandIds = $this->getBrandIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($brandIds as $id) {
                $lestiFpc->clean(sha1('brand_view_' . $id));
            }
            if(self::WARMUP_MANUFACTURER_ENABLE) {
                Mage::getModel('ccache/warmup')->insertIds($entityIds, 'warmup_manufacturers', 'manufacturer_id');
            }
        } else if(Mage::getStoreConfig('ccache/settings/enable') && Mage::getStoreConfig('ccache/settings/manufacturer')) {
            $brandIds = $this->getBrandIds($entityIds);
            $this->addDataToCacheTable($brandIds, 'manufacturer');
        }
    }
    
    /*
     * Alert by Hassan:-
     * Following is the wrong implementation by Dev need to modify it.  brand_products just save is_feature and position of the product in brands only save if these filelds updated in grid. if product dont have position or featire marked then no entry in this table
     *
     * get brand ids
     * @param type array
     * @return array | null
     */
    public function getBrandIds($entityIds) 
    {
        $ids = implode(',', $entityIds);
        $brandTable = Mage::getSingleton('core/resource')->getTableName('brand_products');
        $qry = 'SELECT DISTINCT bp.bp_id FROM '. $brandTable. ' bp WHERE bp.product_id in ('.$ids.")";
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        return $read->fetchCol($qry);
    }
    
    /**
     * 
     * @param array $entityIds
     * @param string $type
     */
    public function addDataToCacheTable($entityIds, $type)
    {
        $ccache = Mage::getModel('ccache/ccache');
        
        foreach ($entityIds as $id) {
            $ccache->load($ccache->getIdWithTypeAndTypeId($id, $type));
            if($ccache->getId()) {
                $ccache->setData('count', ($ccache->getCount() + 1));
            } else {
                $ccache->setData('count', 1);
                $ccache->setData('type', $type);
                $ccache->setData('type_id', $id);
                $ccache->setData('id', null);
            }
            $ccache->save();
            $ccache->setData('id', null);
        }
    } 
}