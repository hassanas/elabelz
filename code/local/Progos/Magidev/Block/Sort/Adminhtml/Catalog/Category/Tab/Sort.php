<?php
/**
 * @method array getCategoryProducts()
 * @method array getProductPositions()
 * @method int getCollectionSize()
 */

class Progos_Magidev_Block_Sort_Adminhtml_Catalog_Category_Tab_Sort extends Mage_Core_Block_Template
{
    const SORT_DIRECTION_ASC = 1, SORT_DIRECTION_DESC = 2;
    const SORT_TYPE_REPLACE = 'replace', SORT_TYPE_INSERT = 'insert';

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('progos/magidev/sort/list.phtml');
        $this->_prepareProducts();
    }

    public function getType()
    {
        return Mage::getStoreConfig('catalog/frontend/merchandising_type');
    }

    public function getScope($attributeCode)
    {
        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
        if (!$attribute->getIsGlobal() || $attribute->getIsGlobal() == Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE) {
            return $this->__('STORE VIEW');
        } elseif ($attribute->getIsGlobal() == Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL) {
            return $this->__('GLOBAL');
        } else {
            return $this->__('WEBSITE');
        }
    }


    private function _prepareProducts()
    {
        $_categoryId = $this->getRequest()->getParam('id');
        if (empty($_categoryId)) {
            $this->setCollectionSize(0);
            return;
        }
        if (!$_category = Mage::registry('category')) {
            $_category = Mage::getModel('catalog/category')->load($_categoryId);
        }
        if (Mage::getSingleton('core/session')->getMagiBackendStoreId()) {
            $_category->setStoreId(Mage::getSingleton('core/session')->getMagiBackendStoreId());
        }

        $_productPositions = $_category->getProductsPosition();
        $this->setProductPositions($_productPositions);
        if (empty($_productPositions)) {
            $this->setCollectionSize(0);
        } else {
            // Add +1 because the collection in Progos_Magidev_Adminhtml_SortproductController::loadProductsAction() adds a blank item to the collection
            $this->setCollectionSize(count($_productPositions) + 1);
        }
    }


    public function getColumnCount()
    {
        return Mage::getStoreConfig('catalog/frontend/merchandising_column_count');
    }

    public function getCategoryId()
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

    public function cronBasedMerchandising(){
        return Mage::getStoreConfig('progos_merchandising/general/cronmerchandisingstatus');
    }
}