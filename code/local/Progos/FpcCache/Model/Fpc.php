<?php
/* Cache Api
*
* @package Progos_FpcCache
*/

class Progos_FpcCache_Model_Fpc 
{
    const API_CACHE = 'restmob_cache.log';
    protected $_api = 'api';
    private $isControllerObjectSet = false;
    protected $_object;
    private $controllerName;
    private $actionName;
    private $request;
    private $key;
    
    public function setControllerObject($object)
    {
        if($object instanceof Mage_Core_Controller_Front_Action) {
            $this->_object = $object;
            $this->setControllerName($object->getRequest()->getControllerName());
            $this->setActionName($object->getRequest()->getActionName());
            $this->setRequest($object->getRequest());
            $this->setKey();
            $this->isControllerObjectSet = true;
        }
    }
    
    public function isControllerObjectSet()
    {
        return $this->isControllerObjectSet;
    }
    
    public function getControllerObject()
    {
        $this->_object;
    }
    
    /**
     * 
     * @param string $name
     * @return void 
     */
    public function setControllerName($name)
    {
        $this->controllerName = $name;
    }
    
    /**
     * 
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }
    /**
     * @param string $name action
     * @return void
     */
    public function setActionName($name)
    {
        $this->actionName = $name;
    }
    
    /**
     * 
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }
    
    /**
     * 
     * @param type $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
    
    /**
     * 
     * @return type
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * @return void
     */
    public function setKey()
    {
        $params = $this->_getParams();
        $this->key = $this->_api . $this->getControllerName() . $this->getActionName() . $params;
    }

    /**
     * @return void
     */
    public function setKeyShipping($key)
    {
        $this->key = $key;
    }


    /**
     * 
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * 
     * @return \Lesti_Fpc_Model_Fpc
     */
    public function getFpc()
    {
        return Mage::getSingleton('fpc/fpc');
    }
    
    /**
     * 
     * @return \Progos_FpcCache_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('fpccache');
    }
    /**
     * 
     * @param type mixed
     * @param type string
     * @param type string
     * @return bool
     */
    public function saveFpc($body , $key,  $tags)
    {
        return $this->getFpc()->save(
            new Lesti_Fpc_Model_Fpc_CacheItem(json_encode($body), time(), 'text/html; charset=UTF-8'),
            $key, 
            $tags
        );
    }
    
    /**
     * 
     * @param string $key
     * @return boolean
     */
    public function isCache($key)
    {
        if($this->getFpc()->load($key) instanceof Lesti_Fpc_Model_Fpc_CacheItem){
            return true;
        } else {
            return false;
        }
    }
    /**
     * 
     * @param string $key
     * @return boolean|\Lesti_Fpc_Model_Fpc_CacheItem
     */
    public function getCacheData($key)
    {
        return $this->getFpc()->load($key);
    }
    /**
     * 
     * @return boolean
     */
    public function isFpcActive()
    {
        return $this->getFpc()->isActive();
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function canCacheRequest()
    {
        $apiCache = (int) Mage::getStoreConfig('fpccache/api/enable'); 
        $cacheType = $this->isCacheTypeActive($this->getControllerName());
        if($this->isControllerObjectSet() && $apiCache && $this->isFpcActive() && $cacheType) {
            $cacheableControllers = $this->getHelper()->getCachableControllers();
            $cacheableControllersActions = $this->getHelper()->getCachableControllersActions();
            if(in_array($this->getControllerName(), $cacheableControllers) && in_array($this->getActionName(), $cacheableControllersActions)) {
                return true;
            } 
       }
       return false;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getData()
    {
        $key = $this->getKey();
        if($this->isCache($key)) {
            if(Mage::getStoreConfig('fpccache/api/log')) {
                $this->_saveLog('Get key '. $key. '\n');
            }
            $cacheItem = $this->getCacheData($key);
            return $cacheItem->getContent();
        }
        return '';
    }
    
    /**
     * 
     * @param array $data
     * @return void 
     */
    public function setData($data)
    {
        if($this->canCacheRequest()) {
            if( !empty($data)) {
                $tags = $this->_setCacheTags();
                $this->saveFpc($data, $this->getKey(), $tags);
                if(Mage::getStoreConfig('fpccache/api/log')) {
                    $this->_saveLog('Set Key '. $this->getKey(). '\n');
                }
            }
        }
    }
    
    /**
     * 
     * @return string
     */
    protected function _getParams()
    {
        $groupId = $this->_getCustomerGroupId();
        $params = Mage::app()->getStore()->getId() . "_" . $groupId;
        $allParams = $this->getRequest()->getParams();
        foreach ($allParams as $param) {
            $params .= '_'. $param;
        } 
        return $params;
    }
    
    /**
     * 
     * @return int
     */
    protected function _getCustomerGroupId()
    {
        $customerSession = Mage::getSingleton('customer/session');
        return $customerSession->getCustomerGroupId();
    }
    
    /**
     * 
     * @return array
     */
    protected function _setCacheTags()
    {
        $cacheTags = array();
        $cacheTags[] = sha1($this->_api. $this->getControllerName());
        if(!empty($this->getActionName())) {
            $cacheTags[] = sha1($this->_api. $this->getControllerName(). '_'. $this->getActionName());
            
            if($this->getActionName() == 'productsfilter' && !empty($this->getRequest()->getParam('cid')) && empty(trim($this->getRequest()->getParam('s')))) {
                $cacheTags[] = sha1($this->_api. '_'. $this->getControllerName(). '_'. $this->getActionName(). '_category');
                $cacheTags[] = sha1($this->_api. '_'. $this->getControllerName(). '_'. $this->getActionName(). '_category_' . $this->getRequest()->getParam('cid'));
            } else if($this->getActionName() == 'productasso' && !empty($this->getRequest()->getParam('id'))) {
                $cacheTags[] = sha1($this->_api. '_'. $this->getControllerName(). '_'. $this->getActionName(). '_product');
                $cacheTags[] = sha1($this->_api. '_'. $this->getControllerName(). '_'. $this->getActionName(). '_product_' . $this->getRequest()->getParam('id'));
            } else if($this->getActionName() == 'layerednav' && !empty($this->getRequest()->getParam('manufacturer'))){
                $brands = explode(',',$this->getRequest()->getParam('manufacturer'));
                foreach ($brands as $brand){
                    $cacheTags[] = sha1($this->_api. '_'. $this->getControllerName(). '_'. $this->getActionName(). '_manufacturer_' . $brand);
                }
            } else if($this->getActionName() == 'autocomplete'){
                $cacheTags[] = sha1($this->_api.'autocomplete');
            }
        }
        return $cacheTags;
    }
    
    /**
     * 
     * @param string $type
     * @return boolean
     */
    public function isCacheTypeActive($type)
    {
        return Mage::app()->useCache($this->_api.$type);
    }
    
    /**
     * 
     * @param string $message
     * @param type $level
     */
    protected function _saveLog($message, $level = null)
    {
        Mage::log($message, $level, self::API_CACHE);
    }

    /**
     *
     * @param $cacheId
     */
    public function setCustomKey($cacheId)
    {
        $this->key = $cacheId;
    }
}