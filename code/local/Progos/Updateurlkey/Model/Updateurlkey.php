<?php

/**
 * Progos_Updateurlkey.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand <saroop.chand@progos.org>
 * @Date 16-04-2018
 *
 */

class Progos_Updateurlkey_Model_Updateurlkey
{
    protected $config;
    public function __construct(){
        Mage::init();
        $this->config = Mage::getSingleton('updateurlkey/config');
    }

    public function run(){
        ini_set('memory_limit', '-1');
        if( $this->config->moduleEnable() ){
            $date           = date('m/d/Y h:i:s a', time());
            $dryrun         = $this->config->isDryrun();
            $stores         = Mage::app()->getStores();
            $storeIds       = $this->config->storeIds();
            $activeStore    = array();
            if( !empty( $storeIds ) ){
                if( in_array( 0 , $storeIds ) ){
                    $activeStore = array( array( 'id' => 0 , 'code' => 'admin') );
                }
            }else{
                $activeStore = array( array( 'id' => 0 , 'code' => 'admin') );
            }

            foreach($stores as $key => $store){
                if( !empty( $storeIds ) ){
                    if( in_array( $key , $storeIds ) ){
                        $activeStore[] = array( 'id'=> $key ,'code'=>Mage::app()->getStore($key)->getCode());
                    }
                }else{
                    $activeStore[] = array( 'id'=> $key ,'code'=>Mage::app()->getStore($key)->getCode());
                }
            }

            $collection = Mage::getResourceModel('catalog/product_collection')
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('type_id', array('eq' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE));

            $ids = $this->config->productIds();
            if( !empty($ids) ){
                $collection->addAttributeToFilter('entity_id', array('in' => $ids));
            }

            foreach( $collection as $product ){
                $catogryIds = $product->getCategoryIds();
                $brand      = $product->getAttributeText('manufacturer');
                $name       = $product->getName();
                $url        = $this->createProductUrl( $brand , $catogryIds, $name , $product->getEntityId() );
                $this->setStoreBasedUrl( $product , $url , $activeStore , $dryrun , $date );
            }
            if( $dryrun )
                $this->config->addLog( "\n\n" , 'dry');
            else
                $this->config->addLog( "\n\n");

            return "Success Please Check Log.";
        }else{
            return "Please enable module first.";
        }
    }

    public function setStoreBasedUrl( $prod , $url , $stores , $dryrun , $date ){
        foreach( $stores as $store ) {
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store['id'], Mage_Core_Model_App_Area::AREA_ADMIN);
            $products = Mage::getResourceModel('catalog/product_collection')
                        ->addAttributeToSelect('url_key')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('manufacturer')
                        ->addAttributeToFilter('entity_id', array('eq' => $prod->getEntityId() ))
                        ->addAttributeToFilter('type_id', array('eq' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE));
            foreach( $products as $product ){
                if( $product->getUrlKey() != $url ) {
                    if ($dryrun) {
                        $data =
                        $data = $date . "," . $url . "," . $product->getUrlKey() . "," . $store['id'] . "," . $store['code'] . "," . $product->getId() . "," . $product->getName() . "," . $product->getAttributeText('manufacturer') . " \n";
                        $this->config->addLog($data, 'dry');
                    } else {
                        Mage::getSingleton('catalog/product_action')->updateAttributes(
                            array($product->getEntityId()),
                            array('url_key' => $url),
                            $store['id']
                        );
                        $data = $date . "," . $url . "," . $product->getUrlKey() . "," . $store['id'] . "," . $store['code'] . "," . $product->getId() . "," . $product->getName() . "," . $product->getAttributeText('manufacturer') . " \n";
                        $this->config->addLog($data);
                    }
                }
            }
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
        if( ! $dryrun )
            Mage::helper("partialindex")->addPartialIndexer( $prod->getId(), 10000 );
    }

    public function createProductUrl( $brand , $category , $name , $id ){
        $seoUrl = "buy ";
        if (!empty($brand)) {
            $seoUrl .= $brand . " ";
        }
        $seoUrl .= $name . " ";
        if (!empty( $category )) {
            $cats = $category;
            sort($cats);
            // here get the parent category ids which need to exclude from product url
            // also get all the child categories from above to remove
            $excludedCategories = explode(',', Mage::getModel('core/variable')->setStoreId(Mage::app()->getStore()->getId())->loadByCode('exclude_categories_from_product_url')->getValue('text'));
            $toExclude = array();
            foreach ($excludedCategories as $excludedCategory) {
                $toExclude = Mage::helper('mirasvitseo')->retrieveAllChildCategories($excludedCategory);
                $toExclude = Mage::helper('mirasvitseo')->getAllKeysForMultiLevelArrays($toExclude);
                $cats = array_diff($cats, $toExclude);
            }
            $cats = array_diff($cats, $excludedCategories);
            $targetedCat = array();
            if (count($cats) > 0) {
                $targetedCat[] = reset($cats);
                $targetedCat[] = end($cats);
                $categories = Mage::getResourceModel('catalog/category_collection')
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('entity_id', array('in' => $targetedCat));
                $seoUrl .= "for ";
                foreach ($categories as $category)
                    $seoUrl .= $category->getName() . " ";
            }
        }
        $seoUrl .= $id . " ";
        $seoUrl = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($seoUrl));
        $seoUrl = strtolower($seoUrl);
        $seoUrl = trim($seoUrl, '-');
        Mage::getSingleton('core/session')->setSeoProductUrl($seoUrl);
        return Mage::getSingleton('core/session')->getSeoProductUrl();
    }
}