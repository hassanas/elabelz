<?php

class Shreeji_Manageimage_Model_Mysql4_Manageimage extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the manageimage_id refers to the key field in your database table.
        $this->_init('manageimage/manageimage', 'manageimage_id');
    }
}