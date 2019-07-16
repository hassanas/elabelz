<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


if (Mage::helper('mstcore')->isModuleInstalled('CorlleteLab_Imagezoom') && class_exists('CorlleteLab_Imagezoom_Block_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends CorlleteLab_Imagezoom_Block_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Fooman_Speedster') && class_exists('Fooman_Speedster_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Fooman_Speedster_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Fooman_SpeedsterAdvanced') && class_exists('Fooman_SpeedsterAdvanced_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Fooman_SpeedsterAdvanced_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Conekta_Card') && class_exists('Conekta_Card_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Conekta_Card_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Aoe_JsCssTstamp') && class_exists('Aoe_JsCssTstamp_Block_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Aoe_JsCssTstamp_Block_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Creativestyle_CheckoutByAmazon') && class_exists('Creativestyle_CheckoutByAmazon_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Creativestyle_CheckoutByAmazon_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('IWD_StoreLocator') && class_exists('IWD_StoreLocator_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends IWD_StoreLocator_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Potato_Compressor') && class_exists('Potato_Compressor_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Potato_Compressor_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Kallyas_Kallyas') && class_exists('Kallyas_Kallyas_Block_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Kallyas_Kallyas_Block_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('WBL_Minify') && class_exists('WBL_Minify_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends WBL_Minify_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Creativestyle_CheckoutByAmazon') && class_exists('Creativestyle_CheckoutByAmazon_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Creativestyle_CheckoutByAmazon_Block_Page_Html_Head {
    }

} elseif (Mage::helper('core')->isModuleEnabled('Apptrian_Minify') && class_exists('Apptrian_Minify_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Apptrian_Minify_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('Smartwave_Porto') && class_exists('Smartwave_Porto_Block_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Smartwave_Porto_Block_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('ISeller_Aceparts') && class_exists('ISeller_Aceparts_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends ISeller_Aceparts_Block_Page_Html_Head {

    }
} elseif (Mage::helper('core')->isModuleEnabled('EM_Em0126settings') && class_exists('EM_Em0126settings_Block_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends EM_Em0126settings_Block_Html_Head {
    }
} elseif (Mage::helper('core')->isModuleEnabled('Smartwave_Mango') && class_exists('Smartwave_Mango_Block_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Smartwave_Mango_Block_Html_Head {
    }
} elseif (Mage::helper('core')->isModuleEnabled('Bubble_Queryfier') && class_exists('Bubble_Queryfier_Block_Page_Html_Head')) {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Bubble_Queryfier_Block_Page_Html_Head {
    }
} else {
    abstract class Mirasvit_Seo_Block_Html_Head_Abstract extends Mage_Page_Block_Html_Head {

    }
}

class Mirasvit_Seo_Block_Html_Head extends Mirasvit_Seo_Block_Html_Head_Abstract
{
    protected $_useAlgoritmForDifferentAttributes = false; //true - we will check if stores use different attributes and change it to correct value in url (for category filter page)
    protected $_useAlternateForWebsite            = false; //true - we will create alternate for all websites
    protected $_useAlternateForLayeredNavigation  = false; //true - we will add alternates for filtered category pages

    protected function _construct()
    {
        parent::_construct();
        $this->setupCanonicalUrl();
        $this->setupAlternateTag();
    }

    public function getConfig()
    {
    	return Mage::getSingleton('seo/config');
    }

    public function getRobots()
    {
        if (!$this->getAction()) {
            return;
        }

        if (Mage::app()->getStore()->isCurrentlySecure()
            && ($robotsCode = $this->getConfig()->getHttpsNoindexPages())) {
               return Mage::helper('seo')->getMetaRobotsByCode($robotsCode);
        }

        if ($product = Mage::registry('current_product')) {
            if ($robots = Mage::helper('seo')->getMetaRobotsByCode($product->getSeoMetaRobots())) {
                return $robots;
            }
        }
    	$fullAction = $this->getAction()->getFullActionName();
        foreach ($this->getConfig()->getNoindexPages() as $record) {
            //for patterns like filterattribute_(arttribte_code) and filterattribute_(Nlevel)
            if (strpos($record['pattern'], 'filterattribute_(') !== false
                && $fullAction == 'catalog_category_view') {
                    if ($this->_checkFilterPattern($record['pattern'])) {
                         return Mage::helper('seo')->getMetaRobotsByCode($record->getOption());
                    }
            }

            if (Mage::helper('seo')->checkPattern($fullAction, $record->getPattern())
                || Mage::helper('seo')->checkPattern(Mage::helper('seo')->getBaseUri(), $record['pattern'])) {
                return Mage::helper('seo')->getMetaRobotsByCode($record->getOption());
            }
        }

        return parent::getRobots();
    }

    protected function _checkFilterPattern($pattern)
    {
        $usedFilters = $this->_checkIfLayeredNavigation();
        if (!empty($usedFilters)) {
            $usedFiltersCount = count($usedFilters);
            if (strpos($pattern, 'level)') !== false) {
                preg_match('/filterattribute_\\((\d{1})level/', trim($pattern), $levelNumber);
                if (isset($levelNumber[1])) {
                    if ($levelNumber[1] == $usedFiltersCount) {
                        return true;
                    }
                }
            }

            foreach($usedFilters as $useFilterVal) {
                if (strpos($pattern, '(' . $useFilterVal . ')') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function _checkIfLayeredNavigation()
    {
        $urlParams = Mage::app()->getFrontController()->getRequest()->getQuery();
        if (!Mage::getSingleton('catalog/layer')->getFilterableAttributes()) {
            return array();
        }
        $currentFilters = Mage::getSingleton('catalog/layer')->getFilterableAttributes()->getData();
        $filterArr = array();
        foreach ($currentFilters as $filterAttr) {
            if (isset($filterAttr['attribute_code'])) {
                $filterArr[] = $filterAttr['attribute_code'];
            }
        }

        $usedFilters = array();
        if (!empty($filterArr)) {
            foreach ($urlParams as $keyParam => $valParam) {
                if (in_array($keyParam, $filterArr)) {
                    $usedFilters[] = $keyParam;
                }
            }
        }

        return $usedFilters;
    }

    public function setupCanonicalUrl() {
        if (!$this->getConfig()->isAddCanonicalUrl() || Mage::helper('seo')->isOnLandingPage()) {
            return;
        }

        if ($canonicalUrl = Mage::helper('seo')->getCanonicalUrl()) {
            $this->addLinkRel('canonical', $canonicalUrl);
        }
    }

    public function setupAlternateTag()
    {
        if (!$this->getConfig()->isAlternateHreflangEnabled(Mage::app()->getStore()->getStoreId()) || !$this->getAction()) {
            return;
        }

        $isMagentoEe = false;
        if (Mage::helper('mstcore/version')->getEdition() == 'ee') {
            $isMagentoEe = true;
        }

        $fullAction = $this->getAction()->getFullActionName();
        $currentStoreGroup = Mage::app()->getStore()->getGroupId();
        if (Mage::app()->getRequest()->getControllerName() == 'product'
            || Mage::app()->getRequest()->getControllerName() == 'category'
            || Mage::app()->getRequest()->getModuleName() == 'cms') {
                $storesNumberInGroup = 0;
                $storesArray         = array();
                $storesBaseUrls      = array();
                $xDefaultUrl         = '';

                foreach (Mage::app()->getStores() as $store)
                {
                    if($this->_useAlternateForWebsite) { // create alternate for all websites
                        $currentStoreGroup = $store->getGroupId();
                    }
                    if ($store->getIsActive() && $store->getGroupId() == $currentStoreGroup) { //we works only with stores which have the same store group
                        $storesArray[$store->getId()] = $store;
                        $storesBaseUrls[$store->getId()] = $store->getBaseUrl();
                        $storesNumberInGroup++;
                    }
                }
                $storesBaseUrlsCountValues = array_count_values($storesBaseUrls); //array with quantity of identical Base Urls

                if ($storesNumberInGroup > 1 ) { //if a current store is multilanguage
                    $isAlternateAdded = false;
                    if (($cmsPageId = Mage::getSingleton('cms/page')->getPageId())
                        && Mage::app()->getRequest()->getActionName() != 'noRoute') {
                        $cmsStoresIds = Mage::getSingleton('cms/page')->getStoreId();
                        $cmsCollection = Mage::getModel('cms/page')->getCollection()
                                        ->addFieldToSelect('alternate_group')
                                        ->addFieldToFilter('page_id', array('eq' => $cmsPageId))
                                        ->getFirstItem();
                        if(($alternateGroup = $cmsCollection->getAlternateGroup()) && $cmsStoresIds[0] != 0) {
                            $cmsCollection = Mage::getModel('cms/page')->getCollection()
                                        ->addFieldToSelect(array('alternate_group', 'identifier'))
                                        ->addFieldToFilter('alternate_group', array('eq' => $alternateGroup))
                                        ->addFieldToFilter('is_active', true);
                            $table = Mage::getSingleton('core/resource')->getTableName('cms/page_store');
                            $cmsCollection->getSelect()
                                         ->join(array('storeTable' => $table), 'main_table.page_id = storeTable.page_id', array('store_id' => 'storeTable.store_id'));
                            $cmsHierarchyCollection = clone $cmsCollection;
                            $cmsPages = $cmsCollection->getData();
                            if ($isMagentoEe) {
                                $cmsHierarchyCollection->clear();
                                $table = Mage::getSingleton('core/resource')->getTableName('enterprise_cms/hierarchy_node');
                                $cmsHierarchyCollection->getSelect()->join(array('cmsHierarchyTable' => $table), 'main_table.page_id = cmsHierarchyTable.page_id',  array('hierarchy_request_url' => 'request_url'));
                                if ($cmsHierarchyPages = $cmsHierarchyCollection->getData()) {
                                    $cmsPages = array_merge_recursive($cmsHierarchyPages, $cmsPages);
                                    $storeArray = array();
                                    foreach ($cmsPages as $keyCmsPages => $valueCmsPages) {
                                        if (in_array($valueCmsPages['store_id'], $storeArray)) {
                                            unset($cmsPages[$keyCmsPages]);
                                        }
                                        $storeArray[] = $valueCmsPages['store_id'];
                                    }
                                }
                            }
                            if(count($cmsPages) > 0) {
                                $alternateLinks = array();
                                foreach ($cmsPages as $page) {
                                     $pageIdentifier = ($isMagentoEe && isset($page['hierarchy_request_url'])) ? $page['hierarchy_request_url'] : $page['identifier'];
                                     $url = ($fullAction == 'cms_index_index') ? Mage::app()->getStore($page['store_id'])->getBaseUrl() : Mage::app()->getStore($page['store_id'])->getBaseUrl() . $pageIdentifier;
                                     $alternateLinks[$page['store_id']] = $url;
                                }
                                if (count($alternateLinks) > 0) {
                                    foreach ($alternateLinks as $storeId => $storeUrl) {
                                        //need if we have the same product url for every store, will add something like ?___store=frenchurl
                                        $urlAddition = (isset($storesBaseUrlsCountValues[$storesArray[$storeId]->getBaseUrl()]) && $storesBaseUrlsCountValues[$storesArray[$storeId]->getBaseUrl()] > 1) ? strstr(htmlspecialchars_decode($storesArray[$storeId]->getCurrentUrl(false)),"?") : '';
                                        $urlAddition = $this->getPreparedUrlAdditionalForCms($urlAddition);
                                        $storeCodeCms = substr(Mage::getStoreConfig('general/locale/code', $storeId),0,2);
                                        if ($urlAddition && !$xDefaultUrl) { //x-default alternate
                                            $xDefaultUrl = $storeUrl;
                                        }
                                        if ($localeCodeCms = $this->getConfig()->getHreflangLocaleCode($storeId)) { //hreflang locale code
                                            $storeCodeCms .= "-". $localeCodeCms;
                                        }
                                        $this->addLinkRel('alternate"' . ' hreflang="' . $storeCodeCms, $storeUrl.$urlAddition." ");
                                    }

                                    $isAlternateAdded = true;

                                }
                            }
                        }
                    }

                    if (!$isAlternateAdded) {
                        $currentStore = Mage::app()->getStore()->getId();
                        foreach ($storesArray as $store)
                        {
                           $storeCode = substr(Mage::getStoreConfig('general/locale/code', $store->getId()),0,2);
                           $addLinkRel = false;
                           //need if we have the same product url for every store, will add something like ?___store=frenchurl
                           $urlAddition = (isset($storesBaseUrlsCountValues[$store->getBaseUrl()]) && $storesBaseUrlsCountValues[$store->getBaseUrl()] > 1) ? strstr(htmlspecialchars_decode($store->getCurrentUrl(false)),"?") : '';
                            if (Mage::app()->getRequest()->getModuleName() == 'cms'
                                && Mage::app()->getRequest()->getActionName() != 'noRoute') {
                                    $cmsStoresIds = Mage::getSingleton('cms/page')->getStoreId();
                                    if ((is_array($cmsStoresIds) && in_array($store->getId(), $cmsStoresIds))
                                        || (isset($cmsStoresIds[0]) && $cmsStoresIds[0] == 0)) {
                                            $urlAdditionCms = $this->getPreparedUrlAdditionalForCms($urlAddition);
                                            if ($isMagentoEe
                                                && ($currentNode = Mage::registry('current_cms_hierarchy_node'))
                                                && ($cmsHierarchyRequestUrl = $currentNode->getRequestUrl()) ) {
                                                    $url = ($fullAction == 'cms_index_index') ? $store->getBaseUrl() . $urlAdditionCms : $store->getBaseUrl() . $cmsHierarchyRequestUrl . $urlAdditionCms;
                                            } else {
                                                $url = ($fullAction == 'cms_index_index') ? $store->getBaseUrl() . $urlAdditionCms : $store->getBaseUrl() . Mage::getSingleton('cms/page')->getIdentifier() . $urlAdditionCms;
                                            }
                                            $addLinkRel = true;
                                    }
                            }
                            if (Mage::app()->getRequest()->getControllerName() == 'product') {
                                $product = Mage::registry('current_product');
                                if (!$product) {
                                    return;
                                }
                                $category = Mage::registry('current_category');
                                $category ? $categoryId = $category->getId() : $categoryId = null;
                                if ($isMagentoEe) {
                                    $url = $store->getBaseUrl() . $this->getEeAlternateProductUrl() . $urlAddition;
                                } else {
                                    $url = $store->getBaseUrl() . $this->getAlternateProductUrl($product->getId(), $categoryId, $store->getId()) . $urlAddition;
                                }
                                $addLinkRel = true;
                            }
                            if (Mage::app()->getRequest()->getControllerName() == 'category') { //categorybegin
                                if ($this->_checkIfLayeredNavigation() != false && !$this->_useAlternateForLayeredNavigation) {
                                    return;
                                }
                                $currentStoreUrl = $store->getCurrentUrl(false);
                                $currentUrl      = Mage::helper('core/url')->getCurrentUrl();
                                $category = Mage::getModel('catalog/category')->getCollection()
                                            ->setStoreId($store->getId())
                                            ->addFieldToFilter('is_active', array('eq'=>'1'))
                                            ->addFieldToFilter('entity_id', array('eq'=>Mage::registry('current_category')->getId()))
                                            ->getFirstItem();

                                if($category->hasData() && ($currentCategory = Mage::getModel('catalog/category')->setStoreId($store->getId())->load($category->getEntityId())) ) {
                                        $categoryUrl     = $store->getBaseUrl() . $currentCategory->getUrlPath() . $urlAddition;
                                        $categoryUrlPath = $currentCategory->getUrlPath();
                                        $requestString = Mage::getSingleton('core/url')->escape(ltrim(Mage::app()->getRequest()->getRequestString(), '/'));
                                        if (($suffix = Mage::helper('catalog/category')->getCategoryUrlSuffix($store->getId()))
                                             && ($currentStoreSuffix = Mage::helper('catalog/category')->getCategoryUrlSuffix(Mage::app()->getStore()->getStoreId()))
                                             && $suffix != $currentStoreSuffix) {
                                                 //add correct suffix for every store
                                                 $requestString      = preg_replace('/'.$currentStoreSuffix.'$/ims', $suffix, $requestString);
                                                 $categoryUrlPath    = preg_replace('/'.$suffix.'$/ims', '', $categoryUrlPath);
                                        }

                                        if (strpos($requestString, $categoryUrlPath) === false) { //create correct category way for every store, need if category use different path
                                            $slashCountCategoryUrlPath = substr_count($categoryUrlPath, '/');
                                            $slashCountRequestString = substr_count($requestString, '/');
                                            $requestStringParts = explode('/', $requestString);
                                            $requestStringCategoryPart = implode('/', array_slice($requestStringParts, 0, $slashCountCategoryUrlPath+1));
                                            if ($slashCountCategoryUrlPath == $slashCountRequestString && $suffix) {
                                                $requestString = str_replace($requestStringCategoryPart, $categoryUrlPath, $requestString) . $suffix;
                                            } else{
                                                $requestString = str_replace($requestStringCategoryPart, $categoryUrlPath, $requestString);
                                            }
                                        }
                                        $preparedUrlAdditionCurrent = $this->getUrlAdditionalParsed(strstr($currentUrl,"?"));
                                        $preparedUrlAdditionStore   = $this->getUrlAdditionalParsed($urlAddition);
                                        $urlAdditionCategory = $this->getPreparedUrlAdditional($preparedUrlAdditionCurrent, $preparedUrlAdditionStore);

                                        if ($this->_useAlgoritmForDifferentAttributes) { // need if store use different attributes name
                                            $requestString = $this->getFilterPageRequestString($store->getId(), $requestString, $categoryUrlPath, $storesArray);
                                        }
                                        $url = $store->getBaseUrl() . $requestString . $urlAdditionCategory;
                                }

                                $addLinkRel = true;
                           } //categoryend

                           if ($addLinkRel && isset($url)) { //need to don't break store if $url not exist
                                if ($urlAddition && !$xDefaultUrl) { //x-default alternate
                                    $xDefaultUrl = $url;
                                }
                                if ($localeCode = $this->getConfig()->getHreflangLocaleCode($store->getId())) { //hreflang locale code
                                    $storeCode .= "-". $localeCode;
                                }
                                // echo "storeCode: ".$storeCode ." ||| alternate url: ". $url . "<br/>";
                                $this->addLinkRel('alternate"' . ' hreflang="' . $storeCode, $url . " ");
                                $isAlternateAdded = true;
                           }
                        }
                    }

                    //x-default alternate
                    if ($isAlternateAdded && $xDefaultUrl) {
                        $xDefaultUrl = $this->getPreparedXDefaultUrl($xDefaultUrl);
                        // echo "storeCode: x-default ||| alternate url: ". $url . "<br/>";
                        $this->addLinkRel('alternate"' . ' hreflang="x-default', $xDefaultUrl . " ");
                    }
                }

        }
    }

    public function getPreparedUrlAdditionalForCms($urlAddition) { //delete ___from_store in url if exist
        if ($urlAddition && strpos($urlAddition, '___from_store') !== false) {
            $preparedCmsUrlAddition = $this->getUrlAdditionalParsed($urlAddition);
            if (isset($preparedCmsUrlAddition['___from_store'])) {
                unset($preparedCmsUrlAddition['___from_store']);
            }

            $urlAddition = (count($preparedCmsUrlAddition) > 0) ? $this->getUrlAdditionalString($preparedCmsUrlAddition) : '';
        }

        return $urlAddition;
    }

    public function getFilterPageRequestString($storeId, $requestString, $categoryUrlPath, $storesArray) //maybe need improvement
    {
        $attributeStoresText = array();
        $attributeArray      = array();
        $currentStoreId      = Mage::app()->getStore()->getStoreId();
        $isFilter            = false;

        if (!Mage::registry('current_category')) {
            return false;
        }

        $categoryid = Mage::registry('current_category')->getId();
        $layer      = Mage::getModel("catalog/layer");
        $category   = Mage::getModel("catalog/category")->load($categoryid);

        $layer->setCurrentCategory($category);
        $attributes = $layer->getFilterableAttributes();
        foreach ($attributes as $attribute) {
            $attributeArray[$attribute->getAttributeCode()] = $attribute->getAttributeId();
        }
        $arrParams   = Mage::app()->getRequest()->getQuery();
        $usedFilters = array_intersect_key($arrParams, $attributeArray);
        if (count($usedFilters) > 0) {
            $isFilter = true;
            $preparedRequestString = str_replace($categoryUrlPath, '[caturlpath]', $requestString);
            foreach ($usedFilters as $keyFilter => $valueFilter) {
                if (($attributeId = $attributeArray[$keyFilter])
                    && (strpos(Mage::helper('core/url')->getCurrentUrl(), $keyFilter.'='.$valueFilter) == false)) {
                    foreach ($storesArray as $store) {
                        Mage::app()->setCurrentStore($store->getId());
                        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                        $attributeText = $attribute ->getSource()->getOptionText($valueFilter);
                        $attributeText = $this->_normalize($attributeText);
                        $attributeStoresText[$store->getId()] = $attributeText;
                    }
                    Mage::app()->setCurrentStore($currentStoreId);
                    if ($currentStoreId != $storeId && isset($attributeStoresText[$currentStoreId]) && isset($attributeStoresText[$storeId]) ) {
                        $preparedRequestString = str_replace($attributeStoresText[$currentStoreId], $attributeStoresText[$storeId], $preparedRequestString);
                    }
                }
            }
            $preparedRequestString = str_replace('[caturlpath]', $categoryUrlPath, $preparedRequestString);
        }

        return $isFilter ? $preparedRequestString : $requestString;
    }

    protected function _normalize($string)
    {
        $table = array(
                'Š'=>'S',  'š'=>'s',  'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z',  'ž'=>'z',  'Č'=>'C',  'č'=>'c',  'Ć'=>'C',  'ć'=>'c',
                'À'=>'A',  'Á'=>'A',  'Â'=>'A',  'Ã'=>'A',  'Ä'=>'Ae', 'Å'=>'A',  'Æ'=>'A',  'Ç'=>'C',  'È'=>'E',  'É'=>'E',
                'Ê'=>'E',  'Ë'=>'E',  'Ì'=>'I',  'Í'=>'I',  'Î'=>'I',  'Ï'=>'I',  'Ñ'=>'N',  'Ò'=>'O',  'Ó'=>'O',  'Ô'=>'O',
                'Õ'=>'O',  'Ö'=>'Oe', 'Ø'=>'O',  'Ù'=>'U',  'Ú'=>'U',  'Û'=>'U',  'Ü'=>'Ue', 'Ý'=>'Y',  'Þ'=>'B',  'ß'=>'Ss',
                'à'=>'a',  'á'=>'a',  'â'=>'a',  'ã'=>'a',  'ä'=>'ae', 'å'=>'a',  'æ'=>'a',  'ç'=>'c',  'è'=>'e',  'é'=>'e',
                'ê'=>'e',  'ë'=>'e',  'ì'=>'i',  'í'=>'i',  'î'=>'i',  'ï'=>'i',  'ð'=>'o',  'ñ'=>'n',  'ò'=>'o',  'ó'=>'o',
                'ô'=>'o',  'õ'=>'o',  'ö'=>'oe', 'ø'=>'o',  'ù'=>'u',  'ú'=>'u',  'û'=>'u',  'ý'=>'y',  'ý'=>'y',  'þ'=>'b',
                'ÿ'=>'y',  'Ŕ'=>'R',  'ŕ'=>'r',  'ü'=>'ue', '/'=>'',   '-'=>'',   '&'=>'',   ' '=>'',   '('=>'',   ')'=>''
            );

        $string = strtr($string, $table);
        $string = Mage::getSingleton('catalog/product_url')->formatUrlKey($string);

        return $string;
    }

    public function getPreparedXDefaultUrl($xDefaultUrl)
    {
        $urlAdditionXDefault = strstr(htmlspecialchars_decode($xDefaultUrl),"?");
        if (!$urlAdditionXDefault) {
            return $xDefaultUrl;
        }

        $preparedUrlAdditionXDefault = $this->getUrlAdditionalParsed($urlAdditionXDefault);

        foreach ($preparedUrlAdditionXDefault as $keyUrlAdditionXDefault => $valueUrlAdditionXDefault) {
            if ($keyUrlAdditionXDefault == '___store') {
                unset($preparedUrlAdditionXDefault[$keyUrlAdditionXDefault]);
            }
        }

        if (count($preparedUrlAdditionXDefault) == 0) {
            $urlAdditionXDefault = strtok($xDefaultUrl, '?');
        } else {
            $urlAdditionXDefault = strtok($xDefaultUrl, '?') . $this->getUrlAdditionalString($preparedUrlAdditionXDefault);
        }

        return $urlAdditionXDefault;
    }

    public function getUrlAdditionalParsed($urlAddition)
    {
        if (!$urlAddition) {
            return array();
        }
        $preparedUrlAddition = array();
        $urlAdditionParsed = (substr($urlAddition, 0, 1) == '?') ? substr($urlAddition, 1) : $urlAddition;
        $urlAdditionParsed = explode('&', $urlAdditionParsed);
        foreach ($urlAdditionParsed as $urlAdditionValue) {
            if (strpos($urlAdditionValue, '=') !== false) {
                $urlAdditionValueArray = explode('=', $urlAdditionValue);
                $preparedUrlAddition[$urlAdditionValueArray[0]] = $urlAdditionValueArray[1];
            } else {
                $preparedUrlAddition[$urlAdditionValue] = '';
            }
        }

        return $preparedUrlAddition;
    }

    public function getPreparedUrlAdditional($preparedUrlAdditionCurrent, $preparedUrlAdditionStore)
    {
        $correctUrlAddition = array();
        $mergedUrlAddition = array_merge_recursive($preparedUrlAdditionCurrent, $preparedUrlAdditionStore);
        foreach ($mergedUrlAddition as $keyUrlAddition => $valueUrlAddition) {
            if (is_array($valueUrlAddition) && $keyUrlAddition == '___store') {
                $correctUrlAddition[$keyUrlAddition] = $valueUrlAddition[1];
            } elseif (is_array($valueUrlAddition)) {
                $correctUrlAddition[$keyUrlAddition] = $valueUrlAddition[0];
            } elseif (array_key_exists($keyUrlAddition, $preparedUrlAdditionCurrent) || $keyUrlAddition == '___store') {
                $correctUrlAddition[$keyUrlAddition] = $valueUrlAddition;
            }
        }
        $urlAddition = (count($correctUrlAddition) > 0) ? $this->getUrlAdditionalString($correctUrlAddition) : '';

        return $urlAddition;
    }

    public function getUrlAdditionalString($correctUrlAddition)
    {
        $urlAddition      = '?';
        $urlAdditionArray = array();
        foreach ($correctUrlAddition as $keyUrlAddition => $valueUrlAddition) {
            $urlAdditionArray[] .= $keyUrlAddition . '=' . $valueUrlAddition;
        }
        $urlAddition .= implode('&', $urlAdditionArray);

        return $urlAddition;
    }

    public function getAlternateProductUrl($productId, $categoryId, $storeId)
    {
        $idPath = sprintf('product/%d', $productId);
        if ($categoryId && $this->getConfig()->getProductUrlFormat() != Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT) {
            $idPath = sprintf('%s/%d', $idPath, $categoryId);
        }
        $urlRewriteObject = Mage::getModel('core/url_rewrite')->setStoreId($storeId)->loadByIdPath($idPath);

        return $urlRewriteObject->getRequestPath();
    }

    public function getEeAlternateProductUrl() { //maybe need improvement
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $url = Mage::getSingleton('core/url')->parseUrl($currentUrl);
        $path = $url->getPath();
        $path = (substr($path, 0, 1) == '/') ? substr($path, 1) : $path;

        return $path;
    }
}
