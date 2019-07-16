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


class Mirasvit_Seo_Model_Observer extends Varien_Object
{
    protected $isProductTitlePrinted            = false;
    protected $isProductShortDescriptionPrinted = false;
    protected $isProductDescriptionPrinted      = false;
    protected $productTitlePrintedCounter       = 0;
    protected $productDescriptionPrintedCounter = 0;

    protected static $_seo = null;

    public function getConfig()
    {
        return Mage::getSingleton('seo/config');
    }

    public function applyMeta()
    {
        $headBlock = Mage::app()->getLayout()->getBlock('head');
        if ($headBlock) {
            if (!$seo = Mage::helper('seo')->getCurrentSeo()) {
               return;
            }
            //support of Amasty xLanding pages
            if (Mage::app()->getRequest()->getModuleName() == 'amlanding') {
                return;
            }
            if (Mage::app()->getRequest()->getModuleName() == 'brand' ) {
                return;
            }
            //support of Amasty Shopby pages
            if (Mage::app()->getRequest()->getModuleName() == 'amshopby' && $headBlock->getTitle() != '') {
                return;
            }

            //support of FISHPIG Attribute Splash Pages http://www.magentocommerce.com/magento-connect/fishpig-s-attribute-splash-pages.html
            if (Mage::registry('splash_page')) {
                return;
            }

            if ($seo->getMetaTitle()) {
                $headBlock->setTitle(Mage::helper('seo')->cleanMetaTag($seo->getMetaTitle()), '1');
            }

            if ($seo->getMetaDescription()) {
                //Removes HTML tags and unnecessary whitespaces from Description Meta Tag
                $description = $seo->getMetaDescription();
                $description = Mage::helper('seo')->cleanMetaTag($description);
                $headBlock->setDescription($description);
            }

            if ($seo->getMetaKeywords()) {
                $headBlock->setKeywords(Mage::helper('seo')->cleanMetaTag($seo->getMetaKeywords()));
            }
        }
    }


    public function addCustomAttributeOutputHandler(Varien_Event_Observer $observer)
    {
        $outputHelper = $observer->getEvent()->getHelper();
        $outputHelper->addHandler('productAttribute', $this);
        $outputHelper->addHandler('categoryAttribute', $this);
    }

    public function categoryAttribute(Mage_Catalog_Helper_Output $outputHelper, $outputHtml, $params)
    {
        if (!Mage::registry('current_category')) {
            return $outputHtml;
        }

        if (self::$_seo === null) {
            self::$_seo = Mage::helper('seo')->getCurrentSeo();
        }

        switch ($params['attribute']) {
            case 'name':
                $outputHtml = self::$_seo->getTitle();
                break;
            case 'description':
                //hide description in layered navigation results
                $layer = Mage::getSingleton('catalog/layer');
                $state = $layer->getState();
                if (count($state->getFilters()) > 0) {
                    $outputHtml = '';
                }
                break;
        }

        return $outputHtml;
    }

    public function productAttribute(Mage_Catalog_Helper_Output $outputHelper, $outputHtml, $params)
    {
        if (!$currentProduct = Mage::registry('current_product')) {
            return $outputHtml;
        }

        if ($params['attribute'] == "name" && $this->isProductTitlePrinted) {
            return $outputHtml;
        }

        if ($params['attribute'] == "short_description" && $this->isProductShortDescriptionPrinted) {
            return $outputHtml;
        }

        if ($params['attribute'] == "description" && $this->isProductDescriptionPrinted) {
            return $outputHtml;
        }

        if (self::$_seo === null) {
            self::$_seo = Mage::helper('seo')->getCurrentSeo();
        }

        switch ($params['attribute']) {
            case 'name':
                if ($currentProduct->getName() != self::$_seo->getTitle()) {
                    if ($this->checkProductTitlePrinted()) {
                        $this->isProductTitlePrinted = true;
                    }
                    $outputHtml = self::$_seo->getTitle();
                }
                break;
            case 'short_description':
                $this->isProductShortDescriptionPrinted = true;
                if ($shortDescription = self::$_seo->getShortDescription()) {
                    $outputHtml = $shortDescription;
                }
                break;
            case 'description':
                if ($this->checkProductDescriptionPrinted()) {
                    $this->isProductDescriptionPrinted = true;
                }
                if ($fullDescription = self::$_seo->getFullDescription()) {
                    $outputHtml = $fullDescription;
                }
                break;
        }

        return $outputHtml;
    }

