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


class Mirasvit_Seo_Model_Snippets_Observer extends Varien_Object {
    protected $appliedSnippets         = false;
    protected $isProductPage           = false;
    protected $isCategoryPage          = false;
    protected $appliedCategorySnippets = false;
    protected $goodrelationsUrl        = 'http://purl.org/goodrelations/v1#';

    function __construct() {
        if(Mage::app()->getFrontController()->getRequest()->getControllerName() === "product") {
            $this->isProductPage = true;
        }
        if(Mage::app()->getFrontController()->getRequest()->getControllerName() === "category"
           || Mage::app()->getFrontController()->getRequest()->getModuleName() === "amlanding") {
            $this->isCategoryPage = true;
        }
    }

    public function getConfig()
    {
        return Mage::getSingleton('seo/config');
    }

    public function addProductSnippets($e) {
        if($this->isProductPage
            && !$this->appliedSnippets
            && $e->getData('block')->getNameInLayout() == "product.info"
            && $this->getConfig()->isRichSnippetsEnabled(Mage::app()->getStore()->getId())) {

            $html = $e->getData('transport')->getHtml();
            $product = $e->getData('block')->getProduct();

            if ($this->getConfig()->isDeleteWrongSnippets(Mage::app()->getStore()->getId())) {
                $html = $this->_deleteWrongSnippets($html);
            }

            if ($htmlOfferFilter = $this->offerFilter($html,$product)) {
                $html = $htmlOfferFilter;
            }

            if ($htmlProductFilter = $this->productFilter($html,$product)) {
                $html = $htmlProductFilter;
            }

            if ($htmlAggregateRatingFilter = $this->aggregateRatingFilter($html,$product)) {
                $html = $htmlAggregateRatingFilter;
            }

            $e->getData('transport')->setHtml(
                $html
            );

            $this->appliedSnippets = true;
        } elseif ($this->isCategoryPage
                && !$this->appliedCategorySnippets
                && $e->getData('block')->getNameInLayout() == "product_list"
                && $this->getConfig()->getCategoryRichSnippets(Mage::app()->getStore()->getId()) == Mirasvit_Seo_Model_Config::CATEGYRY_RICH_SNIPPETS_PAGE) {
                    $productCollection = $e->getData('block')->getLoadedProductCollection();
                    Mage::register('category_product_for_snippets', $productCollection);
                    $this->appliedCategorySnippets = true;
        }

        if ($e->getData('block')->getNameInLayout() == "breadcrumbs"
            && $this->getConfig()->isBreadcrumbs(Mage::app()->getStore()->getId()) == Mirasvit_Seo_Model_Config::BREADCRUMBS_WITHOUT_SEPARATOR) {
                $html = $e->getData('transport')->getHtml();
                if ($crumbs = $this->breadcrumbsFilter($html)) {
                    $html = $crumbs;
                }
                $e->getData('transport')->setHtml(
                    $html
                );
        }
    }

    protected function _deleteWrongSnippets($html) { //maybe need improvement
        $pattern = array('/itemprop="(.*?)"/ims',
                        '/itemprop=\'(.*?)\'/ims',
                        '/itemtype="(.*?)"/ims',
                        '/itemtype=\'(.*?)\'/ims',
                        '/itemscope="(.*?)"/ims',
                        '/itemscope=\'(.*?)\'/ims',
                        '/itemscope=\'\'/ims',
                        '/itemscope=""/ims',
                        '/itemscope\s/ims',
                        );
        $html = preg_replace($pattern,'',$html);

        return $html;
    }

    protected function _getRichSnippetsAttributeValue($attributeArray, $product) {
        $attributeValue = false;
        foreach ($attributeArray as $attributeName) {
            if ($attribute = $product->getResource()->getAttribute($attributeName)) {
                $attributeValue = trim($attribute->getFrontend()->getValue($product));
            }
            if ($attributeValue && $attributeValue != 'No' && $attributeValue != 'Нет') {
                return $attributeValue;
            }
        }

        return false;
    }

