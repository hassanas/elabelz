<?php

class Progos_Magidev_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getCategoryActiveProductsCollection($catId)
    {
        return Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('price')
            ->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
            ->joinField('position',
                'catalog/category_product',
                'position',
                'product_id=entity_id',
                'category_id=' . (int)$catId,
                'left');

    }

    public function getCategoryInActiveProductsCollection($catId)
    {
        return Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('price')
            ->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED))
            ->joinField('position',
                'catalog/category_product',
                'position',
                'product_id=entity_id',
                'category_id=' . (int)$catId,
                'left');

    }
    public function getCategoryActiveProducts($category_id)
    {
        return Mage::getModel('catalog/category')->load($category_id)
            ->getProductCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', 4)
            ->setOrder('price', 'ASC');
    }
}
