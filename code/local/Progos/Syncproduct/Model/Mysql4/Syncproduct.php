<?php

class Progos_Syncproduct_Model_Mysql4_Syncproduct extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the syncproduct_id refers to the key field in your database table.
        $this->_init('progos_syncproduct/syncproduct', 'syncproduct_id');
    }
}