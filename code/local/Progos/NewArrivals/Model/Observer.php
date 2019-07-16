<?php
class Progos_NewArrivals_Model_Observer{
    public function afterProductSave(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('catalog/newarrivals/enabled')) {
            $product = $observer->getProduct();
            $createdAt = $product->getCreatedAt();
            $newFromDate = date("Y-m-d hh:mm:ss", strtotime('-30 days'));
            $todayDate = date('m_d_y');
            $assign=true;
            if (Mage::getStoreConfig('catalog/newarrivals/onlyconfigurable') && !($product->isConfigurable())) {
                $assign=false;
                }

            if ($createdAt < $newFromDate && $assign) {
                $categoryApi = new Mage_Catalog_Model_Category_Api();
                $productCategoryids = $product->getCategoryIds();
                $newArrivalsModel = Mage::getModel('newarrivals/newarrivals');
                $newArrivalsCollection = $newArrivalsModel->getCollection();
                $categories = $newArrivalsCollection->getColumnValues('category_id');
                $newArrivals = $newArrivalsCollection->getColumnValues('new_arrivals_category_id');
                foreach ($productCategoryids as $categoryId) {
                    if (false !== $key = array_search($categoryId, $categories)) {
                        Mage::log('Category Id: ' . $newArrivals[$key] . ':: New Arrivals Product Id: ' . $product->getId(), null, $todayDate . '_newArrivalProductobserver.log');
                        if (!Mage::getStoreConfig('catalog/newarrivals/testmode')) {
                            $categoryApi->assignProduct($newArrivals[$key], $product->getId());
                        }

                    }
                }
            }
        }
    }
    public function afterCategorySave(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('catalog/newarrivals/enabled')) {
            $category = $observer->getEvent()->getCategory();
            $saleCategoryModel = Mage::getModel('newarrivals/newarrivals');
            $saleCategoryCollection = $saleCategoryModel->getCollection();
            $categories = $saleCategoryCollection->getColumnValues('category_id');
            $copyMissingCategories = Mage::getStoreConfig('catalog/newarrivals/copymissingcat');
            if (!in_array($category->getId(), $categories) && in_array($category->getParentId(), $categories) && $copyMissingCategories) {
                $saleCategory = Mage::getModel('newarrivals/newarrivals')->load($category->getParentId(), 'category_id')->getData('new_arrivals_category_id');
                $saleCategoryModel->copycat($category->getId(), $saleCategory);
            }
        }
    }

}