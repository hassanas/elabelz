<?php
/**
 * @package: Progos_Reorder
 * @Rewrite the Core Model into Progos_Reorder_Model_Adminhtml_Sales_Order_Create
 * @description: When Product is going out of stock then It through a exception and break the page So try catch added for handle the exception.
 */
class Progos_Reorder_Model_Adminhtml_Sales_Order_Create extends Mage_Adminhtml_Model_Sales_Order_Create
{
   
    /**
     * Initialize creation data from existing order
     *
     * @param Mage_Sales_Model_Order $order
     * @return unknown
     */
    public function initFromOrder(Mage_Sales_Model_Order $order)
    {
        $session = $this->getSession();
        if (!$order->getReordered()) {
            $session->setOrderId($order->getId());
        } else {
            $session->setReordered($order->getId());
        }

        /**
         * Check if we edit quest order
         */
        $session->setCurrencyId($order->getOrderCurrencyCode());
        if ($order->getCustomerId()) {
            $session->setCustomerId($order->getCustomerId());
        } else {
            $session->setCustomerId(false);
        }

        $session->setStoreId($order->getStoreId());

        //Notify other modules about the session quote
        Mage::dispatchEvent('init_from_order_session_quote_initialized',
                array('session_quote' => $session));

        /**
         * Initialize catalog rule data with new session values
         */
        $this->initRuleData();
        foreach ($order->getItemsCollection(
            array_keys(Mage::getConfig()->getNode('adminhtml/sales/order/create/available_product_types')->asArray()),
            true
            ) as $orderItem) {
            /* @var $orderItem Mage_Sales_Model_Order_Item */
            if (!$orderItem->getParentItem()) {
                if ($order->getReordered()) {
                    $qty = $orderItem->getQtyOrdered();
                } else {
                    $qty = $orderItem->getQtyOrdered() - $orderItem->getQtyShipped() - $orderItem->getQtyInvoiced();
                }
                try{
                    if ($qty > 0) {
                        $item = $this->initFromOrderItem($orderItem, $qty);
                        if (is_string($item)) {
                            Mage::throwException($item);
                        }
                    }
                }catch(Mage_Core_Exception $e){
                   Mage::getSingleton('adminhtml/session')->addError($orderItem->getSku().' '.$e->getMessage());
                }
            }
        }

        $orderShippingAddress = $order->getShippingAddress();
        if ($orderShippingAddress) {
            $addressDiff = array_diff_assoc($orderShippingAddress->getData(), $order->getBillingAddress()->getData());
            unset($addressDiff['address_type'], $addressDiff['entity_id']);
            $orderShippingAddress->setSameAsBilling(empty($addressDiff));
        }

        $this->_initBillingAddressFromOrder($order);
        $this->_initShippingAddressFromOrder($order);

        $quote = $this->getQuote();
        if (!$quote->isVirtual() && $this->getShippingAddress()->getSameAsBilling()) {
            $this->setShippingAsBilling(1);
        }

        $this->setShippingMethod($order->getShippingMethod());
        $quote->getShippingAddress()->setShippingDescription($order->getShippingDescription());

        $quote->getPayment()->addData($order->getPayment()->getData());


        $orderCouponCode = $order->getCouponCode();
        if ($orderCouponCode) {
            $quote->setCouponCode($orderCouponCode);
        }

        if ($quote->getCouponCode()) {
            $quote->collectTotals();
        }

        Mage::helper('core')->copyFieldset(
            'sales_copy_order',
            'to_edit',
            $order,
            $quote
        );

        Mage::dispatchEvent('sales_convert_order_to_quote', array(
            'order' => $order,
            'quote' => $quote
        ));

        if (!$order->getCustomerId()) {
            $quote->setCustomerIsGuest(true);
        }

        if ($session->getUseOldShippingMethod(true)) {
            /*
             * if we are making reorder or editing old order
             * we need to show old shipping as preselected
             * so for this we need to collect shipping rates
             */
            $this->collectShippingRates();
        } else {
            /*
             * if we are creating new order then we don't need to collect
             * shipping rates before customer hit appropriate button
             */
            $this->collectRates();
        }
        $quote->save();
        return $this;
    }
}
