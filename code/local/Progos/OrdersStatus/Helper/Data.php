<?php
/**
 * Progos_OrdersStatus
 *
 * @category    Progos
 * @package     Progos_OrdersStatus
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progostech.com)
 */

/**
 * Class Progos_OrdersStatus_Helper_Data
 */
class Progos_OrdersStatus_Helper_Data extends Mage_Core_Helper_Abstract
{
    const STATUS_CANCELED_CUSTOMER        = 'canceled_by_customer';
    const STATUS_CANCELED_OOS             = 'canceled_oos';
    const STATUS_CANCELED_NO_RESPONSE     = 'canceled_no_response';
    const STATUS_CANCELED_REORDER         = 'canceled_reorder';
    const STATUS_CANCELED_TEST            = 'canceled_test';

    const STATUS_PAYMENT_DECLINED         = 'payment_declined';

    const STATUS_PENDING_SUPPLIER         = 'pending_supplier';

    const STATUS_DELIVERY_SUCCESS         = 'successful_delivery';
    const STATUS_DELIVERY_FAILED          = 'failed_delivery';

    const STATUS_RETURN_FULL              = 'return_full';
    const STATUS_RETURN_PARTIAL           = 'return_partial';

    const STATUS_WH_FAILED_DELIVERY       = 'wh_failed_delivery';
    const STATUS_WH_RETURN_PARTIAL        = 'wh_return_partial';
    const STATUS_WH_RETURN_FULL           = 'wh_return_full';

    const STATUS_CLOSED_NON_REFUND        = 'closed_non_refund';

    /**
     * Set 'canceled_by_customer' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateCanceledCustomer($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCELED_CUSTOMER, '', null)
            ->save();
    }

    /**
     * Set 'canceled_oos' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateCanceledOos($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCELED_OOS, '', null)
            ->save();
    }

    /**
     * Set 'canceled_no_response' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateCanceledNoResponse($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCELED_NO_RESPONSE, '', null)
            ->save();
    }

    /**
     * Set 'canceled_reorder' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateCanceledReorder($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCELED_REORDER, '', null)
            ->save();
    }

    /**
     * Set 'canceled_test' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateCanceledTest($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_CANCELED_TEST, '', null)
            ->save();
    }

    /**
     * Set 'payment_declined' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatePaymentDeclined($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, self::STATUS_PAYMENT_DECLINED, '', null)
            ->save();
    }

    /**
     * Set 'pending_supplier' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStatePendingSupplierConfirmation($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, self::STATUS_PENDING_SUPPLIER, '', null)
            ->save();
    }

    /**
     * Set 'successful_delivery' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateDeliverySuccess($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_DELIVERY_SUCCESS, '', null)
            ->save();
    }

    /**
     * Set 'failed_delivery' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateDeliveryFailed($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_DELIVERY_FAILED, '', null)
            ->save();
    }

    /**
     * Set 'return_full' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateReturnFull($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_RETURN_FULL, '', null)
            ->save();
    }

    /**
     * Set 'return_partial' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateReturnPartial($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_RETURN_PARTIAL, '', null)
            ->save();
    }

    /**
     * Set 'wh_failed_delivery' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateWhFailed($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_WH_FAILED_DELIVERY, '', null)
            ->save();
    }

    /**
     * Set 'wh_return_partial' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateWhReturnPartial($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_WH_RETURN_PARTIAL, '', null)
            ->save();
    }

    /**
     * Set 'wh_return_full' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateWhReturnFull($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, self::STATUS_WH_RETURN_FULL, '', null)
            ->save();
    }

    /**
     * Set 'closed_non_refund' order status
     *
     * @param $order
     * @return Mage_Core_Model_Abstract
     */
    public function setOrderStateClosedNonRefundable($order)
    {
        return $order->setState(Mage_Sales_Model_Order::STATE_CLOSED, self::STATUS_CLOSED_NON_REFUND, '', null)
            ->save();
    }
}