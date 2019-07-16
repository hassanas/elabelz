<?php
/**
 * This Module will create custom order statuses
 *
 * @category       Progos
 * @package        Progos_OrderStatuses
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           16-10-2017 13:17
 */
class Progos_OrderStatuses_Helper_Data extends Mage_Core_Helper_Abstract {

    const STATUS_CANCELED_AUTOMATIC     = 'canceled_automatic';
    const STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
    const STATUS_PENDING_SELLER         = 'pending_seller_confirmation';
    const STATUS_CONFIRMED              = 'confirmed';
    const STATUS_CANCEL                 = 'canceled';
    const STATUS_DELIVERED              = 'successful_delivery';


    /**
     * Set 'canceled_automatic' order status @RT
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatusCanceledAutomatic($order)
    {
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCELED_AUTOMATIC, '', null)->save();
        $this->updateMarketPlaceOrderStatus($order->getId(), self::STATUS_CANCELED_AUTOMATIC);

        $this->syncOrderWithExtendedGrid($order->getId());
    }

    public function setOrderStatusCanceled($order){
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCEL, '', null)->save();
        $this->updateMarketPlaceOrderStatus($order->getId(),self::STATUS_CANCEL);

        $this->syncOrderWithExtendedGrid($order->getId());
    }
    /**
     * Set 'pending_buyer_confirmation' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatusPendingCustomerConfirmation($order)
    {
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, self::STATUS_PENDING_CUSTOMER, '', null)->save();
        $this->updateMarketPlaceOrderStatus($order->getId(),self::STATUS_PENDING_CUSTOMER);
        $this->syncOrderWithExtendedGrid($order->getId());
    }



    /**
     * Set 'pending_supplier_confirmation' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatusPendingSupplierConfirmation($order)
    {
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, self::STATUS_PENDING_SELLER, '', null)->save();
        $this->updateMarketPlaceOrderStatus($order->getId(),self::STATUS_PENDING_SELLER);
        $this->syncOrderWithExtendedGrid($order->getId());
    }

    /**
     * Set 'pending_supplier' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatusConfirmed($order)
    {
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, self::STATUS_CONFIRMED, '', null)->save();
        $this->updateMarketPlaceOrderStatus($order->getId(),self::STATUS_CONFIRMED);
        $this->syncOrderWithExtendedGrid($order->getId());
    }

    /**
     * Set 'successful_delivery' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatusSuccesfullDeliver($order)
    {
        $order->setStatus(self::STATUS_DELIVERED)->save();
        $this->updateMarketPlaceOrderStatus($order->getEntityId(),self::STATUS_DELIVERED);
        $this->syncOrderWithExtendedGrid($order->getEntityId());
    }

    /**
     * This function will update order status in market place against order id
     * @param $orderId
     * @param $status
     *
     */
    public function updateMarketPlaceOrderStatus($orderId, $status){
        $collection = Mage::getModel('marketplace/commission')
            ->getCollection()
            ->addFieldToFilter('order_id',$orderId);
        foreach($collection as $row)
        {
            $row->setOrderStatus($status);
            $row->save();
        }
    }

    /**
     * This function will update the order statuses in Extended order grid
     * @param $orderId
     */
    public function syncOrderWithExtendedGrid($orderId){
        Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrdersStatus($orderId);
    }

}