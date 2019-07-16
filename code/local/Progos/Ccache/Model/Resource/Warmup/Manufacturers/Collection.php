<?php

class Progos_Ccache_Model_Resource_Warmup_Manufacturers_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('ccache/warmup_manufacturers');
    }
}