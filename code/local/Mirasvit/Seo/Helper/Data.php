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


class Mirasvit_Seo_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_product;
    protected $_category;
    protected $_config;
    protected $_parseObjects    = array();
    protected $_additional      = array();
    protected $_storeId         = null;
    protected $_titlePage       = true;
    protected $_descriptionPage = true;

    protected static $_inactiveCat = array();

    public function __construct()
    {
        $this->_config = Mage::getModel('seo/config');
    }

    public function getBaseUri()
    {
        $baseStoreUri = parse_url(Mage::getUrl(), PHP_URL_PATH);

        if ($baseStoreUri  == '/') {
            return $_SERVER['REQUEST_URI'];
        } else {
            $requestUri = $_SERVER['REQUEST_URI'];
            $prepareUri = str_replace($baseStoreUri, '', $requestUri);
            if (substr($requestUri, 0, 1) == '/') {
                return $prepareUri;
            } else {
                return DS . $prepareUri;
            }
        }
    }

    public function checkRewrite($info = false)
    {
        $uid = Mage::helper('mstcore/debug')->start();
        $uri = $this->getBaseUri();
        $collection = Mage::getModel('seo/rewrite')->getCollection()
            ->addStoreFilter(Mage::app()->getStore())
            ->addEnableFilter();
        $resultRewrite = false;
        foreach ($collection as $rewrite) {
            if ($this->checkPattern($uri, $rewrite->getUrl())) {
                $resultRewrite = $rewrite;
                break;
            }
        }

        if ($info && $resultRewrite) {
            return $resultRewrite;
        } elseif ($info) {
            return false;
        }

        if ($resultRewrite) {
            $this->_addParseObjects();
            $resultRewrite->setTitle(Mage::helper('seo/parse')->parse($resultRewrite->getTitle(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $resultRewrite->setDescription(Mage::helper('seo/parse')->parse($resultRewrite->getDescription(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $resultRewrite->setMetaTitle(Mage::helper('seo/parse')->parse($resultRewrite->getMetaTitle(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $resultRewrite->setMetaKeywords(Mage::helper('seo/parse')->parse($resultRewrite->getMetaKeywords(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $resultRewrite->setMetaDescription(Mage::helper('seo/parse')->parse($resultRewrite->getMetaDescription(), $this->_parseObjects, $this->_additional, $this->_storeId));
        }

        Mage::helper('mstcore/debug')->end($uid, array(
            'uri'         => $uri,
            'rewrite_id'  => $resultRewrite? $resultRewrite->getId() : false,
            'rewrite_url' => $resultRewrite? $resultRewrite->getUrl() : false,
        ));

        return $resultRewrite;
    }

    protected function setAdditionalVariable($objectName, $variableName, $value)
    {
        $this->_additional[$objectName][$variableName] = $value;
        if (isset($this->_parseObjects['product'])) {
            if ($objectName.'_'.$variableName == 'product_final_price_minimal') {
                 $this->_parseObjects['product']->setData('final_price_minimal', $value);
            }
            if ($objectName.'_'.$variableName == 'product_final_price_range') {
                 $this->_parseObjects['product']->setData('final_price_range', $value);
            }
        }
    }

    protected function _addParseObjects() {
        if ($this->_parseObjects && $this->_storeId !== null) {
            return;
        }

        $this->_product = Mage::registry('current_product');
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        if ($this->_product) {
            $this->_parseObjects['product'] = $this->_product;
            $this->setAdditionalVariable('product', 'final_price', $this->_product->getFinalPrice());
            $this->setAdditionalVariable('product', 'url', $this->_product->getProductUrl());
            $this->setAdditionalVariable('product', 'final_price_minimal', Mage::helper('seo')->getCurrentProductFinalPrice($this->_product));
            $this->setAdditionalVariable('product', 'final_price_range', Mage::helper('seo')->getCurrentProductFinalPriceRange($this->_product));
        }

        $this->_category = Mage::registry('seo_current_category') ? Mage::registry('seo_current_category') : Mage::registry('current_category'); // "Main Category For SEO" option check

        $this->_parseObjects['store'] = Mage::getModel('seo/object_store');
        $this->_parseObjects['pager'] = Mage::getModel('seo/object_pager');
        $this->_parseObjects['filter'] = Mage::getModel('seo/object_wrapper_filter');

        if ($this->_category) {
            $this->_parseObjects['category'] = $this->_category;
            if($this->_category && $parent = $this->_category->setStoreId(Mage::app()->getStore()->getId())->getParentCategory()) {
                if (Mage::app()->getStore()->getRootCategoryId() != $parent->getId()) {
                    if (($parentParent = $parent->getParentCategory())
                        && (Mage::app()->getStore()->getRootCategoryId() != $parentParent->getId())) {
                        $this->setAdditionalVariable('category', 'parent_parent_name', $parentParent->getName());
                    }
                    $this->setAdditionalVariable('category', 'parent_name', $parent->getName());
                    $this->setAdditionalVariable('category', 'parent_url', $parent->getUrl());
                }
                $this->setAdditionalVariable('category', 'url', $this->_category->getUrl());
                //alias to meta_title
                $this->setAdditionalVariable('category', 'page_title', $this->_category->getMetaTitle());
            }
        }

        $this->_storeId = Mage::app()->getStore();

        return;
    }

    protected function _getElementApplied($productId, $categoryId, $item) {
        if ($productId) {
            $isElementApplied = Mage::getModel('seo/template')->getRule($item->getTemplateId())->isProductApplied($productId);
        } else  {
            $isElementApplied = Mage::getModel('seo/template')->getRule($item->getTemplateId())->isCategoryApplied($categoryId);
        }

        return $isElementApplied;
    }

    protected function _getTempalateRule($collection, $productId, $categoryId, $info) {
        $seoTemplateRule              = array();
        $sortOrderAppliedId           = false;
        $stopRulesProcessingAppliedId = false;

        foreach ($collection as $item) {
            if ($isElementApplied = $this->_getElementApplied($productId, $categoryId, $item)) {
                $seoTemplateRule[$item->getId()] = $item;
                if ($item->getStopRulesProcessing() && !$stopRulesProcessingAppliedId) {
                    $stopRulesProcessingAppliedId = $item->getId();
                }
                if ($item->getSortOrder() && !$stopRulesProcessingAppliedId) {
                    $sortOrderAppliedId = $item->getId();
                }
            }
        }

        if ($info) {
            if($stopRulesProcessingAppliedId) {
                $seoTemplateRule['applied']               = $stopRulesProcessingAppliedId; // stop rules processing
                $seoTemplateRule['stop_rules_processing'] = true;
            } elseif ($sortOrderAppliedId) {
                $seoTemplateRule['applied']    = $sortOrderAppliedId; // sort order
                $seoTemplateRule['sort_order'] = true;
            } elseif ($seoTemplateRule) {
                $seoTemplateRule['applied'] = key(array_slice($seoTemplateRule, -1, 1, true)); // maximal ID
            }

            return $seoTemplateRule;
        }

        if($stopRulesProcessingAppliedId) {
            $seoTemplateRule = $seoTemplateRule[$stopRulesProcessingAppliedId]; // stop rules processing
        } elseif ($sortOrderAppliedId) {
            $seoTemplateRule = $seoTemplateRule[$sortOrderAppliedId]; // sort order
        } else {
            $seoTemplateRule = array_pop($seoTemplateRule); // maximal ID
        }

        return $seoTemplateRule;
    }

    public function checkTempalateRule($isProduct, $isCategory, $isFilter, $info = false)
    {
       $seoTemplateRule = array();

       if ($isProduct) {
            // echo 'PRODUCTS_RULE';
            if (!Mage::registry('current_product')) {
                return false;
            }

            $collectionProduct = Mage::getModel('seo/template')->getCollection()
                                            ->addStoreFilter(Mage::app()->getStore())
                                            ->addFieldToFilter('rule_type', Mirasvit_Seo_Model_Config::PRODUCTS_RULE)
                                            ->addActiveFilter()
                                            ->addSortOrder();

            $seoTemplateRule = $this->_getTempalateRule($collectionProduct,
                                                        Mage::registry('current_product')->getId(),
                                                        false,
                                                        $info);
       } elseif ($isCategory && $isFilter) {
            // echo 'RESULTS_LAYERED_NAVIGATION_RULE';
            if (!Mage::registry('current_category')) {
                return false;
            }

            $collectionLayeredNavigation = Mage::getModel('seo/template')->getCollection()
                                                    ->addStoreFilter(Mage::app()->getStore())
                                                    ->addFieldToFilter('rule_type', Mirasvit_Seo_Model_Config::RESULTS_LAYERED_NAVIGATION_RULE)
                                                    ->addActiveFilter()
                                                    ->addSortOrder();

            $seoTemplateRule = $this->_getTempalateRule($collectionLayeredNavigation,
                                                        false,
                                                        Mage::registry('current_category')->getId(),
                                                        $info);
        } elseif ($isCategory) {
            // echo 'CATEGORIES_RULE';
            if (!Mage::registry('current_category')) {
                return false;
            }

            $categoryCollection = Mage::getModel('seo/template')->getCollection()
                                            ->addStoreFilter(Mage::app()->getStore())
                                            ->addFieldToFilter('rule_type', Mirasvit_Seo_Model_Config::CATEGORIES_RULE)
                                            ->addActiveFilter()
                                            ->addSortOrder();

            $seoTemplateRule = $this->_getTempalateRule($categoryCollection,
                                                        false,
                                                        Mage::registry('current_category')->getId(),
                                                        $info);
        }

        if ($info) {
            return $seoTemplateRule;
        }

        if ($seoTemplateRule) {
            $this->_addParseObjects();
            $seoTemplateRule->setTitle(Mage::helper('seo/parse')->parse($seoTemplateRule->getTitle(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $seoTemplateRule->setDescription(Mage::helper('seo/parse')->parse($seoTemplateRule->getDescription(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $seoTemplateRule->setShortDescription(Mage::helper('seo/parse')->parse($seoTemplateRule->getShortDescription(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $seoTemplateRule->setFullDescription(Mage::helper('seo/parse')->parse($seoTemplateRule->getFullDescription(), $this->_parseObjects, $this->_additional, $this->_storeId));
            $this->prepareMetaData($seoTemplateRule, $isCategory, $isFilter, $isProduct);
        }

        return $seoTemplateRule;
    }

    /**
     * Check MetaTitle, MetaKeywords and MetaDescription if "Use meta tags from categories if they are not empty"
     * or "Use meta tags from products if they are not empty" is enabled
     *
     * @param Mirasvit_Seo_Model_Template $seoTemplateRule
     * @param bool $isCategory
     * @param bool $isFilter
     * @param bool $isProduct
     *
     * @return Mirasvit_Seo_Model_Template Object
    */
    protected function prepareMetaData($seoTemplateRule, $isCategory, $isFilter, $isProduct) {
        $metaTitle = $seoTemplateRule->getMetaTitle();
        $metaKeywords = $seoTemplateRule->getMetaKeywords();
        $metaDescription = $seoTemplateRule->getMetaDescription();

        if ($this->_config->isCategoryMetaTagsUsed()
            && $isCategory && !$isProduct
            && ($category = Mage::registry('current_category'))) {
                $metaTitle = trim($category->getMetaTitle()) ? $category->getMetaTitle() : $seoTemplateRule->getMetaTitle();
                $metaKeywords = trim($category->getMetaKeywords()) ? $category->getMetaKeywords() : $seoTemplateRule->getMetaKeywords();
                $metaDescription = trim($category->getMetaDescription()) ? $category->getMetaDescription() : $seoTemplateRule->getMetaDescription();
        } elseif ($this->_config->isProductMetaTagsUsed() && $isProduct
                    && ($product = Mage::registry('current_product'))) {
                        $metaTitle = trim($product->getMetaTitle()) ? $product->getMetaTitle() : $seoTemplateRule->getMetaTitle();
                        $metaKeywords = trim($product->getMetaKeyword()) ? $product->getMetaKeyword() : $seoTemplateRule->getMetaKeywords();
                        $metaDescription = trim($product->getMetaDescription()) ? $product->getMetaDescription() : $seoTemplateRule->getMetaDescription();

        }

        $seoTemplateRule->setMetaTitle(Mage::helper('seo/parse')->parse($metaTitle, $this->_parseObjects, $this->_additional, $this->_storeId));
        $seoTemplateRule->setMetaKeywords(Mage::helper('seo/parse')->parse($metaKeywords, $this->_parseObjects, $this->_additional, $this->_storeId));
        $seoTemplateRule->setMetaDescription(Mage::helper('seo/parse')->parse($metaDescription, $this->_parseObjects, $this->_additional, $this->_storeId));

        return $seoTemplateRule;
    }

    /**
     * Возвращает сео-данные для текущей страницы
     *
     * Возвращает объект с методами:
     * getTitle() - заголовок H1
     * getDescription() - SEO текст
     * getMetaTitle()
     * getMetaKeyword()
     * getMetaDescription()
     *
     * Если для данной страницы нет СЕО, то возвращает пустой Varien_Object
     *
     * @return Varien_Object $result
     */
    public function getCurrentSeo()
    {
        if (Mage::app()->getStore()->getCode() == 'admin') {
            return new Varien_Object();
        }

        $uid = Mage::helper('mstcore/debug')->start();

        $isCategory = Mage::registry('current_category') || Mage::registry('category');
        $isProduct  = Mage::registry('current_product') || Mage::registry('product');
        $isFilter   = false;

        if ($isCategory) {
            $filters = Mage::getSingleton('catalog/layer')->getState()->getFilters();
            $isFilter = count($filters) > 0;
        }

        if ($isProduct) {
            $seo = Mage::getSingleton('seo/object_product');
        } elseif ($isCategory && $isFilter) {
            $seo =  Mage::getSingleton('seo/object_filter');
        } elseif ($isCategory) {
            $seo =  Mage::getSingleton('seo/object_category');
        } else {
            $seo = new Varien_Object();
        }

        if ($seoTempalate = $this->checkTempalateRule($isProduct, $isCategory, $isFilter)) {
            foreach ($seoTempalate->getData() as $k=>$v) {
                if ($v) {
                   $seo->setData($k, $v);
                }
            }
        }

        if ($seoRewrite = $this->checkRewrite()) {
            foreach ($seoRewrite->getData() as $k=>$v) {
                if ($v) {
                   $seo->setData($k, $v);
                }
            }
        }

        $storeId = Mage::app()->getStore()->getStoreId();
        $page    = Mage::app()->getFrontController()->getRequest()->getParam('p');
        if (!$page) {
            $page = 1;
        }

        if ($isCategory && !$isProduct) {
            if ($this->_titlePage) {
                switch ($this->_config->getMetaTitlePageNumber($storeId)) {
                    case Mirasvit_Seo_Model_Config::META_TITLE_PAGE_NUMBER_BEGIN:
                        if ($page > 1) {
                            $seo->setMetaTitle(Mage::helper('seo')->__("Page %s | %s", $page, $seo->getMetaTitle()));
                            $this->_titlePage = false;
                        }
                        break;
                    case Mirasvit_Seo_Model_Config::META_TITLE_PAGE_NUMBER_END:
                        if ($page > 1) {
                            $seo->setMetaTitle(Mage::helper('seo')->__("%s | Page %s", $seo->getMetaTitle(), $page));
                            $this->_titlePage = false;
                        }
                        break;
                    case Mirasvit_Seo_Model_Config::META_TITLE_PAGE_NUMBER_BEGIN_FIRST_PAGE:
                        $seo->setMetaTitle(Mage::helper('seo')->__("Page %s | %s", $page, $seo->getMetaTitle()));
                        $this->_titlePage = false;
                        break;
                    case Mirasvit_Seo_Model_Config::META_TITLE_PAGE_NUMBER_END_FIRST_PAGE:
                        $seo->setMetaTitle(Mage::helper('seo')->__("%s | Page %s", $seo->getMetaTitle(), $page));
                        $this->_titlePage = false;
                        break;
                }
            }

            if ($this->_descriptionPage) {
                switch ($this->_config->getMetaDescriptionPageNumber($storeId)) {
                    case Mirasvit_Seo_Model_Config::META_DESCRIPTION_PAGE_NUMBER_BEGIN:
                        if ($page > 1) {
                            $seo->setMetaDescription(Mage::helper('seo')->__("Page %s | %s", $page, $seo->getMetaDescription()));
                            $this->_descriptionPage = false;
                        }
                        break;
                    case Mirasvit_Seo_Model_Config::META_DESCRIPTION_PAGE_NUMBER_END:
                        if ($page > 1) {
                            $seo->setMetaDescription(Mage::helper('seo')->__("%s | Page %s", $seo->getMetaDescription(), $page));
                            $this->_descriptionPage = false;
                        }
                        break;
                    case Mirasvit_Seo_Model_Config::META_DESCRIPTION_PAGE_NUMBER_BEGIN_FIRST_PAGE:
                        $seo->setMetaDescription(Mage::helper('seo')->__("Page %s | %s", $page, $seo->getMetaDescription()));
                        $this->_descriptionPage = false;
                        break;
                    case Mirasvit_Seo_Model_Config::META_DESCRIPTION_PAGE_NUMBER_END_FIRST_PAGE:
                        $seo->setMetaDescription(Mage::helper('seo')->__("%s | Page %s", $seo->getMetaDescription(), $page));
                        $this->_descriptionPage = false;
                        break;
                }
            }

            if ($page > 1) {
                $seo->setDescription(''); //set an empty description for page with number > 1 (to not have a duplicate content)
            }
        }

        if ($metaTitleMaxLength = $this->_config->getMetaTitleMaxLength($storeId)) {
            $metaTitleMaxLength = (int)$metaTitleMaxLength;
            if ($metaTitleMaxLength < Mirasvit_Seo_Model_Config::META_TITLE_INCORRECT_LENGTH) {
                $metaTitleMaxLength = Mirasvit_Seo_Model_Config::META_TITLE_MAX_LENGTH; //recommended length
            }
            $seo->setMetaTitle($this->_getTruncatedString($seo->getMetaTitle(), $metaTitleMaxLength, $page));
        }

        if ($metaDescriptionMaxLength = $this->_config->getMetaDescriptionMaxLength($storeId)) {
            $metaDescriptionMaxLength = (int)$metaDescriptionMaxLength;
            if ($metaDescriptionMaxLength < Mirasvit_Seo_Model_Config::META_DESCRIPTION_INCORRECT_LENGTH) {
                $metaDescriptionMaxLength = Mirasvit_Seo_Model_Config::META_DESCRIPTION_MAX_LENGTH; //recommended length
            }
            $seo->setMetaDescription($this->_getTruncatedString($seo->getMetaDescription(), $metaDescriptionMaxLength, $page));
        }

        Mage::helper('mstcore/debug')->end($uid, $seo->getData());

        return $seo;
    }

    /**
     * Truncate string (don't truncate words)
     *
     * @param string $str The source string
     * @param int $length String limit
     * @return string
     */
    protected function _getTruncatedString($str, $length, $page) {
        $usePageNumber       = false;
        $delimiterSymbols    = array(';', '',' ', ',', '.', '!', '?', "\n", "\r", "\r\n");
        $delimiterEndSymbols = array(';', '',' ', ',', "\n", "\r", "\r\n");

        if (strpos($str, ' | Page '.$page) !== false) {
            $str = str_replace(' | Page '.$page, '', $str);
            $length -= strlen(' | Page '.$page);
            $usePageNumber = true;
        }

        $truncatedString = Mage::helper('core/string')->substr($str, 0, $length);

        if(($finalStringPart = str_replace($truncatedString, '', $str))
            && !in_array(substr($finalStringPart, 0, 1), $delimiterSymbols)) {
                $truncatedStringArray = explode(" ", $truncatedString);
                if (count($truncatedStringArray) > 1) {
                    array_pop($truncatedStringArray);
                }
                $truncatedString = implode(" ", $truncatedStringArray);
                if (in_array(substr($truncatedString, -1), $delimiterEndSymbols)) {
                    $truncatedString = substr($truncatedString, 0, -1);
                }
        }

        if ($usePageNumber) {
            $truncatedString .= ' | Page '.$page;
        }

        return $truncatedString;
    }

    //get SeoShortDescription for Sphinx Search
    public function getCurrentSeoShortDescriptionForSearch($product)
    {
        if (Mage::app()->getStore()->getCode() == 'admin') {
            return false;
        }

        $categoryIds = $product->getCategoryIds();
        $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();
        array_unshift($categoryIds, $rootCategoryId);
        $categoryIds = array_reverse($categoryIds);
        $storeId = Mage::app()->getStore()->getStoreId();
        $seoShortDescription = false;
        foreach ($categoryIds as $categoryId) {
            $category = Mage::getModel('catalog/category')->setStoreId($storeId)->load($categoryId);
            if ($seoShortDescription =  $category->getProductShortDescriptionTpl()) {
                break;
            }
        }

        if ($seoShortDescription) {
            $this->_parseObjects['product'] = $product;
            $seoShortDescription = Mage::helper('seo/parse')->parse($seoShortDescription, $this->_parseObjects, $this->_additional, $storeId);
        }

        return $seoShortDescription;
    }

    public function checkPattern($string, $pattern, $caseSensative = false)
    {
        if (!$caseSensative) {
            $string  = strtolower($string);
            $pattern = strtolower($pattern);
        }

        $parts = explode('*', $pattern);
        $index = 0;

        $shouldBeFirst = true;
        $shouldBeLast  = true;

        foreach ($parts as $part) {
            if ($part == '') {
                $shouldBeFirst = false;
                continue;
            }

            $index = strpos($string, $part, $index);

            if ($index === false) {
                return false;
            }

            if ($shouldBeFirst && $index > 0) {
                return false;
            }

            $shouldBeFirst = false;
            $index += strlen($part);
        }

        if (count($parts) == 1) {
            return $string == $pattern;
        }

        $last = end($parts);
        if ($last == '') {
            return true;
        }

        if (strrpos($string, $last) === false) {
            return false;
        }

        if(strlen($string) - strlen($last) - strrpos($string, $last) > 0) {
          return false;
        }

        return true;
    }

	public function cleanMetaTag($tag) {
        $tag = strip_tags($tag);
        //$tag = html_entity_decode($tag);//for case we have tags like &nbsp; added by some extensions //in some hosting adds unrecognized symbols
        //$tag = preg_replace('/[^a-zA-Z0-9_ \-()\/%-&]/s', '', $tag);
        $tag = preg_replace('/\s{2,}/', ' ', $tag); //remove unnecessary spaces
        $tag = preg_replace('/\"/', ' ', $tag); //remove " because it destroys html
        $tag = trim($tag);

	    return $tag;
	}

    public function getMetaRobotsByCode($code)
    {
        switch ($code) {
            case Mirasvit_Seo_Model_Config::NOINDEX_NOFOLLOW:
               return 'NOINDEX,NOFOLLOW';
            break;
            case Mirasvit_Seo_Model_Config::NOINDEX_FOLLOW:
               return 'NOINDEX,FOLLOW';
            break;
            case Mirasvit_Seo_Model_Config::INDEX_NOFOLLOW:
               return 'INDEX,NOFOLLOW';
            break;
            case Mirasvit_Seo_Model_Config::INDEX_FOLLOW:
               return 'INDEX,FOLLOW';
            break;
        };
    }

    public function getProductSeoCategory($product)
    {
        $categoryId = $product->getSeoCategory();
        $category = Mage::registry('current_category');

        if ($category && !$categoryId) {
            return $category;
        }

        if (!$categoryId) {
            $categoryIds = $product->getCategoryIds();
            if (count($categoryIds) > 0) {
                //we need this for multi websites configuration
                $categoryRootId = Mage::app()->getStore()->getRootCategoryId();
                $category = Mage::getModel('catalog/category')->getCollection()
                            ->addFieldToFilter('path', array('like' => "%/{$categoryRootId}/%"))
                            ->addFieldToFilter('entity_id', $categoryIds)
                            ->setOrder('level', 'desc')
                            ->setOrder('entity_id', 'desc')
                            ->getFirstItem()
                        ;
                $categoryId = $category->getId();
            }
        }
        //load category with flat data attributes
        $category = Mage::getModel('catalog/category')->load($categoryId);
        return $category;
    }

    public function getInactiveCategories() {
        $inactiveCategories = Mage::getModel('catalog/category')
                            ->getCollection()
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->addFieldToFilter('is_active', array('neq'=>'1'))
                            ->addAttributeToSelect('entity_id')
                        ;

        Mage::getSingleton('core/resource_iterator')->walk(
            $inactiveCategories->getSelect(),
            array(array($this, 'callbackValidateInactiveCategories'))
        );

        return self::$_inactiveCat;
    }

    public function callbackValidateInactiveCategories($args)
    {
        if (isset($args['row']['entity_id'])) {
            self::$_inactiveCat[] = $args['row']['entity_id'];
        }
    }

    public function getTagProductListUrl($params) {
        $request = Mage::app()->getRequest();
        $fullActionCode = $request->getModuleName().'_'.$request->getControllerName().'_'.$request->getActionName();
        if ($fullActionCode == 'tag_product_list') {
            $urlParams = array();
            if (isset($params['p']) && $params['p'] == 1) {
                unset($params['p']);
            }
            $urlParams['_query'] = $params;
            $urlKeysArray        = array(
                                    '_nosid' => true,
                                    '_type' => 'direct_link'
            );

            $urlParams = array_merge($urlParams, $urlKeysArray);
            $path      = Mage::getSingleton('core/url')->parseUrl(Mage::helper('core/url')->getCurrentUrl())->getPath();
            $path      = (substr($path, 0, 1) == '/') ? substr($path, 1) : $path;

            return Mage::getUrl($path, $urlParams);
        }

        return false;
    }

    public function getFullActionCode() {
        $request = Mage::app()->getRequest();
        return strtolower($request->getModuleName().'_'.$request->getControllerName().'_'.$request->getActionName());
    }

    public function isOnLandingPage() {
        return Mage::app()->getRequest()->getParam('am_landing');
    }

    public function getCanonicalUrl()
    {
        if (!$this->_config->isAddCanonicalUrl()) {
            return;
        }

        if (!Mage::app()->getFrontController()->getAction()) {
            return;
        }

        $fullAction = Mage::app()->getFrontController()->getAction()->getFullActionName();
        foreach ($this->_config->getCanonicalUrlIgnorePages() as $page) {
            if (Mage::helper('seo')->checkPattern($fullAction, $page)
                || Mage::helper('seo')->checkPattern(Mage::helper('seo')->getBaseUri(), $page)) {
                return;
            }
        }

        $productActions = array(
            'catalog_product_view',
            'review_product_list',
            'review_product_view',
            'productquestions_show_index',
        );

        $productCanonicalStoreId = false;
        $useCrossDomain          = true;

        if (in_array($fullAction, $productActions)) {
            $associatedProductId = false;
            $product = Mage::registry('current_product');
            if (!$product) {
                return;
            }

            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
                if ($this->_config->getAssociatedCanonicalConfigurableProduct()) {
                    if (($parentConfigurableProductIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId()))
                        && isset($parentConfigurableProductIds[0])) {
                            $associatedProductId = $parentConfigurableProductIds[0];
                    }
                }

                if (!$associatedProductId && $this->_config->getAssociatedCanonicalGroupedProduct()) {
                    if (($parentGroupedProductIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId()))
                        && isset($parentGroupedProductIds[0])) {
                            $associatedProductId = $parentGroupedProductIds[0];
                    }
                }
                if (!$associatedProductId && $this->_config->getAssociatedCanonicalBundleProduct()) {
                    if (($parentBundleProductIds = Mage::getModel('bundle/product_type')->getParentIdsByChild($product->getId()))
                        && isset($parentBundleProductIds[0])) {
                            $associatedProductId = $parentBundleProductIds[0];
                    }
                }
            }

            if ($associatedProductId) {
                $productId = $associatedProductId;
            } else {
                $productId = $product->getId();
            }

            $productCanonicalStoreId = $product->getSeoCanonicalStoreId(); //canonical store id for current product
            $canonicalUrlForCurrentProduct = trim($product->getSeoCanonicalUrl());

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('entity_id', $productId)
                ->addStoreFilter()
                ->addUrlRewrite();

            $product      = $collection->getFirstItem();
            $canonicalUrl = $product->getProductUrl();

            if ($canonicalUrlForCurrentProduct) {
                if (strpos($canonicalUrlForCurrentProduct, 'http://') !== false
                    || strpos($canonicalUrlForCurrentProduct, 'https://') !== false) {
                        $canonicalUrl = $canonicalUrlForCurrentProduct;
                        $useCrossDomain = false;
                } else {
                    $canonicalUrlForCurrentProduct = (substr($canonicalUrlForCurrentProduct, 0, 1) == '/') ? substr($canonicalUrlForCurrentProduct, 1) : $canonicalUrlForCurrentProduct;
                    $canonicalUrl = Mage::getBaseUrl() . $canonicalUrlForCurrentProduct;
                }
            }
        } elseif ($fullAction == 'catalog_category_view') {
            $category     = Mage::registry('current_category');
            if (!$category) {
                return;
            }
            $canonicalUrl = $category->getUrl();
        } elseif ($fullAction == 'blog_post_view' && Mage::helper('mstcore')->isModuleInstalled('AW_Blog')) {
            // need this if each post has "long"(blog category(es) included) and "short" URLs
            // canonical gets shortrer URL.
            $postBlockClass = Mage::getBlockSingleton('blog/post');
            $postIdentifier = $postBlockClass->getPost()->getIdentifier();
            $canonicalUrl = $postBlockClass->getBlogUrl($postIdentifier);
        } else {
            $canonicalUrl = Mage::helper('seo')->getBaseUri();
            $canonicalUrl = Mage::getUrl('', array('_direct' => ltrim($canonicalUrl, '/')));
            $canonicalUrl = strtok($canonicalUrl, '?');
            // fix canonical for homepage (www.site.com/eng/eng -> www.site.com/eng/ and www.site.com/home/ -> www.site.com/)
            if (Mage::getSingleton('cms/page')->getIdentifier() == 'home'
                && Mage::app()->getFrontController()->getRequest()->getRouteName() == 'cms') {
                $canonicalUrl = Mage::getBaseUrl();
            }
        }

        //setup crossdomian URL if this option is enabled
        if ((($crossDomainStore = $this->_config->getCrossDomainStore()) || $productCanonicalStoreId) && $useCrossDomain) {
            if ($productCanonicalStoreId) {
                $crossDomainStore = $productCanonicalStoreId;
            }
            $mainBaseUrl = Mage::app()->getStore($crossDomainStore)->getBaseUrl();
            $currentBaseUrl = Mage::app()->getStore()->getBaseUrl();
            $canonicalUrl = str_replace($currentBaseUrl, $mainBaseUrl, $canonicalUrl);

            if (Mage::app()->getStore()->isCurrentlySecure()) {
                $canonicalUrl = str_replace('http://', 'https://', $canonicalUrl);
            }
        }

        if (false && isset($product)) { //возможно в перспективе вывести это в конфигурацию. т.к. это нужно только в некоторых случаях.
            // если среди категорий продукта есть корневая категория, то устанавливаем ее для каноникал
            $categoryIds = $product->getCategoryIds();

            if (Mage::helper('catalog/category_flat')->isEnabled()) {
                $currentStore = Mage::app()->getStore()->getId();
                foreach (Mage::app()->getStores() as $store) {
                    Mage::app()->setCurrentStore($store->getId());
                    $collection = Mage::getModel('catalog/category')->getCollection()
                        ->addFieldToFilter('entity_id', $categoryIds)
                        ->addFieldToFilter('level', 1);
                    if ($collection->count()) {
                        $mainBaseUrl = $store->getBaseUrl();
                        break;
                    }
                }
                Mage::app()->setCurrentStore($currentStore);
                if (isset($mainBaseUrl)) {
                    $currentBaseUrl = Mage::app()->getStore()->getBaseUrl();
                    $canonicalUrl = str_replace($currentBaseUrl, $mainBaseUrl, $canonicalUrl);
                }
            } else {
                $collection = Mage::getModel('catalog/category')->getCollection()
                        ->addFieldToFilter('entity_id', $categoryIds)
                        ->addFieldToFilter('level', 1);
                if ($collection->count()) {
                    $rootCategory = $collection->getFirstItem();
                    foreach (Mage::app()->getStores() as $store) {
                          if ($store->getRootCategoryId() == $rootCategory->getId()) {
                            $mainBaseUrl = $store->getBaseUrl();
                            $currentBaseUrl = Mage::app()->getStore()->getBaseUrl();
                            $canonicalUrl = str_replace($currentBaseUrl, $mainBaseUrl, $canonicalUrl);
                          }
                    }
                }
            }
        }


        $page = (int)Mage::app()->getRequest()->getParam('p');
        if ($this->_config->isAddPaginatedCanonical() && $page > 1) {
            $canonicalUrl .= "?p=$page";
        } elseif ($page == 2) {
            $canonicalUrl .= " ";
        }

        return $canonicalUrl;
    }

    public function getCurrentProductFinalPrice($product, $noSymbol = false) {
        $productFinalPrice = false;
        $currencyCode      = Mage::app()->getStore()->getCurrentCurrencyCode();
        $priceModel        = $product->getPriceModel();

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            list($minimalPriceInclTax, $maximalPriceInclTax) = $priceModel->getPrices($product, null, true, false);
            if (($minimalPriceInclTax = $this->_formatPrice($minimalPriceInclTax, $noSymbol)) && $currencyCode) {
                $productFinalPrice = $minimalPriceInclTax;
            }
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            if (($minimalPriceValue = $this->_formatPrice($this->_getGroupedMinimalPrice($product), $noSymbol)) && $currencyCode) {
                $productFinalPrice = $minimalPriceValue;
            }
        } else {
            $finalPriceInclTax = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true);
            if (($finalPriceInclTax = $this->_formatPrice($finalPriceInclTax, $noSymbol)) && $currencyCode) {
                $productFinalPrice = $finalPriceInclTax;
            }
        }

        if ($productFinalPrice) {
            return $productFinalPrice;
        }

        return false;
    }

    public function getCurrentProductFinalPriceRange($product) {
        $productFinalPrice = false;
        $currencyCode      = Mage::app()->getStore()->getCurrentCurrencyCode();
        $priceModel        = $product->getPriceModel();

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            list($minimalPriceInclTax, $maximalPriceInclTax) = $priceModel->getPrices($product, null, true, false);
            if (($minimalPriceInclTax = $this->_formatPrice($minimalPriceInclTax, false))
                && ($maximalPriceInclTax = $this->_formatPrice($maximalPriceInclTax, false)) && $currencyCode) {
                    $productFinalPrice = $minimalPriceInclTax . ' - ' . $maximalPriceInclTax;
            }
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $productFinalPrice = $this->_getGroupedPriceRange($product);
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $productFinalPrice = $this->_getConfigurablePriceRange($product);
        } else {
            $finalPriceInclTax = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true);
            if (($finalPriceInclTax = $this->_formatPrice($finalPriceInclTax, false)) && $currencyCode) {
                $productFinalPrice = $finalPriceInclTax;
            }
        }

        if ($productFinalPrice) {
            return $productFinalPrice;
        }

        return false;
    }

    protected function _formatPrice($price, $noSymbol = true)
    {
        $displaySymbol = $noSymbol ? array('display'=>Zend_Currency::NO_SYMBOL) : array('display'=>Zend_Currency::USE_SYMBOL);
        if (intval($price) || is_float($price)) {
            $price = Mage::getModel('directory/currency')->format(
                $price,
                $displaySymbol,
                false
            );

            return $price;
        }

        return false;
    }

    protected function _getGroupedMinimalPrice($product)
    {
        $product = Mage::getModel('catalog/product')->getCollection()
            ->addMinimalPrice()
            ->addFieldToFilter('entity_id',$product->getId())
            ->getFirstItem();

        return Mage::helper('tax')->getPrice($product, $product->getMinimalPrice(), $includingTax = true);
    }

    protected function _getGroupedPriceRange($product)
    {
        $groupedPrices      = array();
        $groupedPriceRange  = false;
        $typeInstance       = $product->getTypeInstance(true);
        $associatedProducts = $typeInstance->setStoreFilter($product->getStore(), $product)
                                ->getAssociatedProducts($product);

        foreach ($associatedProducts as $childProduct) {
            if ($childProduct->isAvailable() && ($childProduct->isSaleable() || $childProduct->getIsInStock() > 0)) {
                $groupedPrices[] = $childProduct->getFinalPrice(1);
            }
        }
        if (count($groupedPrices)
            && ($minGroupedPrice = min($groupedPrices))
            && ($maxGroupedPrice = max($groupedPrices))
            && $minGroupedPrice != $maxGroupedPrice) {
                $groupedPriceRange = $this->_formatPrice(Mage::helper('tax')->getPrice($product, $minGroupedPrice, $includingTax = true), false)
                                    . ' - ' . $this->_formatPrice(Mage::helper('tax')->getPrice($product, $maxGroupedPrice, $includingTax = true), false);
        } elseif (count($groupedPrices) && ($minGroupedPrice = min($groupedPrices))) {
            $groupedPriceRange = $this->_formatPrice(Mage::helper('tax')->getPrice($product, $minGroupedPrice, $includingTax = true), false);
        } else {
            $groupedPriceRange = $this->_getGroupedMinimalPrice($product);
        }

        return $groupedPriceRange;
    }

    protected function _getConfigurablePriceRange($product)
    {
        $configurablePriceRange  = false;
        $configurablePrices      = array();
        $pricesByAttributeValues = array();
        $typeInstance            = $product->getTypeInstance();
        $baseConfigurablePrice   = $product->getFinalPrice();
        $attributes              = $typeInstance->getConfigurableAttributes($product);

        foreach ($attributes as $attribute){
            if($prices = $attribute->getPrices()) {
                foreach ($prices as $price){
                    if ($price['is_percent']){ //if the price is specified in percents
                        $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $baseConfigurablePrice / 100;
                    }
                    else { //if the price is absolute value
                        $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                    }
                }
            }
        }

        $associatedProducts = $typeInstance->getUsedProducts();
        foreach ($associatedProducts as $childProduct){
            $childProductPrice = $baseConfigurablePrice;
            foreach ($attributes as $attribute){
                $specificAttributeValue = $childProduct->getData($attribute->getProductAttribute()->getAttributeCode());
                if (isset($pricesByAttributeValues[$specificAttributeValue])){
                    $childProductPrice += $pricesByAttributeValues[$specificAttributeValue];
                }
            }
            if ($childProduct->isAvailable() && ($childProduct->isSaleable() || $childProduct->getIsInStock() > 0)) {
                $configurablePrices[] = $childProductPrice;
            }
        }

        if (count($configurablePrices)
            && ($minConfigurablePrice = min($configurablePrices))
            && ($maxConfigurablePrice = max($configurablePrices))
            && $minConfigurablePrice != $maxConfigurablePrice) {
                $configurablePriceRange = $this->_formatPrice(Mage::helper('tax')->getPrice($product, $minConfigurablePrice, $includingTax = true), false)
                                        . ' - ' . $this->_formatPrice(Mage::helper('tax')->getPrice($product, $maxConfigurablePrice, $includingTax = true), false);
        } elseif (count($configurablePrices) && ($minConfigurablePrice = min($configurablePrices))) {
            $configurablePriceRange = $this->_formatPrice(Mage::helper('tax')->getPrice($product, $minConfigurablePrice, $includingTax = true), false);
        }

        return $configurablePriceRange;
    }
}
