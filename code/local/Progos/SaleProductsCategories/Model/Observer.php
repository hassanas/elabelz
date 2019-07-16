<?php

class Progos_SaleProductsCategories_Model_Observer
{

    public function afterProductSave(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('catalog/salecategory/enabled')) {
            if (Mage::getStoreConfig('catalog/salecategory/copyproducts')) {
                $product = $observer->getProduct();
                $specialprice = $product->getSpecialPrice();
                $specialPriceFromDate = $product->getSpecialFromDate();
                $specialPriceToDate = $product->getSpecialToDate();
                $today = time();
                $todayDate = date('m_d_y');
                if ($specialprice && ($product->getPrice() > $product->getFinalPrice())) {
                    if (($today >= strtotime($specialPriceFromDate) && $today <= strtotime($specialPriceToDate)) ||
                        ($today >= strtotime($specialPriceFromDate) && $specialPriceToDate == '')) {
                        $categoryApi = new Mage_Catalog_Model_Category_Api();
                        $productCategoryids = $product->getCategoryIds();
                        $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
                        $saleCategoryCollection = $saleCategoryModel->getCollection();
                        $categories = $saleCategoryCollection->getColumnValues('category_id');
                        $salecategories = $saleCategoryCollection->getColumnValues('sale_category_id');
                        foreach ($productCategoryids as $categoryId) {
                            if (false !== $key = array_search($categoryId, $categories)) {
                                Mage::log("Product: ".$product->getId()." assigns to category ".$salecategories[$key], null,$todayDate . '_addProduct.log' );
                                if(!Mage::getStoreConfig('catalog/salecategory/testmode')) {
                                    $categoryApi->assignProduct($salecategories[$key], $product->getId());
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function afterCategorySave(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('catalog/salecategory/enabled')) {
            $category = $observer->getEvent()->getCategory();
            $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
            $saleCategoryCollection = $saleCategoryModel->getCollection();
            $categories = $saleCategoryCollection->getColumnValues('category_id');
            $copyMissingCategories = Mage::getStoreConfig('catalog/salecategory/copymissingcat');
            if (!in_array($category->getId(), $categories) && in_array($category->getParentId(), $categories) && $copyMissingCategories) {
                $saleCategory = Mage::getModel('saleproductscategories/salecategories')->load($category->getParentId(), 'category_id')->getData('sale_category_id');
                $saleCategoryModel->copycat($category->getId(), $saleCategory);
            }
        }
    }

}
