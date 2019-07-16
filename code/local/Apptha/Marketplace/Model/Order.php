<?php
/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 *
 */

/**
 * Function written in this file are used for seller order management
 */
class Apptha_Marketplace_Model_Order extends Mage_Core_Model_Abstract
{
    /**
     * Set order item cancel/refund request status for customer
     *
     * @param number $itemProductId
     * @param number $orderId
     * @param number $loggedInCustomerId
     * @param number $sellerId
     * @param number status
     * @return void
     */
    public function updateSellerRequest($itemProductId, $orderId, $loggedInCustomerId, $sellerId, $value)
    {
        /**
         * Checking for product id , order id and customer d
         */
        if (!empty($itemProductId) && !empty($orderId) && !empty($loggedInCustomerId)) {
            /**
             * Get product from commission model
             */
            $products = Mage::getModel('marketplace/commission')->getCollection();
            $products->addFieldToSelect('*');

            /**
             * Filter by value
             */
            if ($value == 2) {
                $products->addFieldToFilter('seller_id', $loggedInCustomerId);
            } else {
                $products->addFieldToFilter('seller_id', $sellerId);
                $products->addFieldToFilter('customer_id', $loggedInCustomerId);
            }
            $products->addFieldToFilter('order_id', $orderId);
            $products->addFieldToFilter('product_id', $itemProductId);

            /**
             * Getting first data id
             */
            $collectionId = $products->getFirstItem()->getId();

            /**
             * Checking for first data exist or not
             */
            if (!empty($collectionId)) {
                if ($value == 2) {
                    $data = array('refund_request_seller' => 1);
                } elseif ($value == 1) {
                    $data = array('refund_request_customer' => 1);
                } else {
                    $data = array('cancel_request_customer' => 1);
                }

                /**
                 * Update date for commission model
                 */
                $model = Mage::getModel('marketplace/commission')->load($collectionId)->addData($data);

                /**
                 * Save model
                 */
                $model->setId($collectionId)->save();
            }
        }
    }

    /**
     * Getting request status for customer order item
     *
     * @param number $itemProductId
     * @param number $orderId
     * @param number $loggedInCustomerId
     * @param number $value
     * @return boolean $status
     */
    public function getItemRequestStatus($itemProductId, $orderId, $loggedInCustomerId, $value)
    {
        /**
         * Load commission model
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');

        /**
         * Filter by order id and product id
         */
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $itemProductId);

        /**
         * Checking for value
         */
        if ($value == 2 || $value == 3 || $value == 4) {
            $products->addFieldToFilter('seller_id', $loggedInCustomerId);
        } else {
            $products->addFieldToFilter('customer_id', $loggedInCustomerId);
        }

        /**
         * Checking for value
         */
        if ($value == 4) {
            $status = $products->getFirstItem()->getRefundRequestSeller();
        } elseif ($value == 3) {
            $status = $products->getFirstItem()->getCancelRequestCustomer();
        } elseif ($value == 1 || $value == 2) {
            $status = $products->getFirstItem()->getRefundRequestCustomer();
        } else {
            $status = $products->getFirstItem()->getCancelRequestCustomer();
        }

