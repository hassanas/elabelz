<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Block_Adminhtml_Column_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('form_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('mageworx_ordersgrid')->__('Grid Type'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('order', array(
            'label'     => Mage::helper('mageworx_ordersgrid')->__('Orders Grid'),
            'title'     => Mage::helper('mageworx_ordersgrid')->__('Orders Grid'),
            'content'   => $this->getLayout()->createBlock('mageworx_ordersgrid/adminhtml_column_edit_tab_order')->toHtml(),
        ));

        $this->addTab('customer', array(
            'label'     => Mage::helper('mageworx_ordersgrid')->__("Customer's Orders Tab"),
            'title'     => Mage::helper('mageworx_ordersgrid')->__("Customer's Orders Tab"),
            'content'   => $this->getLayout()->createBlock('mageworx_ordersgrid/adminhtml_column_edit_tab_customer')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}
