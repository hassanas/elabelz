<?php
class Progos_SaleProductsCategories_Model_Cron{	
	public function addSaleProducts(){
        if (Mage::getStoreConfig('catalog/salecategory/enabled')) {
            $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
            $saleCategoryCollection = $saleCategoryModel->getCollection();
            foreach ($saleCategoryCollection as $saleCategoryIds) {
                $saleCategoryModel->assignProducts($saleCategoryIds->getCategoryId(), $saleCategoryIds->getSaleCategoryId());
            }
        }
        return true;
	}
	/*Remove products that are not on sale*/
    public function productsSaleExpired(){
        if (Mage::getStoreConfig('catalog/salecategory/enabled')) {
            $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
            $saleCategoryCollection = $saleCategoryModel->getCollection();
            $categories = $saleCategoryCollection->getColumnValues('sale_category_id');
            $categoryApi = Mage::getSingleton('catalog/category_api');
            $todayDate = date('m_d_y');

            foreach ($categories as $saleCategoryId) {
                $productCollection = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect('sku');
                $productCollection->addAttributeToSelect(array('name', 'created_at', 'manufacturer', 'url_path'), 'inner');
                $productCollection->addAttributeToFilter('type_id', 'configurable');
                $productCollection->addAttributeToFilter('status', 1);
                $productCollection
                    ->getSelect()
                    ->group('entity_id');
                Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
                $productCollection
                    ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
                    ->addAttributeToFilter('category_id', array('in' => $saleCategoryId));
                $productCollection->addPriceData(null,1);
                $productCollection->getSelect()->where('price_index.final_price >= price_index.price');
                $ids = $productCollection->getAllIds();
                if (count($ids)) {
                    foreach ($ids as $id) {
                        Mage::log('Removed Product Id: ' . $id . ':: Sale Category Id: ' . $saleCategoryId, null, $todayDate . '_removeProduct.log');
                        if(!Mage::getStoreConfig('catalog/salecategory/testmode')) {
                            $categoryApi->removeProduct($saleCategoryId, $id);
                        }

                    }
                }

            }
        }
        return true;
    }

	public  function  saleCreateCategories(){
        if (Mage::getStoreConfig('catalog/salecategory/enabled')) {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $sourceRoot = Mage::getStoreConfig('catalog/salecategory/sourcerootcategory');
            $destinationRoot = Mage::getStoreConfig('catalog/salecategory/salerootcategory');
            $saleCategoryModel = Mage::getModel('saleproductscategories/salecategories');
            $sourceCategory = Mage::getModel('catalog/category')->load($sourceRoot);
            $childcat = $sourceCategory->getChildrenCategories();
            $saleCategoryCollection = $saleCategoryModel->getCollection();
            $categories = $saleCategoryCollection->getColumnValues('category_id');

            foreach ($childcat as $key => $value) {
                $saleCategoryModel->copyMissingCategory(
                    $value,
                    $categories,
                    $destinationRoot
                );
            }
        }
        return true;

    }
}