        /**
         * Return status
         */
        return $status;
    }

    /**
     * Check
     *
     * @param number $itemProductId
     * @param number $orderId
     */
    public function getIsCancelledProduct($itemProductId, $orderId)
    {
        /**
         * Load commission model
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');

        /**
         * Filter by order id and product id
         */
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $itemProductId);
        $status = $products->getFirstItem()->getItemOrderStatus();

        /**
         * Return status
         */
        return $status;
    }

    /**
     * Update order status for seller items
     *
     * @param array $itemsArr
     * @param number $orderId
     * @return void
     */
    public function updateOrderStatusForSellerItems($itemsArr, $orderId)
    {
        foreach ($itemsArr as $item) {
            /**
             * Get status based on order item
             */
            $status = Mage::helper('marketplace/vieworder')->getOrderStatusForSellerItemsBased($item);

            /**
             * Inilize product id
             */
            $itemProductId = $item->getProductId();

            /**
             * Load commission collection
             */
            $products = Mage::getModel('marketplace/commission')->getCollection();
            $products->addFieldToSelect('*');

            /**
             * Filter by order id and product id
             */
            $products->addFieldToFilter('order_id', $orderId);
            $products->addFieldToFilter('product_id', $itemProductId);
            $collectionId = $products->getFirstItem()->getId();

            /**
             * Checking for collection id exist or not
             */
            if (!empty($collectionId)) {
                /**
                 * Initilize order item status
                 */
                $data = array('item_order_status' => $status);
                $model = Mage::getModel('marketplace/commission')->load($collectionId)->addData($data);

                /**
                 * Save model
                 */
                $model->setId($collectionId)->save();
            }
        }
    }

    /**
     * Update cancel order status for seller items
     *
     * @param number $productId
     * @param number $orderId
     * @return void
     */
    public function updateCancelOrderStatusForSellerItems($productId, $orderId)
    {
        /**
         * Update canceled seller order items
         */
        $status = 'canceled';

        /**
         * Load commission model for seller item statu update
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');

        /**
         * Filter by order id and product id
         */
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $productId);

        /**
         * Get first item from model
         */
        $collectionId = $products->getFirstItem()->getId();
        if (!empty($collectionId)) {
            /**
             * Update seller order item status canceled
             */
            $data = array('item_order_status' => $status);
            $model = Mage::getModel('marketplace/commission')->load($collectionId)->addData($data);

            /**
             * Save commisstion model
             */
            $model->setId($collectionId)->save();
        }
    }

    /**
     * Update seller order items based on shipping
     *
     * @param $savedQtys
     * @param $orderId
     * @param $value
     */
    public function updateSellerOrderItemsBasedOnSellerItems($savedQtys, $orderId, $value)
    {
        /**
         * Load order by order id
         */
        $order = Mage::getModel('sales/order')->load($orderId);
        $itemsArr = array();

        /**
         * Getting order items
         */
        foreach ($order->getAllItems() as $item) {
            $itemId = $item->getItemId();
            /**
             * Checking for seller order items
             */
            if (array_key_exists($itemId, $savedQtys) && $value != 1) {
                $itemsArr[] = $item;
            }
            /**
             * Prepare items for seller order status
             */
            if (in_array($itemId, $savedQtys) && $value == 1) {
                $itemsArr[] = $item;
            }
        }

        /**
         * Update seller order item status
         */
        Mage::getModel('marketplace/order')->updateOrderStatusForSellerItems($itemsArr, $orderId);
    }

    public function deleteOrderItem($order_id, $product_id, $check_rejected)
    {
        $_order = Mage::getModel('sales/order')->load($order_id);
        $base_grand_total = $_order->getBaseGrandTotal();
        $base_subtotal = $_order->getBaseSubtotal();
        $base_tva = $_order->getBaseTaxAmount();
        $grand_total = $_order->getGrandTotal();
        $subtotal = $_order->getSubtotal();
        $tva = $_order->getTaxAmount();
        $base_subtotal_incl_tax = $_order->getBaseSubtotalInclTax();
        $subtotal_incl_tax = $_order->getSubtotalInclTax();
        $quote = Mage::getModel('sales/quote')->getCollection()
            ->addFieldToFilter("entity_id", $_order->getQuoteId())->getFirstItem();

        $product = Mage::getModel('catalog/product')->load($product_id);
        $sku = $product->getSku();
        $items = $_order->getAllItems();
        foreach ($items as $item) {
            if ($item->getSku() == $sku){
                if ($item->getProductType() == "configurable") {
                    $qtyToCancel = $item->getData('qty_ordered') - $item->getQtyCanceled();
                    $origQtyCancelled = $item->getQtyCanceled();
                    $item->setQtyCanceled($origQtyCancelled + $qtyToCancel);

                    Mage::dispatchEvent('sales_order_item_cancel', array('item' => $item));

                    $item_price = $item->getPrice() * $qtyToCancel;
                    $item_base_price = $item->getBasePrice() * $qtyToCancel;
                    $item_tva = $item->getTaxAmount();
                    $item_base_tva = $item->getBaseTaxAmount();
                    $discount_amountBase = $item->getBaseDiscountAmount();
                    $discount_amount = $item->getDiscountAmount();
                    $item_price_total = $item_price - $discount_amount ;
                    $item_base_price_total = $item_base_price - $discount_amountBase;

                    $item->setSubtotal(0)
                        ->setBaseSubtotal(0)
                        ->setTaxAmount(0)
                        ->setBaseTaxAmount(0)
                        ->setTaxPercent(0)
                        ->setDiscountAmount(0)
                        ->setBaseDiscountAmount(0)
                        ->setRowTotal(0)
                        ->setBaseRowTotal(0);

                    $item->setTaxCanceled(
                        $item->getTaxCanceled() +
                        $item->getBaseTaxAmount() * $item->getQtyCanceled() / $item->getQtyOrdered()
                    );

                    $item->setHiddenTaxCanceled(
                        $item->getHiddenTaxCanceled() +
                        $item->getHiddenTaxAmount() * $item->getQtyCanceled() / $item->getQtyOrdered()
                    );

                    $quote->removeItem($item->getQuoteItemId())->save();
                    if ($check_rejected === "false_seller"){
                        if ($discount_amount){
                            $baseDiscountAmount = $_order->getBaseDiscountAmount() + $discount_amountBase;
                            $discountAmount = $_order->getDiscountAmount() + $discount_amount;
                            $_order->setBaseDiscountAmount($baseDiscountAmount);
                            $_order->setDiscountAmount($discountAmount);
                        }
                        if($base_grand_total > 0) {
                            $amount = $base_grand_total - $item_base_price_total - $item_base_tva;
                            if ($amount > 0) {
                                $_order->setBaseGrandTotal($base_grand_total - $item_base_price_total - $item_base_tva);
                            }
                            else{
                                $_order->setBaseGrandTotal(0);
                            }
                        }

                        $_order->setBaseSubtotal($base_subtotal - $item_base_price);
                        $_order->setBaseTaxAmount($base_tva - $item_base_tva);
                        if($grand_total > 0) {
                            $amount = $grand_total - $item_price_total - $item_tva;
                            if($amount > 0) {
                                $_order->setGrandTotal($grand_total - $item_price_total - $item_tva);
                            }
                            else{
                                $_order->setGrandTotal(0);
                            }
                        }
                        $_order->setSubtotal($subtotal - $item_price);
                        $_order->setTaxAmount($tva - $item_tva);
                        $_order->setBaseSubtotalInclTax($base_subtotal_incl_tax - $item_base_price);
                        $_order->setSubtotalInclTax($subtotal_incl_tax - $item_price);
                        $_order->setTotalItemCount(count($items) - 1);


                        $_order->save();
                    }
                    elseif ($check_rejected === "true_seller"){
                        //if last item got canceled revert storecredit
                        if($_order->getAwStorecredit() && $_order->getCustomerId()) {
                            $storeCreditModel = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($_order->getCustomerId());
                            $quote_storecredit = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($_order->getQuoteId());
                            foreach($quote_storecredit as $storecredit){
                                $baseStoreCreditReturnToCustomer = $storecredit->getBaseStorecreditAmount();
                                $storecredit->setBaseStorecreditAmount(0);
                                $storecredit->setStorecreditAmount(0);
                                $storecredit->save();
                            }
                            foreach ($_order->getAwStorecredit() as $storecredit) {
                                $storecredit['base_storecredit_amount'] = 0;
                                $storecredit['storecredit_amount'] = 0;
                            }
                            $storeCreditModel
                                ->setOrder($_order)
                                ->setOrderCanceled(true)
                                ->setCustomerId($_order->getCustomerId())
                                ->setBalance($storeCreditModel->getBalance() + $baseStoreCreditReturnToCustomer);
                            try {
                                $storeCreditModel->save();
                            } catch (Exception $e) {
                                Mage::logException($e);
                            }

                        }
                        //configurable
                        if (Mage::helper('marketplace/general')->isEnableCanceledAutomatic()) {
                            /**
                             * setting order status to canceled-automatic in cancel state
                             * When an order item is canceled by seller from seller dashboard,
                             * if the item is the only item in the order and has qty 1,
                             * execute below code to set the order status to Canceled-Automatic
                             * rest of the functionality will remain same. @RT
                             **/
                            //get visible item count
                            $itemCount = count($_order->getAllVisibleItems());
                            //check item qty that can be canceled, must be 1 to execute below code
                            if (Mage::getDesign()->getArea() != 'adminhtml' && $itemCount == 1 && $qtyToCancel == 1) {
                                //set order status to Canceled Automatic
                                Mage::helper('orderstatuses')->setOrderStatusCanceledAutomatic($_order);
                            } else {
                                // SET ORDER STATUS TO CANCEL
                                Mage::helper('orderstatuses')->setOrderStatusCanceled($_order);
                            }
                        } else {
                            // SET ORDER STATUS TO CANCEL
                            Mage::helper('orderstatuses')->setOrderStatusCanceled($_order);
                        }
                    }
                }

                if ($item->getProductId() == $product_id) {
                    $qtyToCancel = $item->getData('qty_ordered') - $item->getQtyCanceled();
                    $origQtyCancelled = $item->getQtyCanceled();
                    $item->setQtyCanceled($origQtyCancelled + $qtyToCancel);
                    Mage::dispatchEvent('sales_order_item_cancel', array('item' => $item));

                    $item->setSubtotal(0)
                        ->setBaseSubtotal(0)
                        ->setTaxAmount(0)
                        ->setBaseTaxAmount(0)
                        ->setTaxPercent(0)
                        ->setDiscountAmount(0)
                        ->setBaseDiscountAmount(0)
                        ->setRowTotal(0)
                        ->setBaseRowTotal(0);

                    $item->setTaxCanceled(
                        $item->getTaxCanceled() +
                        $item->getBaseTaxAmount() * $item->getQtyCanceled() / $item->getQtyOrdered()
                    );
                    $item->setHiddenTaxCanceled(
                        $item->getHiddenTaxCanceled() +
                        $item->getHiddenTaxAmount() * $item->getQtyCanceled() / $item->getQtyOrdered()
                    );
                    $quote->removeItem($item->getQuoteItemId)->save();


                    $this->_addQtyToStock($item->getProductId(), $qtyToCancel, $item->getSku());
                    $data = array('product_qty' => 0, 'seller_amount' => 0, 'commission_fee' => 0, 'item_order_status' => 'canceled');

                    $products = Mage::getModel('marketplace/commission')->getCollection();
                    $products->addFieldToSelect('*');
                    $products->addFieldToFilter('order_id', $order_id);
                    $products->addFieldToFilter('product_id', $product_id);

                    if ($products) {
                        foreach ($products as $product):
                            $id = $product->getId();
                        endforeach;

                        $model = mage::getmodel('marketplace/commission')->load($id);
                        $model->addData($data);

                        try {
                            $model->setId($id)->save();

                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }
                }


            }
        }

        $quote->setIsActive(0)->save();

        //reverting store credit on item rejection
        if($_order->getAwStorecredit() && $_order->getCustomerId()) {
            $storeCreditModel = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($_order->getCustomerId());
            $order_total = $_order->getBaseSubtotal() + $_order->getBaseDiscountAmount() + $_order->getBaseShippingAmount()+ $_order->getBaseTaxAmount();
            foreach ($_order->getAwStorecredit() as $storecredit) {
                $orderStoreCredit = $storecredit['base_storecredit_amount'];
                if ($orderStoreCredit == NULL) {
                    $orderStoreCredit = 0;
                }
            }
            if($orderStoreCredit >= $order_total ){
                $baseStoreCreditReturnToCustomer = $orderStoreCredit - $order_total;//10 aed e.g amount reverted back in customer balance
                $storeCreditUsedInOrderQuoteBase = $orderStoreCredit - $baseStoreCreditReturnToCustomer;//base store credit amount added in quote
                $storeCreditUsedInOrderQuote = $quote->getStore()->roundPrice($quote->getStore()->convertPrice($storeCreditUsedInOrderQuoteBase));
                //store storecredit amount added in quote

                $storeCreditModel
                    ->setOrder($_order)
                    ->setOrderCanceled(true)
                    ->setCustomerId($_order->getCustomerId())
                    ->setBalance($storeCreditModel->getBalance() + $baseStoreCreditReturnToCustomer);
                try {
                    $storeCreditModel->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }

                $quote_id = $_order->getQuoteId();
                $quote_storecredit = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quote_id);
                foreach($quote_storecredit as $storecredit){
                    $storecredit->setBaseStorecreditAmount($storeCreditUsedInOrderQuoteBase);
                    $storecredit->setStorecreditAmount($storeCreditUsedInOrderQuote);
                    $storecredit->save();
                }

                $payment = $_order->getPayment();
                $_order->setMspBaseCashondelivery(0);
                $_order->setMspCashondelivery(0);
                $payment->setMethod('free');
                $_order->setGrandTotal(0);
                $_order->setBaseGrandTotal(0);
                $payment->save();
                $_order->save();
            }
        }
    }

    public function _addQtyToStock($productId, $qty, $sku)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $_catalog = Mage::getModel('catalog/product');
        $productId = $_catalog->getIdBySku($sku);
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        $qtyAfter = $stockItem->getQty() + $qty;

        if ($qtyAfter <= 0) {
            $stockItem->setIsInStock(1);
            $stockItem->setQty(0);
        } else {
            $stockItem->setIsInStock(1);
            $stockItem->setQty($qtyAfter);
        }

        $stockItem->save();
    }
}