    protected function checkProductTitlePrinted() {
        $packageName = array("rwd"); //here we add an array with the name of the theme, in which H1 is the second
        if (in_array(Mage::getDesign()->getPackageName(), $packageName)) {
             if ($this->productTitlePrintedCounter < 2) {
                $this->productTitlePrintedCounter++;
                return false;
            }
        }

        return true;
    }

    protected function checkProductDescriptionPrinted() {
        $packageName = array("unique"); //here we add an array with the name of the theme, in which fullDescription is the second
        if (in_array(Mage::getDesign()->getPackageName(), $packageName)) {
             if ($this->productDescriptionPrintedCounter < 2) {
                $this->productDescriptionPrintedCounter++;
                return false;
            }
        }

        return true;
    }

    public function addFieldsToCmsEditForm($e)
    {
        $form = $e->getForm();

        $fieldset = $form->addFieldset('seo_fieldset', array('legend' => Mage::helper('seo')->__('SEO Data'), 'class' => 'fieldset-wide'));

        $fieldset->addField('meta_title', 'text', array(
            'name' => 'meta_title',
            'label' => Mage::helper('seo')->__('Meta Title'),
            'title' => Mage::helper('seo')->__('Meta Title'),
        ));

        $fieldset->addField('description', 'textarea', array(
            'name' => 'description',
            'label' => Mage::helper('seo')->__('SEO Description'),
            'title' => Mage::helper('seo')->__('SEO Description'),
        ));
    }

    /**
     * Check is Request from AJAX
     * Magento 1.4.1.1 does not have this function in core
     *
     * @return boolean
     */
    public function isAjax()
    {
        $request = Mage::app()->getRequest();
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        if ($request->getParam('ajax') || $request->getParam('isAjax')) {
            return true;
        }

        return false;
    }


