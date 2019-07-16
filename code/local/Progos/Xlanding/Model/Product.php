<?php

class Progos_Xlanding_Model_Product extends Mage_Core_Model_Abstract
{
    
    protected function  _construct()
    {
        $this->_init('xlanding/page_product');
    }

    public function savePageRelation($page)
    {
        $data = $page->getProductsData();
            $this->_getResource()->savePageRelation($page, $data);
        return $this;
    }



    public function getProductCollection($page)
    {
        $collection = Mage::getResourceModel('xlanding/page_product_collection')
            ->addPageFilter($page);
        return $collection;
    }

    
}
