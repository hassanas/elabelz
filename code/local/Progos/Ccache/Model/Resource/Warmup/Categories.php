<?php
class Progos_Ccache_Model_Resource_Warmup_Categories extends Mage_Core_Model_Mysql4_Abstract
{
    
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('ccache/warmup_categories','id');
    }
}