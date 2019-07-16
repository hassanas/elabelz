<?php
class Progos_Ccache_Model_Warmup
{
    const PRODUCT_ENABLE = 'ccache/warmup_product/enable';
    const PRODUCT_LIMIT = 'ccache/warmup_product/limit';
    const PRODUCT_ORDER = 'ccache/warmup_product/order';
    const CATEGORY_ENABLE = 'ccache/warmup_category/enable';
    const CATEGORY_LIMIT = 'ccache/warmup_category/limit';
    const CATEGORY_ORDER = 'ccache/warmup_category/order';
    const MANUFACTURER_ENABLE = 'ccache/warmup_manufacturer/enable';
    const MANUFACTURER_LIMIT = 'ccache/warmup_manufacturer/limit';
    const MANUFACTURER_ORDER = 'ccache/warmup_manufacturer/order';
    public function products()
    { 
        $active = Mage::getStoreConfig(self::PRODUCT_ENABLE);
        if($active) {
            $limit =  Mage::getStoreConfig(self::PRODUCT_LIMIT) > 0 ? Mage::getStoreConfig(self::PRODUCT_LIMIT) : 10;
            $orderBy = Mage::getStoreConfig(self::PRODUCT_ORDER) ? 'ASC' : 'DESC';
            $allStores = Mage::app()->getStores();
            $collection = Mage::getModel('ccache/warmup_products')->getCollection();
            $collection->setPageSize($limit)
                    ->setOrder('product_id', $orderBy);
            foreach ($collection as $data) {
                foreach ($allStores as $store) {
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getStoreId());
                    $product = Mage::getModel('catalog/product')->load($data->getProductId());
                    if($product->getId() && $product->getTypeId() == 'configurable') {
                        $this->curlRequest($product->getProductUrl());
                    }
                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                }
                $data->delete($data->getId());
            }
        }
    }
    
    public function categories()
    {
        $active = Mage::getStoreConfig(self::CATEGORY_ENABLE);
        if($active) {
            $limit =  Mage::getStoreConfig(self::CATEGORY_LIMIT) > 0 ? Mage::getStoreConfig(self::CATEGORY_LIMIT) : 10;
            
            $orderBy = Mage::getStoreConfig(self::CATEGORY_ORDER) ? 'ASC' : 'DESC';
            $allStores = Mage::app()->getStores();
            $collection = Mage::getModel('ccache/warmup_categories')->getCollection();
            $collection->setPageSize($limit)
                    ->setOrder('category_id', $orderBy);
            foreach ($collection as $data) {
                foreach ($allStores as $store) {
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getStoreId());
                    $category = Mage::getModel('catalog/category')->load($data->getCategoryId());
                    if($category->getId() && $category->getIsActive()) {
                        $this->curlRequest($category->getUrl());
                    }
                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                }
                $data->delete($data->getId());
            }
        }
    }
    
    public function manufacturers()
    {
        $active = Mage::getStoreConfig(self::MANUFACTURER_ENABLE);
        if($active) {
            $limit =  Mage::getStoreConfig(self::MANUFACTURER_LIMIT) > 0 ? Mage::getStoreConfig(self::MANUFACTURER_LIMIT) : 10;
            $orderBy = Mage::getStoreConfig(self::MANUFACTURER_ORDER) ? 'ASC' : 'DESC';
            $allStores = Mage::app()->getStores();
            $collection = Mage::getModel('ccache/warmup_manufacturers')->getCollection();
            $collection->setPageSize($limit)
                    ->setOrder('manufacturer_id', $orderBy);
            $brandHelper = Mage::helper('shopbybrand');
            foreach ($collection as $data) {
                foreach ($allStores as $store) {
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getStoreId());
                    $manufacturer = Mage::getModel('shopbybrand/brand')->setStoreId($store->getStoreId())->load($data->getManufacturerId());
                    if($manufacturer->getId() && $manufacturer->getStatus() == 1) {
                        $this->curlRequest(Mage::getBaseUrl(). $brandHelper->refineUrlKey($manufacturer->getUrlKey()));
                    }
                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                }
                $data->delete($data->getId());
            }
        }
    }
    
    public function curlRequest($url)
    {
        Mage::log($url, Zend_Log::INFO, "warmup_pages.log");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpCode != 200) {
            Mage::log('Url not found: '. $url. ' error code: '. $httpCode, Zend_Log::INFO, "warmup_pages.log");
        }
    }
    
    public function insertIds($ids, $tableName, $columnName)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName($tableName);
        foreach ($ids as $id) {
            $insertData = array($columnName => $id);
            $writeConnection->insertOnDuplicate($table, $insertData, array($columnName));
        }
    }
}