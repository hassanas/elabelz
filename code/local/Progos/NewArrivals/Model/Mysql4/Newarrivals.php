<?php
class Progos_NewArrivals_Model_Mysql4_Newarrivals extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("newarrivals/newarrivals", "id");
    }
}