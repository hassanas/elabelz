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


class Mirasvit_Seo_Model_Enterprise_Rewrite_Product_Url extends Enterprise_Catalog_Model_Product_Url
{
    /**
     * Retrieve product URL based on requestPath param
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $requestPath
     * @param array $routeParams
     *
     * @return string
     */
    protected function _getProductUrl($product, $requestPath, $routeParams)
    {
        $categoryId = $this->_getCategoryIdForUrl($product, $routeParams);

        if (!empty($requestPath)) {
            if ($categoryId) {
                $category = $this->_factory->getModel('catalog/category')->load($categoryId);
                if ($category->getId()) {
                    $categoryRewrite = $this->_factory->getModel('enterprise_catalog/category')
                        ->loadByCategory($category);
                    if ($categoryRewrite->getId()) {
                        if (Mage::getSingleton('seo/config')->getProductUrlFormat() == Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT) {
                            $requestPath = $requestPath;
                        } else {
                            $requestPath = $categoryRewrite->getRequestPath() . '/' . $requestPath;
                        }
                    }
                }
            }

            $storeId = $this->getUrlInstance()->getStore()->getId();
            $requestPath = $this->_factory->getHelper('enterprise_catalog')
                ->getProductRequestPath($requestPath, $storeId);

            return $this->getUrlInstance()->getDirectUrl($requestPath, $routeParams);
        }

        $routeParams['id'] = $product->getId();
        $routeParams['s'] = $product->getUrlKey();
        if ($categoryId) {
            $routeParams['category'] = $categoryId;
        }
        return $this->getUrlInstance()->getUrl('catalog/product/view', $routeParams);
    }
}