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



class Mirasvit_SeoAutolink_Block_Adminhtml_Widget_Renderer_Store extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Store
{
    /**
     * Render row store views.
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $out = '';
        $skipAllStoresLabel = $this->_getShowAllStoresLabelFlag();
        $skipEmptyStoresLabel = $this->_getShowEmptyStoresLabelFlag();
        $origStores = $row->getData($this->getColumn()->getIndex());

        $storeCollection = Mage::getModel('seoautolink/link')->getCollection();
        $storeCollection->getSelect()
                ->joinLeft(array('store_table' => Mage::getSingleton('core/resource')->getTableName('seoautolink/link_store')), 'main_table.link_id = store_table.link_id', array())
                ->where('store_table.link_id in (?)', array($origStores))
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('store_id', 'store_table');

        $origStores = array();
        foreach ($storeCollection as $store) {
            $origStores[] = $store->getStoreId();
        }

        if (is_null($origStores) && $row->getStoreName()) {
            $scopes = array();
            foreach (explode("\n", $row->getStoreName()) as $k => $label) {
                $scopes[] = str_repeat('&nbsp;', $k * 3).$label;
            }
            $out .= implode('<br/>', $scopes).$this->__(' [deleted]');

            return $out;
        }

        if (empty($origStores) && !$skipEmptyStoresLabel) {
            return '';
        }
        if (!is_array($origStores)) {
            $origStores = array($origStores);
        }

        if (empty($origStores)) {
            return '';
        } elseif (in_array(0, $origStores) && count($origStores) == 1 && !$skipAllStoresLabel) {
            return Mage::helper('adminhtml')->__('All Store Views');
        }

        $data = $this->_getStoreModel()->getStoresStructure(false, $origStores);

        foreach ($data as $website) {
            $out .= $website['label'].'<br/>';
            foreach ($website['children'] as $group) {
                $out .= str_repeat('&nbsp;', 3).$group['label'].'<br/>';
                foreach ($group['children'] as $store) {
                    $out .= str_repeat('&nbsp;', 6).$store['label'].'<br/>';
                }
            }
        }

        return $out;
    }
}
