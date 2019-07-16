<?php

class Progos_Api_Cache_Model_Clean extends Progos_Api_Cache_Model_Fpc
{
    public function controllerActionPredispatchAdminhtmlCacheMassRefresh()
    {
        $types = Mage::app()->getRequest()->getParam('types');
        $isActive = (int) Mage::getStoreConfig('progos_cache_api/apicache/enable'); 
        $cacheableActions = $this->getHelper()->getCacheApiConfigs();
        if($isActive) {
            if(is_array($types)) {
                foreach ($types as $type) {
                    $this->_clean($type, $cacheableActions);
                }
            } else {
                $this->_clean($type, $cacheableActions);
            }
        }
        
    }
    
    protected function _isActive($key)
    {
        return Mage::app()->useCache($key);
    }
    
    protected function _clean($type, $cacheableActions) 
    {
        if($this->_isActive($type) && in_array($type, $cacheableActions)) {
            switch($type) {
                case self::CATEGORY_PAGE_API:
                    $this->cleanCache(self::CATEGORY_PAGE_TAGS);
                    return;
                case self::CATEGORY_PAGE_FILTER_API:
                    $this->cleanCache(self::CATEGORY_PAGE_FILTER_TAGS);
                    return;
                case self::SEARCH_PAGE_API:
                    $this->cleanCache(self::SEARCH_PAGE_TAGS);
                    return;
                case self::SEARCH_PAGE_FILTER_API:
                    $this->cleanCache(self::SEARCH_PAGE_FILTER_TAGS);
                    return;
                case self::PRODUCT_DETAIL_API:
                    $this->cleanCache(self::PRODUCT_DETAIL_API_TAGS);
                    return;
            }
        }
    }
    
    function cleanCache($key) 
    {
        $this->getFpc()->clean(sha1($key));
    }
    
    public function clearSpecificPageCache($type, $id)
    {
        $key = $type. '_' . $id;
        $this->cleanCache($key);
    }
}
