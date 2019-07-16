<?php

/**
 * @category   Progos
 * @author     Saroop
 * @package    Progos_OrdersGrid
 * @copyright  Copyright (c) 2017 Progos (http://progostech.com/)
 */
class Progos_OrdersGrid_Model_Order_Grid extends MageWorx_OrdersGrid_Model_Order_Grid
{

    public function syncOrdersStatus($orderIds)
    {
        $collection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('entity_id', $orderIds);
        $order = $collection->getFirstItem();
        if ($order->getId()) { 
            $data=array(
                'status'=>$order->getStatus(),
            );
            // updating the mageworx order grid table
            $model = Mage::getModel('mageworx_ordersgrid/order_grid')->load($orderIds);
            $model->addData($data)->save();
        }
        return;
    }

}