    public function productFilter($html, $product) {
        $html = preg_replace('/\\"product\\-name(.*?)\\"/i','"product-name $1" itemprop="name"',$html,1);
        $html = preg_replace('/\\"short\\-description\\"/i','"short-description" itemprop="description"',$html,1);

        $replacement = '';
        $attributeBrand  = false;
        $attributeModel  = false;
        $attributeColor  = false;
        $attributeHeight = false;
        $attributeWidth  = false;
        $attributeDepth  = false;
        if ($brands = $this->getConfig()->getRichSnippetsBrandAttributes()) {
            $attributeBrand = $this->_getRichSnippetsAttributeValue($brands, $product);
        }
        if ($attributeBrand) {
            $replacement .= '<meta itemprop="brand" content="'.$attributeBrand.'" />';
        }
        if ($models = $this->getConfig()->getRichSnippetsModelAttributes()) {
            $attributeModel = $this->_getRichSnippetsAttributeValue($models, $product);
        }
        if ($attributeModel) {
            $replacement .= '<meta itemprop="model" content="'.$attributeModel.'"/>';
        }
        if ($colors = $this->getConfig()->getRichSnippetsColorAttributes()) {
            $attributeColor = $this->_getRichSnippetsAttributeValue($colors, $product);
        }
        if ($attributeColor) {
            $replacement .= '<meta itemprop="color" content="'.$attributeColor.'"/>';
        }
        if ($sku = $product->getSku()) {
            $replacement .= '<meta itemprop="sku" content="'.$sku.'"/>';
        }
        if ($this->getConfig()->isEnabledRichSnippetsProductCategory()
            && ($categoryName = $this->_getCategoryName($product))) {
                $replacement .= '<meta itemprop="category" content="'.$categoryName.'"/>';
        }
        if (($weight = $product->getWeight()) && ($weightCode = $this->getConfig()->getRichSnippetsWeightCode())) {
            $replacement .='
            <span itemprop="weight" itemscope itemtype="http://schema.org/QuantitativeValue">
                <meta itemprop="value" content="'.$weight.'"/>
                <meta itemprop="unitCode" content="'.$weightCode.'">
            </span>';
        }
        if ($this->getConfig()->isEnabledRichSnippetsDimensions()) {
            $dimensionalUnit = $this->getConfig()->getRichSnippetsDimensionUnit();
            if ($dimensionalUnit) {
                $dimensionalUnit = Mage::helper('seo/snippets')->prepareDimensionCode($dimensionalUnit);
            }
            if ($height = $this->getConfig()->getRichSnippetsHeightAttributes()) {
                $attributeHeight = $this->_getRichSnippetsAttributeValue($height, $product);
            }
            if ($attributeHeight) {
                $replacement .= '
                <span itemprop="height" itemscope itemtype="http://schema.org/QuantitativeValue">
                    <meta itemprop="value" content="'.$attributeHeight.'"/>';
                if ($dimensionalUnit) {
                    $replacement .= '<meta itemprop="unitCode" content="'.$dimensionalUnit.'">';
                }
                $replacement .= '</span>';
            }

            if ($width  = $this->getConfig()->getRichSnippetsWidthAttributes()) {
                $attributeWidth = $this->_getRichSnippetsAttributeValue($width, $product);
            }
            if ($attributeWidth) {
                $replacement .= '
                <span itemprop="width" itemscope itemtype="http://schema.org/QuantitativeValue">
                     <meta itemprop="value" content="'.$attributeWidth.'"/>';
                if ($dimensionalUnit) {
                    $replacement .= '<meta itemprop="unitCode" content="'.$dimensionalUnit.'">';
                }
                $replacement .= '</span>';
            }

            if ($depth = $this->getConfig()->getRichSnippetsDepthAttributes()) {
                $attributeDepth = $this->_getRichSnippetsAttributeValue($depth, $product);
            }
            if ($attributeDepth) {
                $replacement .= '
                <span itemprop="depth" itemscope itemtype="http://schema.org/QuantitativeValue">
                     <meta itemprop="value" content="'.$attributeDepth.'"/>';
                if ($dimensionalUnit) {
                    $replacement .= '<meta itemprop="unitCode" content="'.$dimensionalUnit.'">';
                }
                $replacement .= '</span>';
            }
        }

        if ($image = Mage::helper('catalog/image')->init($product, 'image')) {
            $replacement .= '<meta itemprop="image" content="'.$image.'"/>';
        }

        if ($replacement) {
            $html = preg_replace('/\\<div class\\=\\"product\\-name/i', $replacement . '<div class="product-name',$html,1);
        }

        $html = '<div itemscope itemtype="http://schema.org/Product">'.$html.'</div>';
        return $html;
    }

