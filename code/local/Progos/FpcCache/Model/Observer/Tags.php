<?php

/**
* @category Progos
* @package Progos_FpcCache
* @author Gul Muhammad <gul.muhamamd@progos.org>
*/
class Progos_FpcCache_Model_Observer_Tags extends Lesti_Fpc_Model_Observer_Tags
{
    /**
     * @param $observer
     */
    public function fpcObserverCollectCacheTags($observer)
    {
        /** @var Lesti_Fpc_Helper_Data $helper */
        $helper = Mage::helper('fpc');
        $fullActionName = $helper->getFullActionName();
        $cacheTags = array();
        $request = Mage::app()->getRequest();
        switch ($fullActionName) {
            case 'cms_index_index' :
                $cacheTags = $this->getCmsIndexIndexCacheTags();
                break;
            case 'cms_page_view' :
                $cacheTags = $this->getCmsPageViewCacheTags($request);
                break;
            case 'catalog_product_view' :
                $cacheTags = $this->getCatalogProductViewCacheTags($request);
                break;
            case 'catalog_category_view' :
                $cacheTags = $this->getCatalogCategoryViewCacheTags($request);
                break;
            case 'shopbybrand_index_index' :
                $cacheTags = $this->getShopByBrandIndexTags();
                break;
            case 'shopbybrand_index_view' :
                $cacheTags = $this->getShopByBrandViewTags($request);
                break;
        }

        $cacheTagObject = $observer->getEvent()->getCacheTags();
        $additionalCacheTags = $cacheTagObject->getValue();
        $additionalCacheTags = array_merge($additionalCacheTags, $cacheTags);
        $cacheTagObject->setValue($additionalCacheTags);
    }
    
    /**
     * @param Mage_Core_Controller_Request_Http $request
     * @return array
     */
    protected function getShopByBrandIndexTags()
    {
        $cacheTags = array();
        $cacheTags[] = sha1('brand_index');
        return $cacheTags;
    }
    
    /**
     * @param Mage_Core_Controller_Request_Http $request
     * @return array
     */
    protected function getShopByBrandViewTags(
        Mage_Core_Controller_Request_Http $request
    )
    {
        $cacheTags = array();
        $cacheTags[] = sha1('brand_view');
        $id = (int)$request->getParam('id', false);
        if($id) {
            $cacheTags[] = sha1('brand_view_' . $id);
        }
        return $cacheTags;
    }
}

