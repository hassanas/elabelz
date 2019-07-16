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


class Mirasvit_SeoSitemap_Helper_Kb extends Mage_Core_Helper_Abstract
{
    public function getKbCategoryCollection($storeId = null)
    {
        $collection = Mage::getModel('kb/category')->getCollection()
            ->addFieldToFilter('is_active', true)
            ->addStoreIdFilter($storeId)
            ->setOrder('position', 'asc')
            ;
        return $collection;
    }

    public function getKbArticleCollection($categoryId, $storeId = null)
    {
        $collection = Mage::getModel('kb/article')->getCollection()
            ->addCategoryIdFilter($categoryId)
            ->addFieldToFilter('main_table.is_active', true)
            ->addStoreIdFilter($storeId)
            ->setOrder('position', 'asc')
            ;
        return $collection;
    }

    public function getKbHomeUrl($storeId = null)
    {
        return Mage::app()->getStore($storeId)->getBaseUrl() . Mage::getSingleton('kb/config')->getGeneralBaseUrl();
    }
}