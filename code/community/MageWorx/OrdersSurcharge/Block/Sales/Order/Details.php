<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Sales_Order_Details extends Mage_Core_Block_Template
{

    public function _construct()
    {
        parent::_construct();
    }

    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Get all surcharges for current order
     *
     * @return bool|MageWorx_OrdersSurcharge_Model_Resource_Surcharge_Collection
     */
    public function getSurcharges()
    {
        $order = $this->getOrder();
        if (!$order->getId()) {
            return false;
        }
        $surcharges = Mage::getModel('mageworx_orderssurcharge/surcharge')->loadByParentOrder($order);
        if (!$surcharges->getFirstItem()) {
            return false;
        }

        return $surcharges;
    }

    /**
     * @param $orderId
     * @return string
     */
    public function getOrderIncrementId($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $incrementId = $order->getIncrementId();

        return $incrementId;
    }
}