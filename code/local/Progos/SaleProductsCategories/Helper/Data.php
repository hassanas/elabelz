<?php
class Progos_SaleProductsCategories_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getAllCategoryIds()
    {
        $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
        $saleCategoryCollection = $saleCategoryModel->getCollection();
        $categories = $saleCategoryCollection->getColumnValues('category_id');
        return $categories;
    }
    public function getOnSaleCategories()
    {
        $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
        $saleCategoryCollection = $saleCategoryModel->getCollection();
        $categories = $saleCategoryCollection->getColumnValues('sale_category_id');
        return $categories;
    }
    public function getOnSaleProducts()
    {
        $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
        $saleCategoryCollection = $saleCategoryModel->getCollection();
        $categories = $saleCategoryCollection->getColumnValues('sale_category_id');
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->addAttributeToSelect('name');


        $productCollection
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('in' => $categories));

        return array_unique($productCollection->getAllIds());
    }
    public function getAlreadyAssignedProducts($categoryId)
    {
        if (!is_array($categoryId)) {
            $categoryIds = array($categoryId);
        }
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->addAttributeToSelect('name');
        $productCollection
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('in' => $categoryIds));

        return array_unique($productCollection->getAllIds());
    }
}
	 