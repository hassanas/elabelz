<?php

class Progos_Restmob_Model_Resource_Quote_Index_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('restmob/quote_index');
    }
}