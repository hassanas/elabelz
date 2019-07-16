<?php

class Progos_SaleProductsCategories_Model_Mysql4_Salecategories extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("saleproductscategories/salecategories", "id");
    }
}