    public function offerFilter($html, $product)
    {
        $availability    = "";
        $paymentMethods  = "";
        $deliveryMethods = "";
        $condition       = "";

        //availability
        if(method_exists ($product , "isAvailable" )) {
            $check = $product->isAvailable();
        } else {
            $check = $product->isInStock();
        }
        if ($check) {
            $availability .= '<link itemprop="availability" href="http://schema.org/InStock" />';
        } else {
            $availability .= '<link itemprop="availability" href="http://schema.org/OutOfStock" />';
        }

        //Payment Methods
        if ($this->getConfig()->isEnabledRichSnippetsPaymentMethod()
            && ($activePaymentMethods = $this->_getActivePaymentMethods())) {
                foreach ($activePaymentMethods as $method) {
                    $paymentMethods .= '<link itemprop="acceptedPaymentMethod" href="'.$method.'" />';
                }
        }

        //Delivery Methods
        if ($this->getConfig()->isEnabledRichSnippetsDeliveryMethod()
            && ($activeDeliveryMethods = $this->_getActiveDeliveryMethods())) {
            foreach ($activeDeliveryMethods as $method) {
                $deliveryMethods .= '<link itemprop="availableDeliveryMethod" href="'.$method.'" />';
            }
        }

        //Product Condition
        if($this->getConfig()->isEnabledRichSnippetsCondition() == Mirasvit_Seo_Model_Config::CONDITION_RICH_SNIPPETS_NEW_ALL) {
            $condition = '<link itemprop="itemCondition" href="http://schema.org/NewCondition" />';
        } elseif($this->getConfig()->isEnabledRichSnippetsCondition() == Mirasvit_Seo_Model_Config::CONDITION_RICH_SNIPPETS_CONFIGURE
            && ($conditionAttribute = $this->getConfig()->getRichSnippetsConditionAttribute())) {
                if($attributeCondition = $this->_getRichSnippetsAttributeValue($conditionAttribute, $product)) {
                    switch (strtolower($attributeCondition)) {
                        case (strtolower($this->getConfig()->getRichSnippetsNewConditionValue())):
                            $condition = '<link itemprop="itemCondition" href="http://schema.org/NewCondition" />';
                            break;
                        case (strtolower($this->getConfig()->getRichSnippetsUsedConditionValue())):
                            $condition = '<link itemprop="itemCondition" href="http://schema.org/UsedCondition" />';
                            break;
                        case (strtolower($this->getConfig()->getRichSnippetsRefurbishedConditionValue())):
                            $condition = '<link itemprop="itemCondition" href="http://schema.org/RefurbishedCondition" />';
                            break;
                        case (strtolower($this->getConfig()->getRichSnippetsDamagedConditionValue())):
                            $condition = '<link itemprop="itemCondition" href="http://schema.org/DamagedCondition" />';
                            break;
                    }
                }
        }

        //price
        $price = "";
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $productFinalPrice = Mage::helper('seo')->getCurrentProductFinalPrice($product, true);
        /* Google Structured Data Testing Tool throws Warning on price attribute with comma like price = 3,99 */
        if (substr_count($productFinalPrice, ',') + substr_count($productFinalPrice, '.') > 1) {
            $productFinalPrice = str_replace(',', '', $productFinalPrice);
        } elseif (strpos($productFinalPrice, ',') !== false) {
            $productFinalPrice = str_replace(',', '.', $productFinalPrice);
        }

        if(preg_match('/(special\\-price\\".*?)\\<span class\\=\\"price\\"(.*?)>(.*?)\\<\\/span\\>/ims',$html)) {
            if ($productFinalPrice) {
                $price = '<meta itemprop="priceCurrency" content="'.$currencyCode.'" />'.
                         '<meta itemprop="price" content="'.$productFinalPrice.'" />'.
                         '<span class="price" $2>$3</span>'
                        ;
                $replacement = '$1<span itemprop="offers" itemscope itemtype="http://schema.org/Offer">'.$condition.$deliveryMethods.$paymentMethods.$availability.$price.'</span>';
                $html = preg_replace('/(special\\-price\\".*?)\\<span class\\=\\"price\\"(.*?)\\>(.*?)\\<\\/span\\>/ims', $replacement, $html, 1);
            }
        } else {
            if ($productFinalPrice) {
                $price = '<meta itemprop="priceCurrency" content="'.$currencyCode.'" />'.
                         '<meta itemprop="price" content="'.$productFinalPrice.'" />'.
                         '<span class="price" $1>$2</span>'
                        ;
                $replacement = '<span itemprop="offers" itemscope itemtype="http://schema.org/Offer">'.$condition.$deliveryMethods.$paymentMethods.$availability.$price.'</span>';
                $html = preg_replace('/\\<span class\\=\\"price\\"(.*?)\\>(.*?)\\<\\/span\\>/ims', $replacement, $html, 1);
            }
        }

        return $html;
    }

