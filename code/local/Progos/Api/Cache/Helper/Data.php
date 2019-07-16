<?php

/**
 * Description of Data
 *
 * @author 
 */
class Progos_Api_Cache_Helper_Data extends Progos_Api_Base_Helper_Data 
{
    const XML_PATH_CACHEABLE_ACTIONS = 'progos_cache_api/apicache/cache_actions';
    public function getCacheApiConfigs()
    {
        $configs = trim(Mage::getStoreConfig(self::XML_PATH_CACHEABLE_ACTIONS));

        if ($configs) {
            return array_unique(array_map('trim', explode(',', $configs)));
        }

        return array();
    }
}
