<?php

class VES_AdvancedPdfProcessor_Model_Mysql4_Variable_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('advancedpdfprocessor/variable');
    }
}