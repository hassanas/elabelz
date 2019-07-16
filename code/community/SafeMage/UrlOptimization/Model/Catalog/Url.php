<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_Catalog_Url extends Mage_Catalog_Model_Url
{
    /**
     * Refresh product rewrite
     *
     * @param Varien_Object $product
     * @param Varien_Object $category
     * @return Mage_Catalog_Model_Url
     */
    protected function _refreshProductRewrite(Varien_Object $product, Varien_Object $category)
    {
        $checkDisabledUrl = Mage::helper('safemage_urloptimization')->checkDisabledUrl();

        // remove form core_url_rewrite DISABLED or NOT_VISIBLE product
        if ($checkDisabledUrl &&
            ($product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED ||
            $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
        ) {
            $this->getResource()->clearProductRewrites($product->getId(), $category->getStoreId());
            return $this;
        }

        // remove form core_url_rewrite inactive category
        if ($checkDisabledUrl && !is_null($category->getIsActive()) && !$category->getIsActive()) {
            $this->getResource()
                ->deleteCategoryProductStoreRewrites($category->getId(), $product->getId(), $category->getStoreId());
            return $this;
        }

        return parent::_refreshProductRewrite($product, $category);
    }

    /**
     * Get unique product request path
     *
     * @param   Varien_Object $product
     * @param   Varien_Object $category
     * @return  string
     */
    public function getProductRequestPath($product, $category)
    {
        if ($product->getUrlKey() == '') {
            $urlKey = $this->getProductModel()->formatUrlKey($product->getName());
        } else {
            $urlKey = $this->getProductModel()->formatUrlKey($product->getUrlKey());
        }
        $storeId = $category->getStoreId();
        $suffix  = $this->getProductUrlSuffix($storeId);
        $idPath  = $this->generatePath('id', $product, $category);
        /**
         * Prepare product base request path
         */
        if ($category->getLevel() > 1) {
            // To ensure, that category has path either from attribute or generated now
            $this->_addCategoryUrlPath($category);
            $categoryUrl = Mage::helper('catalog/category')->getCategoryUrlPath($category->getUrlPath(),
                false, $storeId);
            $requestPath = $categoryUrl . '/' . $urlKey;
        } else {
            $requestPath = $urlKey;
        }

        if (strlen($requestPath) > self::MAX_REQUEST_PATH_LENGTH + self::ALLOWED_REQUEST_PATH_OVERFLOW) {
            $requestPath = substr($requestPath, 0, self::MAX_REQUEST_PATH_LENGTH);
        }

        $this->_rewrite = null;
        /**
         * Check $requestPath should be unique
         */
        if (isset($this->_rewrites[$idPath])) {
            $this->_rewrite = $this->_rewrites[$idPath];
            $existingRequestPath = $this->_rewrites[$idPath]->getRequestPath();

            if ($existingRequestPath == $requestPath . $suffix
                || $existingRequestPath == $requestPath . '-' . $product->getId() . $suffix
            ) {
                return $existingRequestPath;
            }

            $existingRequestPath = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $existingRequestPath);
            /**
             * Check if existing request past can be used
             */
            if (!empty($requestPath) && strpos($existingRequestPath, $requestPath) === 0) {
                $existingRequestPath = preg_replace(
                    '/^' . preg_quote($requestPath, '/') . '/', '', $existingRequestPath
                );
                if (preg_match('#^-([0-9]+)$#i', $existingRequestPath)) {
                    return $this->_rewrites[$idPath]->getRequestPath();
                }
            }

            $fullPath = $requestPath.$suffix;
            if ($this->_deleteOldTargetPath($fullPath, $idPath, $storeId)) {
                return $fullPath;
            }
        }
        /**
         * Check 2 variants: $requestPath and $requestPath . '-' . $productId
         */
        $validatedPath = $this->getResource()->checkRequestPaths(
            array($requestPath.$suffix, $requestPath.'-'.$product->getId().$suffix),
            $storeId
        );

        if ($validatedPath) {
            return $validatedPath;
        }

        /**
         * Use unique path generator
         */
        return $this->getUnusedPath($storeId, $requestPath.$suffix, $idPath);
    }

    /**
     * Get unique category request path
     *
     * @param Varien_Object $category
     * @param string $parentPath
     * @return string
     */
    public function getCategoryRequestPath($category, $parentPath)
    {	
        $storeId = $category->getStoreId();
        $idPath  = $this->generatePath('id', null, $category);
        $suffix  = $this->getCategoryUrlSuffix($storeId);

        if (isset($this->_rewrites[$idPath])) {
            $this->_rewrite = $this->_rewrites[$idPath];
            $existingRequestPath = $this->_rewrites[$idPath]->getRequestPath();
        }

        if ($category->getUrlKey() == '') {
            $urlKey = $this->getCategoryModel()->formatUrlKey($category->getName());
        } else {
            $urlKey = $this->getCategoryModel()->formatUrlKey($category->getUrlKey());
        }

        if (null === $parentPath) {
            $parentPath = $this->getResource()->getCategoryParentPath($category);
        } elseif ($parentPath == '/') {
            $parentPath = '';
        }
        $parentPath = Mage::helper('catalog/category')
            ->getCategoryUrlPath($parentPath, true, $category->getStoreId());

        $requestPath = $parentPath . $urlKey . $suffix;
        
        if (isset($existingRequestPath) && $existingRequestPath == $requestPath) {
            return $existingRequestPath;
        }

        /**
         * Check if existing request past can be used
         */
        if (isset($this->_rewrites[$idPath]) && $requestPath && $existingRequestPath) {
            $existingRequestPath = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $existingRequestPath);
            $requestPathWithoutSuffix = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $requestPath);
            if ($existingRequestPath && strpos($existingRequestPath, $requestPathWithoutSuffix) === 0) {
                $existingRequestPathEnding = preg_replace(
                    '/^' . preg_quote($requestPathWithoutSuffix, '/') . '/', '', $existingRequestPath
                );
                if (preg_match('#^-([0-9]+)$#i', $existingRequestPathEnding)) {
                    return $this->_rewrites[$idPath]->getRequestPath();
                }
            }
        }

        if ($this->_deleteOldTargetPath($requestPath, $idPath, $storeId)) {
            return $requestPath;
        }

        return $this->getUnusedPath($category->getStoreId(), $requestPath,
            $this->generatePath('id', null, $category)
        );
    }
}
