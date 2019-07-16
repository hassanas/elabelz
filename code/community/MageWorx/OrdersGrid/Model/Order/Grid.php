<?php

/**
 * MageWorx
 * Admin Order Grid extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_Order_Grid extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('mageworx_ordersgrid/order_grid');
    }

    public function syncOrderById($orderIds)
    {
        Mage::getResourceModel('mageworx_ordersgrid/order_grid')->syncOrders($orderIds);
    }

    public function syncAllOrders()
    {
        Mage::getResourceModel('mageworx_ordersgrid/order_grid')->syncOrders();
    }
}