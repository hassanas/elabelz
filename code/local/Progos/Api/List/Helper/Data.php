<?php

class Progos_Api_List_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DEFAULT_PAGE_LIMIT = 10;
    
    const DEFAULT_PAGE_MAXIMUM_LIMIT = 200;
    
    public function getRequestValues($key, $backendType = '')
    {
       $v = Mage::app()->getRequest()->getParam($key);
       
       if (is_array($v) || $backendType == 'decimal'){//smth goes wrong
           return array();
       }
       
       if (preg_match('/^[0-9,]+$/', $v)){
            $v = array_unique(explode(',', $v));
       }
       else { 
            $v = array();
       }
       
       return $v;       
    }
    
    /**
     * 
     * @return int
     */
    public function getDefaultPageLimit()
    {
        $configLimit = (int)Mage::getStoreConfig('progos_cache_api/apicache/min_page_limit');
        $limit = $configLimit > 0 ? $configLimit : self::DEFAULT_PAGE_LIMIT;
        return $limit;
    }
    
    /**
     * 
     * @return int
     */
    public function getPageMaxLimit()
    {
        $configLimit = (int)Mage::getStoreConfig('progos_cache_api/apicache/max_page_limit');
        $limit = $configLimit > 0 ? $configLimit : self::DEFAULT_PAGE_MAXIMUM_LIMIT;
        return $limit;
    }
    
    /**
     * 
     * @param int $limit
     * @return int
     */
    public function getPageLimit($limit)
    {
        $maxLimit = $this->getPageMaxLimit();
        if((int)$limit > $maxLimit) {
            return $maxLimit;
        }
        return $limit;
    }
    
}