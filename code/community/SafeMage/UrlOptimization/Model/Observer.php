<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_Observer
{
    // refresh product url rewrite
    public function productSaveCommitAfter(Varien_Event_Observer $observer) {
        $product = $observer->getEvent()->getProduct();
        if (!$product->getId() || !Mage::helper('safemage_urloptimization')->checkDisabledUrl()) {
            return $this;
        }
        if ($product->dataHasChangedFor('status') || $product->dataHasChangedFor('visibility')) {
            Mage::getSingleton('catalog/url')->refreshProductRewrite($product->getId());
        }
    }

    // refresh category url rewrite
    public function categorySaveCommitAfter(Varien_Event_Observer $observer) {
        $category = $observer->getEvent()->getCategory();
        if (!$category->getId() || !Mage::helper('safemage_urloptimization')->checkDisabledUrl()) {
            return $this;
        }
        if ($category->dataHasChangedFor('is_active')) {
            Mage::getSingleton('catalog/url')->refreshCategoryRewrite($category->getId());
        }
    }
}
