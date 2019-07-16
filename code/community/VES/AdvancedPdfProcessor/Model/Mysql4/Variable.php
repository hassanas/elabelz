<?php

class VES_AdvancedPdfProcessor_Model_Mysql4_Variable extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the pickupfromstore_id refers to the key field in your database table.
        $this->_init('advancedpdfprocessor/variable', 'entity_id');
    }
}