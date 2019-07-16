<?php
/**
* @author Gul Muhammad <gul.muhamamd@progos.org>
 */
class Progos_FpcCache_Helper_Order extends Progos_FpcCache_Helper_Data
{
    const ORDER_LOG = "order_placed_after.log";
    /**
     * 
     * @param type $entityIds product ids array
     * @return void
     */
    public function clearCacheAfterOrderPlaced($entityIds)
    {
        try {
            if($this->isActive()) {
                $this->productPageCache($entityIds);
                $this->categoryPageCache($entityIds);
                $this->brandPageCache($entityIds);
            }
        } catch (Exception $e)
        {
            Mage::log($e->getMessage(), '', self::ORDER_LOG);
        }
    }
    /**
     * 
     * @param type $entityIds
     * @return void
     */
    public function productPageCache($entityIds) 
    {
        $isActive = (int)Mage::getStoreConfig('progos_partialindex/order/clearProductCache');
        $isMobileActive = (int)Mage::getStoreConfig('progos_partialindex/order/clearMobileProductCache');
        $cache = true;
        if($isActive && $isMobileActive) {
            $lestiFpc = $this->getFpc();
            foreach ($entityIds as $id) {
                $lestiFpc->clean(sha1('product_' . $id));
                $lestiFpc->clean(sha1('api_products_productasso_product_' . $id));
            }
            $cache = false;
        } elseif($isActive) {
            $lestiFpc = $this->getFpc();
            foreach ($entityIds as $id) {
                $lestiFpc->clean(sha1('product_' . $id));
            }
        } elseif($isMobileActive) {
            $lestiFpc = $this->getFpc();
            foreach ($entityIds as $id) {
                $lestiFpc->clean(sha1('api_products_productasso_product_' . $id));
            }
        }
        
        if($cache && Mage::getStoreConfig('ccache/settings/enable') && Mage::getStoreConfig('ccache/settings/product')) {
            Mage::helper('fpccache')->addDataToCacheTable($entityIds, 'product');
        }
    }
    /**
     * 
     * @param type $entityIds
     * @return void 
     */
    public function categoryPageCache($entityIds) 
    {
        $isActive = (int)Mage::getStoreConfig('progos_partialindex/order/clearCategoryCache');
        $isMobileActive = (int)Mage::getStoreConfig('progos_partialindex/order/clearMobileCategoryCache');
        $cache = true;
        if($isActive && $isMobileActive) {
            $catIds = $this->getCategoriesIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($catIds as $id) {
                $lestiFpc->clean(sha1('category_' . $id));
                $lestiFpc->clean(sha1('api_products_productsfilter_category_' . $id));
            }
            $cache = false;
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
            }
        }
        
        if($cache && Mage::getStoreConfig('ccache/settings/enable') && Mage::getStoreConfig('ccache/settings/category')) {
            $catIds = $this->getCategoriesIds($entityIds);
            Mage::helper('fpccache')->addDataToCacheTable($catIds, 'category');
        }
    }
    
    
    /**
     * clear brand page cache
     * @param type $entityIds
     * @return void 
     */
    public function brandPageCache($entityIds) 
    {
        $isActive = (int)Mage::getStoreConfig('progos_partialindex/order/clearBrandCache');
        $brandIds = array();
        if($isActive) {
            $brandIds = $this->getBrandIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($brandIds as $id) {
                $lestiFpc->clean(sha1('brand_view_' . $id));
            }
        } else if(Mage::getStoreConfig('ccache/settings/enable') && Mage::getStoreConfig('ccache/settings/manufacturer')) {
            $brandIds = $this->getBrandIds($entityIds);
            Mage::helper('fpccache')->addDataToCacheTable($brandIds, 'manufacturer');
        }

        // clear filters cache after product sale due to issues on filters, cached(size.color) appear which sold out
        $isMobileFilterClearActive = (int)Mage::getStoreConfig('progos_partialindex/order/clearMobileFiltersCache');
        if($isMobileFilterClearActive){
            $brandIds = $this->_getBrandIds($entityIds);
            $lestiFpc = Mage::getSingleton('fpc/fpc');
            foreach ($brandIds as $id) {
                $lestiFpc->clean(sha1('api_filters_layerednav_manufacturer_' . $id));
            }
        }
    }


    /**
     * @param $entityIds
     */
    protected function _getBrandIds ($entityIds){

        $attributeCode = Mage::helper('shopbybrand/brand')->getAttributeCode();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect($attributeCode)
            ->addAttributeToFilter('entity_id',array('in'=>$entityIds));
        $brand = array();
        foreach ($collection as $d){
            $brand[] = $d->getManufacturer();
        }
        $brand = array_values(array_unique($brand));
        return $brand;
    }

}