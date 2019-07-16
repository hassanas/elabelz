<?php

class Shreeji_Manageimage_Model_Mysql4_Manageimage_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('manageimage/manageimage');
    }
}