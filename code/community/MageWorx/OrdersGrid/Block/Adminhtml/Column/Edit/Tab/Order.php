<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Block_Adminhtml_Column_Edit_Tab_Order extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct() {
        parent::__construct();
        $this->setTemplate('mageworx/ordersgrid/columns.phtml');
    }
    
    public function getTabLabel() {
        return Mage::helper('sales')->__('Order');
    }
   
    public function getTabTitle() {
        return Mage::helper('sales')->__('Order');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        $helper = Mage::helper('mageworx_ordersgrid');
        return $helper->getColumnSettings($helper::XML_GRID_TYPE_ORDER);
    }

    /**
     * @return string
     */
    public function getGridType()
    {
        $helper = Mage::helper('mageworx_ordersgrid');
        return $helper::XML_GRID_TYPE_ORDER;
    }
}
