<?php
class Progos_NewArrivals_Model_Cron{	
	public function newArrivalCategories(){
	    if (Mage::getStoreConfig('catalog/newarrivals/enabled')) {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $sourceRoot = Mage::getStoreConfig('catalog/newarrivals/root_category');
            $destinationRoot = Mage::getStoreConfig('catalog/newarrivals/new_arrival_root');
            $newArrivalsModel = Mage::getModel('newarrivals/newarrivals');
            $sourceCategory = Mage::getModel('catalog/category')->load($sourceRoot);
            $childCat = $sourceCategory->getChildrenCategories();
            $newArrivalsCollection = $newArrivalsModel->getCollection();
            $categories = $newArrivalsCollection->getColumnValues('category_id');
            foreach ($childCat as $key => $value) {
                $newArrivalsModel->copyMissingCategory(
                    $value,
                    $categories,
                    $destinationRoot
                );
            }
        }
	} 	
	public function newArrivalProducts(){
        if (Mage::getStoreConfig('catalog/newarrivals/enabled')) {
            $newArrivalsModel = Mage::getModel('newarrivals/newarrivals');
            $newArrivalsCollection = $newArrivalsModel->getCollection();
            foreach ($newArrivalsCollection as $newArrivalsIds) {
                $newArrivalsModel->assignProducts($newArrivalsIds->getCategoryId(), $newArrivalsIds->getNewArrivalsCategoryId());
            }
        }
	} 	
	public function removeNewProducts(){
        if (Mage::getStoreConfig('catalog/newarrivals/enabled')) {
            $newArrivalsModel = Mage::getModel('newarrivals/newarrivals');
            $newArrivalsCollection = $newArrivalsModel->getCollection();
            $categories = $newArrivalsCollection->getColumnValues('new_arrivals_category_id');
            $categoryApi = Mage::getSingleton('catalog/category_api');
            $newFromDate = date("Y-m-d hh:mm:ss", strtotime('-30 days'));
            $todayDate = date('m_d_y');

            foreach ($categories as $newArrivalsIds) {
                $newCategory = Mage::getModel('catalog/category')->load($newArrivalsIds);
                $products = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addCategoryFilter($newCategory)
                    ->addAttributeToFilter('created_at',
                        array('lt' => $newFromDate));
                $ids = $products->getAllIds();
                if (count($ids)) {
                    foreach ($ids as $id) {
                        Mage::log('Removed Product Id: ' . $id . ':: New Arrivals Category Id: ' . $newArrivalsIds, null, $todayDate . '_removeProductFromNew.log');
                        if (!Mage::getStoreConfig('catalog/newarrivals/testmode')) {
                            $categoryApi->removeProduct($newArrivalsIds, $id);
                        }
                    }
                }

            }
        }
	} 
}