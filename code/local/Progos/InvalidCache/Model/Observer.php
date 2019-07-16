<?php

class Progos_InvalidCache_Model_Observer {
    
    /**
     * Description:
     * Clear cache via cronjob
    */
    public function clearAllInvalidateCache() {
        // get all Invalidated caches
        $types = Mage::app()->getCacheInstance()->getInvalidatedTypes();
        Mage::Log(count($types) . " caches are invalidated.", null, "block_cache.log");
        foreach($types as $_type) {
            // bingo clear them now
            Mage::app()->getCacheInstance()->cleanType($_type->getId());
            Mage::Log(sprintf("Cleared: %s",$_type->getId()), null, "block_cache.log");
        }
        return $this;
    }
}