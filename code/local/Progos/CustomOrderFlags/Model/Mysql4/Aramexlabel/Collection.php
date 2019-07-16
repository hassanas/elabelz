<?php

class Progos_CustomOrderFlags_Model_Mysql4_Aramexlabel_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('customorderflags/aramexlabel');
    }
}