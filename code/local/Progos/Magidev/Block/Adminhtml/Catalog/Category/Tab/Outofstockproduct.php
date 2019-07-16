<?php
/**
 * User: Hassan Ali Shahzad
 * Date: 3/15/2017
 * Time: 12:29 PM
 */ 
class Progos_Magidev_Block_Adminhtml_Catalog_Category_Tab_Outofstockproduct extends Mage_Adminhtml_Block_Catalog_Category_Tab_Product {
    protected function _prepareCollection()
    {
        //echo $this->getCategory()->getId();exit;
        if ($this->getCategory()->getId()) {
            $this->setDefaultFilter(array('in_category'=>1));
        }
        $this->setCollection(Mage::helper('progos_magidev')->getCategoryInActiveProductsCollection((int) $this->getRequest()->getParam('id', 0)));

        if ($this->getCategory()->getProductsReadonly()) {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
        }

       return  Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }
}