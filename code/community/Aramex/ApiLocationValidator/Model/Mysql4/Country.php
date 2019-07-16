<?php
class Aramex_ApiLocationValidator_Model_Mysql4_Country extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("apilocationvalidator/country", "location_id");
    }
}