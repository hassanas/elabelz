<?php
class Progos_Ccache_Model_Warmup_Manufacturers extends Mage_Core_Model_Abstract
{
	
    /**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ccache/warmup_manufacturers');
    }
}