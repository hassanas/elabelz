<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_Observer_Order extends MageWorx_OrdersSurcharge_Model_Observer_Abstract
{
    /**
     * Check is order editable (adminhtml_sales_order_view)
     *
     * @param $observer
     * @return $this
     */
    public function checkIsOrderEditable($observer)
    {
        Mage::getSingleton('adminhtml/session')->setBlockEditOrder(null);

        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        $orderId = $observer->getControllerAction()->getRequest()->getParam('order_id');
        if (!$orderId) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            return $this;
        }

        if ($order->getSurchargeId()) {
            Mage::getSingleton('adminhtml/session')->addNotice($helper->__('You can not edit a surcharge order'));
            Mage::getSingleton('adminhtml/session')->setBlockEditOrder(true);
            return $this;
        }

        if (abs($order->getBaseLinkedAmountInvoiced()) < abs($order->getBaseLinkedAmount())) {
            $surcharges = Mage::getModel('mageworx_orderssurcharge/surcharge')->loadByParentOrder($order);
            $surcharge = $surcharges->getFirstItem();
            if ($surcharge) {
                $surchargeId = $surcharge->getId();
                Mage::getSingleton('adminhtml/session')->addNotice(
                    $helper->__(
                        'You can not edit the order unless the payment link with ID #%s (sent to the customer) is paid or canceled',
                        $surchargeId
                    )
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addNotice(
                    $helper->__('You can not edit the order unless the payment link is paid')
                );
            }
            Mage::getSingleton('adminhtml/session')->setBlockEditOrder(true);
            return $this;
        }

        return $this;
    }

    /**
     * Remove linked amount from parent order when surcharge order was refunded
     * and change surcharge status
     *
     * @param $observer
     * @return $this
     */
    public function checkRefund($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        $surchargeId = $order->getSurchargeId();
        if (!$surchargeId) {
            return $this;
        }

        if ($order->getBaseTotalPaid() &&
            $order->getBaseTotalRefunded() &&
            $order->getBaseSurchargeAmount() &&
            $order->getBaseTotalPaid() == $order->getBaseTotalRefunded() &&
            $order->getBaseSurchargeAmount() == $order->getBaseTotalRefunded()
        ) {
            $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->load($surchargeId);
            $surcharge->refund($order);
        }

        return $this;
    }

    /**
     * Delete the surcharge if the surcharge order was canceled
     *
     * @param $observer
     * @return $this
     * @throws Exception
     */
    public function checkCancel($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        $surchargeId = $order->getSurchargeId();
        if (!$surchargeId) {
            return $this;
        }

        $orderStateCanceled = $order->getState() === Mage_Sales_Model_Order::STATE_CANCELED;
        if (!$orderStateCanceled) {
            return $this;
        }

        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->load($surchargeId);
        $surcharge->delete();

        return $this;
    }

    /**
     * Update parent order if surcharge was paid
     *
     * @param $observer
     * @return $this
     */
    public function applySurchargeToParentOrder($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        if (!$order || !$order instanceof Mage_Sales_Model_Order) {
            return $this;
        }

        $surchargeId = $order->getSurchargeId();
        if (!$surchargeId) {
            return $this;
        }

        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->load($surchargeId);
        $surcharge->updateOrderIdAndIncrementId($order);
        $surcharge->applySurcharge($order);

        return $this;
    }

    /**
     * Change surcharge status
     *
     * @param $observer
     * @return $this
     * @throws Exception
     */
    public function changeStatusAfterSurchargeWasPaid($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        $surchargeId = $order->getSurchargeId();
        if (!$surchargeId) {
            return $this;
        }

        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->load($surchargeId);
        if ($surcharge->getStatus() == MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_PENDING) {
            $surcharge->setStatus(MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_PROCESSING);
            $surcharge->save();
        }

        return $this;
    }
}