    public function aggregateRatingFilter($html, $product) {
        if (!is_object($product->getRatingSummary())) {
            return false;
        }
        if ($product->getRatingSummary()->getRatingSummary() && $product->getRatingSummary()->getReviewsCount()) {
            $ratingData = '';
            if ($ratingValue = number_format($product->getRatingSummary()->getRatingSummary()/100*5, 2)) {
                $ratingData .= '<meta itemprop="ratingValue" content="'.$ratingValue.'" />';
            }
            if ($reviewsCount = $product->getRatingSummary()->getReviewsCount()) {
                $ratingData .= '<meta itemprop="reviewCount" content="'.$reviewsCount.'" />';
            }
            $html = preg_replace('/\\<div class\\=\\"ratings\\"(.*?)\\>/ims','<div class="ratings" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">'.$ratingData,$html,1);
            return $html;
        } else {
            return false;
        }
    }

    public function breadcrumbsFilter($html) {
            if (strpos($html, 'class="breadcrumbs"') !== false) {
                $liTagCount = substr_count($html, '<li');
                $html = preg_replace('/\\<li/','<li typeof="v:Breadcrumb"',$html, $liTagCount-1); // we don't add v:Breadcrumb in the final tag
                $html = preg_replace('/\\<a/','<a rel="v:url" property="v:title"',$html);
                return $html;
            }
            return false;
    }

    protected function _getCategoryName($product) {
        $categoryName = false;
        if ($category = Mage::registry('current_category')) {
            $categoryName = $category->getName();
        } else {
            $categoryIds = $product->getCategoryIds();
            $categoryIds = array_reverse($categoryIds);
            if (isset($categoryIds[0])) {
                $categoryName = Mage::getModel('catalog/category')
                                ->setStoreId(Mage::app()->getStore()->getStoreId())
                                ->load($categoryIds[0])
                                ->getName();
            }
        }

        return $categoryName;
    }

    protected function _getActiveDeliveryMethods()
    {
        $existingMethods = array();
        $methods = array(
            'flatrate'     => 'DeliveryModeFreight',
            'freeshipping' => 'DeliveryModeFreight',
            'tablerate'    => 'DeliveryModeFreight',
            'dhl'          => 'DHL',
            'fedex'        => 'FederalExpress',
            'ups'          => 'UPS',
            'usps'         => 'DeliveryModeMail',
            'dhlint'       => 'DHL',
        );

        $deliveryMethods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        foreach($deliveryMethods as $code => $method)
        {
            if (isset($methods[$code])) {
                $existingMethods[] = $this->goodrelationsUrl . $methods[$code];
            }
        }

        return array_unique($existingMethods);
    }

    protected function _getActivePaymentMethods()
    {
       $payments = Mage::getSingleton('payment/config')->getActiveMethods();
       $methods = array();
       foreach ($payments as $paymentCode => $paymentModel) {
            if (strpos($paymentCode, 'paypal') !== false) {
                $methods[] = $this->goodrelationsUrl . 'PayPal';
            }
            if (strpos($paymentCode, 'googlecheckout') !== false) {
                $methods[] = $this->goodrelationsUrl . 'GoogleCheckout';
            }
            if (strpos($paymentCode, 'cash') !== false) {
                $methods[] = $this->goodrelationsUrl . 'Cash';
            }
            if ($paymentCode == 'ccsave') {
                if ($existingMethods = $this->_getActivePaymentCctypes()) {
                    $methods = array_merge($methods, $existingMethods);
                }
            }
        }

        return array_unique($methods);
    }

    protected function _getActivePaymentCctypes() {
        $existingMethods = array();
        $methods = array(
            'AE'  => 'AmericanExpress',
            'VI'  => 'VISA',
            'MC'  => 'MasterCard',
            'DI'  => 'Discover',
            'JCB' => 'JCB',
        );

        if ($cctypes = Mage::getStoreConfig('payment/ccsave/cctypes', Mage::app()->getStore()->getStoreId())) {
            $cctypesArray = explode(",", $cctypes);
            foreach ($cctypesArray as $cctypeValue) {
                if (isset($methods[$cctypeValue])) {
                    $existingMethods[] = $this->goodrelationsUrl . $methods[$cctypeValue];
                }
            }
            return $existingMethods;
        }

        return false;
    }
}