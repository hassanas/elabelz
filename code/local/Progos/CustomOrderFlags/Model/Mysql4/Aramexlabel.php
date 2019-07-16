<?php

class Progos_CustomOrderFlags_Model_Mysql4_Aramexlabel extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        // Note that the label_id refers to the key field in your database table.
        $this->_init('customorderflags/aramexlabel', 'label_id');
    }
}