<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_System_Config_Source_Cms_Block
{
    protected $_options;

    public function toOptionArray($isMultiselect = true)
    {
        if (!$this->_options) {

            $this->_options = array(
                array('value' => 0, 'label' => 'No')
            );

            $storeId = $this->getCurrentScopeId();
            $blocks = Mage::getResourceModel('cms/block_collection')
                ->addStoreFilter($storeId)
                ->load()
                ->toOptionArray();

            $this->_options = array_merge($this->_options, $blocks);
        }

        return $this->_options;
    }

    /**
     * Returns the store id of the currently selected scope
     *
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function getCurrentScopeId()
    {
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        } else {
            $store_id = 0;
        }

        return $store_id;
    }
}