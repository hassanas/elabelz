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


class Mirasvit_Seo_Model_Resource_Template_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('seo/template');
    }

    public function toOptionArray()
    {
        $this->addFieldToFilter('is_active', 1)
            ->setOrder('sort_order', 'asc');
        return $this->_toOptionArray('template_id');
    }

    public function addActiveFilter() {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function addStoreFilter($store)
    {
        if (!Mage::app()->isSingleStoreMode()) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            $this->getSelect()
                ->joinLeft(array('store_table' => $this->getTable('seo/template_store')), 'main_table.template_id = store_table.template_id', array())
                ->where('store_table.store_id in (?)', array(0, $store));

            return $this;
        }
        return $this;
    }

    public function addSortByTemplateId()
    {
        $this->getSelect()->order(new Zend_Db_Expr('template_id asc'));
        return $this;
    }

    public function addSortOrder()
    {
        $this->getSelect()->order(new Zend_Db_Expr('sort_order asc, template_id asc'));
        return $this;
    }
}