<?php
class Progos_Xlanding_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getPageActiveProducts($pageId){

        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));

        $collection
            ->getSelect()
            ->join('am_landing_page_products', 'am_landing_page_products.product_id = e.entity_id', array('position'))
            ->where('am_landing_page_products.page_id='.$pageId)
            ->order('am_landing_page_products.position asc');
        return $collection;
    }
}
	 