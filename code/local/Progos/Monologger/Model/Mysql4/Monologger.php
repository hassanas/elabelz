<?php
class Progos_Monologger_Model_Mysql4_Monologger extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("monologger/monologger", "id");
    }
}