    public function checkUrl($e)
    {
        $action  = $e->getControllerAction();
        $url     = $action->getRequest()->getRequestString();
        $fullUrl = $_SERVER['REQUEST_URI'];

        if (Mage::app()->getStore()->isAdmin()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        if ($this->isAjax()){
            return;
        }

        $this->_redirectFromRedirecManagerUrlList();

        $this->_redirectFromOldLayeredNavigationUrl();

        $urlToRedirect = $this->getUrlWithCorrectEndSlash($url);

        if ($url != $urlToRedirect) {
            $this->redirect(rtrim(Mage::getBaseUrl(), '/') . $urlToRedirect);
        }

        if (substr($fullUrl, -4, 4) == '?p=1') {
            $this->redirect(substr($fullUrl, 0, -4));
        }

        if (in_array(trim($fullUrl,'/'), array('home', 'index.php', 'index.php/home'))) {
            $this->redirect('/');
        }
    }

    /**
     * @param string $redirectUrl
     * @return string
     */
    protected function _prepareRedirectUrl($redirectUrl) {
        if (stripos($redirectUrl, 'http://') === false
                && stripos($redirectUrl, 'https://') === false) {
                    return Mage::getBaseUrl() . ltrim($redirectUrl, '/');
        }

        return $redirectUrl;
    }

    /**
     * @param string $urlFrom
     * @param string $currentUrl
     * @return bool
     */
    protected function _checkRedirectPattern($urlFrom, $currentUrl) {
        $urlFrom = preg_quote($urlFrom, '/');
        $urlFrom = str_replace('\*', '(.*?)', $urlFrom);
        $pattern = '/' . $urlFrom . '/ims';
        if (preg_match($pattern, $currentUrl)) {

            return true;
        }

        return false;
    }

    /**
     * Redirect from Redirect Manager
     * @return bool
     */
    protected function _redirectFromRedirecManagerUrlList() {
        Varien_Profiler::start('seoredirect_getRedirectUrls');
        $currentUrl            = Mage::helper('core/url')->getCurrentUrl();
        $currentAction         = Mage::helper('seo')->getFullActionCode();
        // $defaultRedirectAction = $this->getConfig()->getRedirectManagerDefaultAction(Mage::app()->getStore()->getStoreId());

        $redirectCollection = Mage::getModel('seo/redirect')
                    ->getCollection()
                    ->addActiveFilter()
                    ->addStoreFilter(Mage::app()->getStore())
                    ->addFieldToFilter('url_from', array(
                        array('eq' => $currentUrl),
                        array('eq' => str_replace(Mage::getBaseUrl(), '', $currentUrl)),
                        array('eq' => str_replace(rtrim(Mage::getBaseUrl(), '/'), '', $currentUrl)),
                        array('like' => '%*%')
                    ));
        foreach ($redirectCollection as $redirect) {
            $urlFrom = $this->_prepareRedirectUrl($redirect->getUrlFrom());
            $urlTo   = $this->_prepareRedirectUrl($redirect->getUrlTo());
            $action  = $redirect->getIsRedirectOnlyErrorPage();

            if ($action && $currentAction != 'cms_index_noroute') {
                continue;
            }

            if ($currentUrl == $urlFrom
                || (stripos($redirect->getUrlFrom(), '*') !== false
                    && $this->_checkRedirectPattern($redirect->getUrlFrom(), $currentUrl)) ) {
                        $this->redirect($urlTo, $redirect->getRedirectType());
                        break;
                        // echo 'Do Redirect. Url ' . $urlFrom . ' is in Redirect Manager. <br/>';
            }
        }

        Varien_Profiler::stop('seoredirect_getRedirectUrls');

        return false;
    }

    //redirect from old Layered Navigation urls to category if mirasvit Layered Navigation enabled
    protected function _redirectFromOldLayeredNavigationUrl() {
        if(Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter')
            && Mage::getModel('seofilter/config')->isEnabled()) {
            $fullActionCode = Mage::helper('seo')->getFullActionCode();
            $isCategory = ($fullActionCode == 'catalog_category_view') ? true : false;

            $attributeArray = array();
            $currentUrl     = Mage::helper('core/url')->getCurrentUrl();
            $doRedirect     = false;

            if($isCategory) {
                $params = Mage::app()->getRequest()->getParams();
                if (isset($params['id'])) {
                    $layer = Mage::getModel("catalog/layer");
                    $category = Mage::getModel("catalog/category")->load($params['id']);
                    $layer->setCurrentCategory($category);
                    $attributes = $layer->getFilterableAttributes();
                    foreach ($attributes as $attribute) {
                        $attributeArray[] = $attribute->getAttributeCode() . '=';
                    }
                    foreach ($attributeArray as $attribute) {
                        if ($attribute == 'price=') {
                            continue;
                        }
                        if (strpos($currentUrl, $attribute) !== false) {
                            $doRedirect = true;
                            break;
                        }
                    }
                }
            }

            if ($doRedirect && $currentUrl) {
                $this->redirect(strtok($currentUrl, '?'));
            }
        }
    }

    protected function getUrlWithCorrectEndSlash($url)
    {
        $extension = substr(strrchr($url, '.'), 1);

        if (substr($url, -1) != '/' && $this->getConfig()->getTrailingSlash() == Mirasvit_Seo_Model_Config::TRAILING_SLASH) {
            if (!in_array($extension, array('html', 'htm', 'php', 'xml', 'rss'))) {
                $url .= '/';
                if ($_SERVER['QUERY_STRING']) {
                    $url .= '?'.$_SERVER['QUERY_STRING'];
                }
            }
        } elseif ($url != '/' && substr($url, -1) == '/' && $this->getConfig()->getTrailingSlash() == Mirasvit_Seo_Model_Config::NO_TRAILING_SLASH) {
            $url = rtrim($url, '/');
            if ($_SERVER['QUERY_STRING']) {
                $url .= '?'.$_SERVER['QUERY_STRING'];
            }
        }

        if (substr($url, -6) == '.html/') {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    protected function redirect($url, $redirectType = '301')
    {
        //additional check to avoid empty rediret type value
        if (!$redirectType) {
            $redirectType = '301';
        }
        //return false for URL Tracking
        if(preg_match("/fs_.*/", $url) || preg_match("/utm_.*/", $url) || preg_match("/gclid=.*/", $url)) {
            return false;
        }
        if (strpos(Mage::helper('core/url')->getCurrentUrl(), 'customer/account')) {
            return false;
        }
        //return false if redirect exist
        foreach (Mage::app()->getResponse()->getHeaders() as $header) {
            if ($header['name'] == 'Location') {
                return false;
            }
        }
        $redirectCode = (int)$redirectType;
        Mage::app()->getFrontController()->getResponse()
            ->setRedirect($url, $redirectCode)
            ->sendResponse();
        die;
    }

    public function addCategorySeoTab($e)
    {
        $tabs = $e->getTabs();
        if (!is_object($tabs->getCategory())) {
            return;
        }
        $ids  = $tabs->getTabsIds();

        $attributeSetId     = $tabs->getCategory()->getDefaultAttributeSetId();
        $groupCollection    = Mage::getResourceModel('eav/entity_attribute_group_collection')
            ->setAttributeSetFilter($attributeSetId)
            ->addFieldToFilter('attribute_group_name', 'SEO')
            ->load();
        $group = $groupCollection->getFirstItem();

        if ($group) {
            $tabs->removeTab('group_'.$group->getAttributeGroupId());
        }

        $tabs->addTab('seo', array(
            'label'     => Mage::helper('seo')->__('SEO'),
            'content'   => Mage::app()->getLayout()->createBlock(
                'seo/adminhtml_catalog_category_tab_seo',
                'category.seo'
            )->toHtml(),
        ));
    }

    //if we use SHORT or LONG url format we do a redirection of other url format
    public function checkProductUrlRedirect($e)
    {
        $urlFormat = $this->getConfig()->getProductUrlFormat();

        if ($urlFormat != Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT &&
            $urlFormat != Mirasvit_Seo_Model_Config::URL_FORMAT_LONG) {
                return;
        }

        if ($this->isAjax()){
            return;
        }

        $action = $e->getControllerAction();

        if ($action->getRequest()->getModuleName() != 'catalog') { //we redirecto only for catalog
            return;
        }
        if ($action->getRequest()->getControllerName() != 'product') { //we redirecto only for catalog
            return;
        }
        if ($action->getRequest()->getActionName() != 'view') { //we redirecto only from products page. not from images views.
            return;
        }

        //need to not alter product URLs with tracking parameters
        $currentUrl = Mage::helper('core/url')->getCurrentUrl(); 
        if(preg_match("/fs_.*/", $currentUrl) || preg_match("/utm_.*/", $currentUrl) || preg_match("/gclid=.*/", $currentUrl)) {
            return;
        }

        $url = ltrim($action->getRequest()->getRequestString(), '/');
        $product = $e->getProduct();
        //we need this because we need to load url rewrites
        //maybe its possible to optimize
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('entity_id', $product->getId());
        $product = $products->getFirstItem();
        $productUrl = str_replace(Mage::getBaseUrl(), '', $product->getProductUrl());
        $productUrl = $this->getUrlWithCorrectEndSlash($productUrl);

        if ($productUrl != $url) {
            $url = $product->getProductUrl();
            $url = $this->getUrlWithCorrectEndSlash($url);
            $this->redirect($url);
        }
    }

    public function setupProductUrls($e)
    {
        $collection = $e->getCollection();
        $this->_addUrlRewrite($collection);
    }

   /**
     * Add URL rewrites to collection
     *
     */
    protected function _addUrlRewrite($collection)
    {
        $urlFormat = $this->getConfig()->getProductUrlFormat();
        if ($urlFormat != Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT &&
            $urlFormat != Mirasvit_Seo_Model_Config::URL_FORMAT_LONG) {
                return;
        }

        $urlRewrites = null;

        if (!$urlRewrites) {
            $productIds = array();
            foreach($collection->getItems() as $item) {
                $productIds[] = $item->getEntityId();
            }

            if (!count($productIds)) {
                return;
            }

            $storeId = Mage::app()->getStore()->getId();
            if ($collection->getStoreId()) {
                $storeId = $collection->getStoreId();
            }

            if (Mage::helper('mstcore/version')->getEdition() != 'ee') { //we don't use Mirasvit_Seo_Model_Config::URL_FORMAT_LONG for EE
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
                $seoCatIds = array();

                //It used for Main Category for SEO. Category for SEO not empty and not null. Product URL = Include categories path to Product URLs.
                $attributeId = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'seo_category')->getAttributeId();
                if ($attributeId) {
                    if (Mage::helper('catalog/product_flat')->isEnabled()) {
                        $attrCollection = Mage::getModel('catalog/product')
                            ->setStoreId($storeId)
                            ->getCollection();

                        $table = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'seo_category')->getBackend()->getTable();
                        $attrCollection->getSelect()->join(array('attributeTable' => $table), 'e.entity_id = attributeTable.entity_id', array('seo_category' => 'attributeTable.value'))
                                        ->where("attributeTable.attribute_id = ?", $attributeId)
                                        ->where("attributeTable.value > 0")
                                        ;
                        if ($attrCollection->getSize() > 0) {
                            foreach ($attrCollection->getData('seo_category') as $attrItem) {
                                $seoCatIds[$attrItem['entity_id']] = $attrItem['seo_category'];
                            }
                        }
                    } else {
                        $attrCollection = Mage::getModel('catalog/product')
                            ->setStoreId($storeId)
                            ->getCollection()
                            ->addFieldToFilter('seo_category',array("neq" => ''))
                            ->addAttributeToSelect('seo_category')
                            ;

                        if ($attrCollection->getSize() > 0) {
                            foreach ($attrCollection->getData('seo_category') as $attrItem) {
                                $seoCatIds[$attrItem['entity_id']] = $attrItem['seo_category'];
                            }
                        }
                    }
                }

                $select = $connection->select()
                    ->from($tablePrefix.'core_url_rewrite', array('product_id', 'request_path', 'category_id'))
                    ->where('store_id = ?', $storeId)
                    ->where('is_system = ?', 1)
                    ->where('product_id IN(?)', $productIds)
                    ->order('category_id desc'); // more priority is data with category id

                if ($urlFormat == Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT) {
                    $select->where('category_id IS NULL');
                }

                $inactiveCat = Mage::helper('seo')->getInactiveCategories();
                $urlRewrites = array();
                foreach ($connection->fetchAll($select) as $row) {
                    if (!isset($urlRewrites[$row['product_id']])
                        && !in_array($row['category_id'], $inactiveCat)) {
                            if ($urlFormat == Mirasvit_Seo_Model_Config::URL_FORMAT_LONG) {
                                if (! empty($seoCatIds[$row['product_id']])) {
                                    if ($seoCatIds[$row['product_id']] == $row['category_id']) {
                                        $urlRewrites[$row['product_id']] = $row['request_path'];
                                    }
                                } else {
                                    $urlRewrites[$row['product_id']] = $row['request_path'];
                                }
                            }

                            if ($urlFormat == Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT) {
                                $urlRewrites[$row['product_id']] = $row['request_path'];
                            }
                    }
                }
            }

            foreach($collection->getItems() as $item) {
                if (isset($urlRewrites[$item->getEntityId()])) {
                    $item->setData('request_path', $urlRewrites[$item->getEntityId()]);
                } else {
                    $item->setData('request_path', false);
                }
            }
        }
    }

    public function setupPagingMeta()
    {
        if ($this->getConfig()->isPagingPrevNextEnabled()) {
            Mage::getModel('seo/paging')->createLinks();
        }
    }

    public function saveProductBefore($observer)
    {
        $product = $observer->getProduct();
        if ($product->getStoreId() == 0
        //~ && $product->getOrigData('url_key') != $product->getData('url_key')

        ) {
            Mage::getModel('seo/system_template_worker')->processProduct($product);
        }
    }

    public function httpResponseSendBeforeEvent($e)
    {
        Mage::getSingleton('seo/opengraph')->modifyHtmlResponse($e);
    }

    public function onCleanCatalogImagesCacheAfter($e)
    {
        if (!$this->getConfig()->getIsEnableImageFriendlyUrls()) {
            return;
        }
        $directory = Mage::getBaseDir('media') . DS.'product'.DS;
        $io = new Varien_Io_File();
        $io->rmdir($directory, true);

        Mage::helper('core/file_storage_database')->deleteFolder($directory);
    }

    public function updateUrlKeyByTemplateIfEmpty($observer) //if we use "Product URL Key Template" and "URL Key" = NULL we will create  URL Key by Template for Magento Enterprise
    {
        if (Mage::helper('mstcore/version')->getEdition() == 'ee' && Mage::getVersion() >= '1.13.0.0') {
            $product = $observer->getProduct();
            if (is_object($product)) {
                $config = $this->getConfig();
                $urlKeyTemplate = array();
                $storeObject    = array();
                foreach (Mage::app()->getStores(true) as $storeKey => $store) {
                    $urlKeyTemplate[$store->getId()] = $config->getProductUrlKey($store);
                    $storeObject[$store->getId()]  = $store;
                }
                $urlKeyTemplate = array_diff($urlKeyTemplate, array(''));
                if (count($urlKeyTemplate) > 0) {  // check if Product URL Key Template set
                    $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
                    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $select = $connection->select()
                                ->from($tablePrefix.'catalog_product_entity_url_key')
                                ->where("entity_id = ?", $product->getId())
                                ;
                    $row    = $connection->fetchAll($select);
                    if ($row) {
                        foreach ($row as $urlKeyValue) {
                            if (!$product->getName()) {
                                $product = Mage::getModel('catalog/product')->setStoreId($product->getStoreId())->load($product->getId());
                            }
                            if ($urlKeyValue['value'] == NULL
                                || $urlKeyValue['value'] == Mage::getSingleton('catalog/product_url')->formatUrlKey($product->getName())) {
                                $store = $storeObject[$urlKeyValue['store_id']];
                                $templ = Mage::getModel('seo/object_producturl')
                                        ->setProduct($product)
                                        ->setStore($store);

                                $urlKey = $templ->parse($urlKeyTemplate[$store->getId()]);
                                $urlKey = Mage::getSingleton('seo/system_template_worker')->formatUrlKey($urlKey);
                                if (!empty($urlKey)) {
                                    $urlKeyTable = 'catalog_product_entity_url_key';
                                    //check if url key is unique
                                    if ($urlKeyPrepared = Mage::getSingleton('seo/system_template_worker')->prepareUrlKeys($connection, $urlKey, $tablePrefix, $urlKeyTable)) {
                                        $urlKey = $urlKeyPrepared;
                                    }
                                    $select = $connection->select()->from($tablePrefix.'eav_entity_type')
                                                ->where("entity_type_code = 'catalog_product'")
                                                ;
                                    $productTypeId = $connection->fetchOne($select);
                                    $select = $connection->select()->from($tablePrefix.'eav_attribute')
                                                ->where("entity_type_id = $productTypeId AND (attribute_code = 'url_key')")
                                                ;
                                    $urlKeyId = $connection->fetchOne($select);
                                    $connection->update($tablePrefix.'catalog_product_entity_url_key', array('value' => $urlKey), "attribute_id = $urlKeyId AND entity_id = {$product->getId()} AND store_id = {$store->getId()}");
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}