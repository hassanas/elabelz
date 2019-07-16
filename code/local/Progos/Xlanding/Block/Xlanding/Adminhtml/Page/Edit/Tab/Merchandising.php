<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xlanding
 */ 
class Progos_Xlanding_Block_Xlanding_Adminhtml_Page_Edit_Tab_Merchandising
    extends Mage_Adminhtml_Block_Widget_Form {

    public function _construct()
    {
        $this->setTemplate('xlanding/page/merchandising.phtml');
    }

    public function getProducts()
    {
        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('name');
//        ->addAttributeToFilter('entity_id', array('in' => $_collectProductIds));

        $collection
            ->getSelect()
            ->join('am_landing_page_products', 'am_landing_page_products.product_id = e.entity_id', array('position'))
            ->where('am_landing_page_products.page_id='.$this->getPage()->getId())
            ->order('am_landing_page_products.position asc');


        return $collection;
    }
    public function getPage()
    {
        return  Mage::registry('current_page');
    }
    public function getColumnCount()
    {
        return Mage::getStoreConfig('catalog/frontend/merchandising_column_count');
    }

    public function getPageId()
    {
        return $this->getRequest()->getParam('id');
    }

    public function getPageCount()
    {
        return Mage::getStoreConfig('catalog/frontend/grid_per_page');;
    }

    public function getAdminUrl($route, $params)
    {
        if ($this->getRequest()->getParam('store')) {
            $params['store'] = $this->getRequest()->getParam('store');
        }
        return Mage::helper("adminhtml")->getUrl($route, $params);
    }
    public function getType()
    {
        return Mage::getStoreConfig('catalog/frontend/merchandising_type');
    }
}
