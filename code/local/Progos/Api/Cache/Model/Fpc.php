<?php


class Progos_Api_Cache_Model_Fpc 
{
    protected $_key = 'api2';
    
    const API_CACHE = 'api_cache.log';
    
    const CATEGORY_PAGE_TAGS = 'categoryapi2tags';
    const CATEGORY_PAGE_API = 'list';
    
    const CATEGORY_PAGE_FILTER_TAGS = 'categoryapi2filtertags';
    const CATEGORY_PAGE_FILTER_API = 'productfilters';
    
    const SEARCH_PAGE_TAGS = 'searchapi2tags';
    const SEARCH_PAGE_API = 'search';
    
    const SEARCH_PAGE_FILTER_TAGS = 'searchapi2filtertags';
    const SEARCH_PAGE_FILTER_API = 'searchfilters';
    
    const PRODUCT_DETAIL_API = 'productdetail';
    const PRODUCT_DETAIL_API_TAGS = 'productdetailtags';
    
    public function getFpc()
    {
        return Mage::getSingleton('fpc/fpc');
    }
    
    public function isActive()
    {
        return $this->getFpc()->isActive();
    }
    
    public function getHelper()
    {
        return Mage::helper('api-cache');
    }
    
    public function isApiCacheActive($type)
    {
        return Mage::app()->useCache($type);
    }
    
    public function isCache($key)
    {
        if($this->getFpc()->load($key)  instanceof Lesti_Fpc_Model_Fpc_CacheItem ) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getCacheData($key)
    {
        $cacheItem = $this->getFpc()->load($key);
        return $cacheItem->getContent();
    }
    
    public function prepareData($observer)
    {
       $isActive = (int) Mage::getStoreConfig('progos_cache_api/apicache/enable'); 
       $cacheableActions = $this->getHelper()->getCacheApiConfigs();
       $object =  $observer->getEvent()->getObject();
       $operation = $observer->getEvent()->getOperation();
       $request = $object->getRequest();
       $type = $request->getParam('type');
       $isApiCacheActive = $this->isApiCacheActive($type);
       if($isActive && $this->isActive() && in_array($type, $cacheableActions) && $isApiCacheActive) {
            $data = $this->getCacheApi($type, $request , $object, $operation);  
            $object->setData($data);
       } else {
           $object->setData('');
       }
    }
    
    public function getCacheApi($type, $request , $object, $operation)
    {
        switch($type) {
            
            case self::CATEGORY_PAGE_API:
                $id = $request->getParam('cid');
                $additionalParams = $this->_getAdditionalPageParams($request);
                return $this->getData(self::CATEGORY_PAGE_TAGS, $operation, $object, $id, $additionalParams);
            case self::CATEGORY_PAGE_FILTER_API:
                $id = $request->getParam('cid');
                $additionalParams = $this->_getAdditionalPageParams($request);
                return $this->getData(self::CATEGORY_PAGE_FILTER_TAGS, $operation, $object, $id, $additionalParams);
            case self::SEARCH_PAGE_API:
                $id = $request->getParam('q');
                $additionalParams = $this->_getAdditionalPageParams($request);
                return $this->getData(self::SEARCH_PAGE_TAGS, $operation, $object, $id, $additionalParams);
            case self::SEARCH_PAGE_FILTER_API:
                $id = $request->getParam('q');
                $additionalParams = $this->_getAdditionalPageParams($request);
                return $this->getData(self::SEARCH_PAGE_FILTER_TAGS, $operation, $object, $id, $additionalParams);
            case self::PRODUCT_DETAIL_API:
                $id = $request->getParam('id');
                $sku = $request->getParam('sku');
                if (isset($sku)) {
                    $id = $sku;
                }
                $additionalParams = $this->_getAdditionalPageParams($request);
                return $this->getData(self::PRODUCT_DETAIL_API_TAGS, $operation, $object, $id, $additionalParams);
        }
        
    }
    
    protected function _getAdditionalPageParams($request)
    {
        $groupId = $this->_getCustomerGroupId();
        $additionalParams = Mage::app()->getStore()->getId() . "_" . $groupId;
        $params = $request->getParams();
        unset($params['model'], $params['type'], $params['api_type'], $params['action_type']);
        foreach ($params as $param) {
            $additionalParams .= '_'. $param;
        } 
        return $additionalParams;
    }
    
    protected function _getCustomerGroupId()
    {
        $customerSession = Mage::getSingleton('customer/session');
        return $customerSession->getCustomerGroupId();
    }
    
    public function getData($key, $operation, $object, $id = '', $additionalParams = '')
    {
        $fullKey = $this->_key . $key . $id . $additionalParams;
        if($this->isCache($fullKey)) {
            return json_decode($this->getCacheData($fullKey) , true);
        } else {
            $body = $this->getOperationData($operation, $object);
            $tags = $this->_cacheTags($key , $id);
            $this->saveFpc($body, $fullKey, $tags);
            return json_decode($this->getCacheData($fullKey), true);
        }
    }
    
    public function getOperationData($operation, $object)
    {
        if($operation == 'collection') {
            return $object->getCollection();
        } else {
            return $object->getRetrieve();
        }
    }
    
    public function saveFpc($body , $key,  $tags)
    {
        return $this->getFpc()->save(
            new Lesti_Fpc_Model_Fpc_CacheItem(json_encode($body), time(), 'text/html; charset=UTF-8'),
            $key, 
            $tags
        );
    }
    
    protected function _cacheTags($key, $id = '')
    {
        $cacheTags = array();
        $cacheTags[] = sha1($key);
        if(!empty($id)) {
            $cacheTags[] = sha1($key. '_' . $id);
        }
        return $cacheTags;
    }
    
    
}
