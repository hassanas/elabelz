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


class Mirasvit_Seo_Block_Richsnippet extends Mage_Core_Block_Template
{
    protected $isCategoryFilterChecked = false;
    protected $categorySnippetsRating;
    protected $categorySnippetsRatingCount;

    public function getConfig()
    {
    	return Mage::getSingleton('seo/config');
    }

    protected function _toHtml()
    {
        if (!$this->getConfig()->getCategoryRichSnippets(Mage::app()->getStore()->getId())) {
            return;
        }

        return parent::_toHtml();
    }

    public function categorySnippetsFilter() {
        if ($this->getConfig()->getCategoryRichSnippets(Mage::app()->getStore()->getId()) == Mirasvit_Seo_Model_Config::CATEGYRY_RICH_SNIPPETS_PAGE) {
            if (!Mage::registry('category_product_for_snippets')) {
                $this->isCategoryFilterChecked = true;
                return false;
            }

            $productCollection = Mage::registry('category_product_for_snippets');
            if($productCollection->count()) {
                $price        = array();
                $rating       = array();
                $reviewsCount = 0;
                foreach ($productCollection as $product) {
                    if (is_object($product->getRatingSummary())) {
                        if ($product->getRatingSummary()->getReviewsCount() > 0) {
                            $reviewsCount += $product->getRatingSummary()->getReviewsCount();
                        }
                        if ($product->getRatingSummary()->getRatingSummary() > 0) {
                            $rating[] = $product->getRatingSummary()->getRatingSummary();
                        }
                    }
                    if ($product->getFinalPrice() > 0) {
                        $price [] = $product->getFinalPrice();
                    } elseif ($product->getMinimalPrice() > 0) {
                        $price[] = $product->getMinimalPrice();
                    }
                }
                if (count($price) > 0) {
                    $this->categorySnippetsPrice = min($price);
                }

                if (array_sum($rating) > 0) {
                    $rating = array_filter($rating);
                    $summaryRating = array_sum($rating);
                    if ($this->getConfig()->getRichSnippetsRewiewCount(Mage::app()->getStore()->getStoreId()) == Mirasvit_Seo_Model_Config::REVIEWS_NUMBER && !empty($rating)) {
                        $this->categorySnippetsRatingCount = $reviewsCount;
                        $this->categorySnippetsRating = $summaryRating/count($rating);
                    } elseif(!empty($rating)) { //Mirasvit_Seo_Model_Config::PRODUCTS_WITH_REVIEWS_NUMBER
                       $this->categorySnippetsRatingCount = count($rating);
                       $this->categorySnippetsRating = $summaryRating/$this->categorySnippetsRatingCount;
                    }
                }
            }
        }

        if ($this->getConfig()->getCategoryRichSnippets(Mage::app()->getStore()->getId()) == Mirasvit_Seo_Model_Config::CATEGYRY_RICH_SNIPPETS_CATEGORY) {
            $currentCategory = false;
            if ($currentCategoryId = Mage::app()->getRequest()->getParam('cat')) { //Mage::registry('current_category')->getId() return parent category id if filter category enabled
                $currentCategory = Mage::getModel('catalog/category')->load($currentCategoryId);
            }
            if (!$currentCategory && Mage::registry('current_category')) {
                $currentCategory = Mage::registry('current_category');
            }
            if (!$currentCategory) {
                return false;
            }

            $minPriceProductCollection = Mage::getModel('catalog/product')
                                        ->getCollection()
                                        ->addAttributeToSelect('*')
                                        ->addStoreFilter(Mage::app()->getStore()->getStoreId())
                                        ->addCategoryFilter($currentCategory)
                                        ->addAttributeToFilter('visibility', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
                                                                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
                                        ->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                                        ->joinField('rating_summary', 'review/review_aggregate', 'rating_summary', 'entity_pk_value=entity_id',
                                                    array('entity_type' => 1, 'store_id' => Mage::app()->getStore()->getId()), 'left')
                                        ->joinField('reviews_count', 'review/review_aggregate', 'reviews_count', 'entity_pk_value=entity_id',
                                                    array('entity_type' => 1, 'store_id' => Mage::app()->getStore()->getId()), 'left')
                                        ->addFieldToFilter('price', array("gt" => 0))
                                        ->setOrder('price', 'ASC');

            if ($minPriceProductCollection->getSize() > 0) {
                $minPriceProductCollection->setPage(0,1);
                $this->categorySnippetsPrice = $minPriceProductCollection->getFirstItem()->getFinalPrice();
            }

            $productRatingCollection = Mage::getModel('catalog/product')
                                        ->getCollection()
                                        ->addAttributeToSelect('*')
                                        ->addStoreFilter(Mage::app()->getStore()->getStoreId())
                                        ->addCategoryFilter($currentCategory)
                                        ->addAttributeToFilter('visibility', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
                                                                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
                                        ->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                                        ->joinField('rating_summary', 'review/review_aggregate', 'rating_summary', 'entity_pk_value=entity_id',
                                                    array('entity_type' => 1, 'store_id' => Mage::app()->getStore()->getId()), 'left')
                                        ->joinField('reviews_count', 'review/review_aggregate', 'reviews_count', 'entity_pk_value=entity_id',
                                                    array('entity_type' => 1, 'store_id' => Mage::app()->getStore()->getId()), 'left')
                                        ->addFieldToFilter('price', array("gt" => 0))
                                        ->setOrder('price', 'ASC')
                                        ->addFieldToFilter('rating_summary', array("nin" => array(0, NULL)));

            if ($productRatingCollection->getSize() > 0) {
                $rating                   = $productRatingCollection->getColumnValues('rating_summary');
                $rating                   = array_diff($rating, array(0, null)); // double check, for some stores addFieldToFilter('rating_summary', array("nin" => array(0, NULL))) return also empty fields
                $summaryRating            = array_sum($rating);

                if ($this->getConfig()->getRichSnippetsRewiewCount(Mage::app()->getStore()->getStoreId()) == Mirasvit_Seo_Model_Config::REVIEWS_NUMBER && !empty($rating)) {
                    $this->categorySnippetsRatingCount = array_sum($productRatingCollection->getColumnValues('reviews_count'));
                    $this->categorySnippetsRating      = $summaryRating/count($rating);
                } elseif (!empty($rating)) { //Mirasvit_Seo_Model_Config::PRODUCTS_WITH_REVIEWS_NUMBER
                    $this->categorySnippetsRatingCount = count($rating);
                    $this->categorySnippetsRating      = $summaryRating/$this->categorySnippetsRatingCount;
                }
            }
        }

        $this->isCategoryFilterChecked = true;
    }

    public function getCategorySnippetsPrice() {
        if (!$this->isCategoryFilterChecked) {
            $this->categorySnippetsFilter();
        }

        return $this->categorySnippetsPrice;
    }

    public function getCategorySnippetsRating() {
        if (!$this->isCategoryFilterChecked) {
            $this->categorySnippetsFilter();
        }

        return $this->categorySnippetsRating;
    }

    public function getCategorySnippetsRatingCount() {
        if (!$this->isCategoryFilterChecked) {
            $this->categorySnippetsFilter();
        }

        return $this->categorySnippetsRatingCount;
    }

    public function getSnippetsPriceLabel() {
        return $this->getConfig()->getCategoryRichSnippetsPriceText(Mage::app()->getStore()->getId());
    }

    public function getSnippetsRatingLabel() {
        return $this->getConfig()->getCategoryRichSnippetsRatingText(Mage::app()->getStore()->getId());
    }

    public function getSnippetsRewiewCountLabel() {
        return $this->getConfig()->getCategoryRichSnippetsRewiewCountText(Mage::app()->getStore()->getId());
    }

    public function isHide() {
        // return $this->getConfig()->isHideCategoryRichSnippets(Mage::app()->getStore()->getId()); //most likely this functional will be deleted in future
        return false; // Category Rich Snippets won't be shown by Google is hidden
    }
}
