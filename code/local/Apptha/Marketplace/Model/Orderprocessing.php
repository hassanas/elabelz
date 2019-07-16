<?php
/**
 *   This model will separate order processing logic from controller
 *
 * @Author         Hassan Ali Shahzad
 *
 * @copyright      Progos Tech (c) 2017
 * Date: 08/03/2018
 * Time: 12:10
 *
 */

class Apptha_Marketplace_Model_Orderprocessing extends Mage_Core_Model_Abstract
{

    /**
     * This function will update order item statuses from seller dash-board
     * @param $orderId
     * @param $produtId
     * @param $itemStatus
     * @return bool
     */
    public function confirm($orderId, $produtId, $itemStatus)
    {
        ini_set('memory_limit', '-1');
        $sellerId = Mage::getSingleton('customer/session')->getId();
        $products = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('product_id', $produtId);
        $collectionId = $products->getFirstItem()->getId();
        $orderStatusFlag = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/order_manage');
        if (!empty($collectionId) && $orderStatusFlag == 1) {
            try {
                $order = Mage::getModel('sales/order')->load($orderId);
                // using product model for adding product sku in comment
                $product = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect('sku')
                            ->addAttributeToFilter('entity_id', $produtId)->getFirstItem();

                $current = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $data = array();
                if ($itemStatus == "confirm") {
                    $data = array('is_seller_confirmation' => 'Yes', 'is_seller_confirmation_date' => $current, 'item_order_status' => 'ready');
                    $msg = "Confirmed Successfully.";
                    $comment = "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> by seller";
                } elseif ($itemStatus == "rejected") {
                    $data = array('is_seller_confirmation' => 'Rejected', 'is_seller_confirmation_date' => $current, 'item_order_status' => 'rejected_seller');
                    $msg = "Rejected Successfully.";
                    $comment = "Order Item having SKU '{$product->getSku()}' is <strong>rejected</strong> by seller";
                }

                /*Saving Data in marketplace table*/
                $commissionModel = Mage::getModel('marketplace/commission')->load($collectionId)->addData($data);
                $commissionModel->setId($collectionId)->save();

                /*Saving order comment*/
                $order->addStatusHistoryComment($comment, $order->getStatus())
                      ->setIsVisibleOnFront(0)
                      ->setIsCustomerNotified(0);
                $order->save();

                /*-----------Cancelling items that are rejected-------*/
                //adding failed delivery status checking if all order items are rejected or cancelled
                $check_rejected = "";
                if ($itemStatus == "rejected") {
                    //adding failed delivery status checking if all order items are rejected or cancelled
                    $check_rejected = Mage::helper('marketplace/marketplace')->getProductReject($orderId);
                    if ($check_rejected == "true_seller") {
                        Mage::helper('progos_ordersedit')->getFailedDeliveryStatus($order);
                    }
                    //cancelling the quantity of item removed
                    Mage::getSingleton('marketplace/order')->deleteOrderItem($orderId, $produtId, $check_rejected);

                    //sending email to suppliers@elabelz.com when an item is rejected
                    Mage::helper('marketplace/vieworder')->getSellerRejectedData($product->getSku(), $sellerId, $order->getIncrementId());
                    //send email to kustomer on an order item reject @RT
                    if (Mage::helper('progos_infotrust')->isEnableSellerItemRejectEmailStorefront()) {
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
                            Mage::log('inside confirm after seller item reject', null, 'jsonld.log');
                        }
                        //send email to Kustomer for each item reject
                        $itemRejectJsonld = Mage::helper('progos_infotrust')->getSellerItemRejectEmailJsonld($order, ['product_sku' => $product->getSku(), 'seller_id' => $sellerId]);
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('get Jsonld', null, 'jsonld.log');
                        }
                        //send email to kustomer hook
                        Mage::helper('progos_infotrust')->zendSend($itemRejectJsonld, $order, 'Order Item Reject Notification. Order#'. $order->getIncrementId(), 'Storefront Seller item reject for order #'. $order->getIncrementId());
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('after confirm after seller item reject', null, 'jsonld.log');
                        }
                    }
                    /*--------Sending Emails to seller if all products are rejected--------*/
                    //if all items are approved by seller
                    $rejected = Mage::helper('marketplace/marketplace')->getProductConfirmSellerRejected($orderId, $sellerId);

                    if ($rejected) {
                        //checking if rejected items are rejected by seller only
                        $rejected_items = Mage::helper('marketplace/vieworder')->allProductsSellerRejected($orderId);
                        if (!empty($rejected_items)) {
                            $totalRejectedItems = count($rejected_items);
                            if ($totalRejectedItems >= 1) {
                                //sending email
                                Mage::helper('marketplace/vieworder')->cancelOrderItemEmail($rejected_items);
                            }
                        }
                    }
                }
                // Hassan: change order status (16-10-2017 20:08)
                if (Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromBuyer($orderId) && Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromSeller($orderId) && $check_rejected != "true_seller") {
                    // Set 'Confirmed' order status
                    $order = Mage::getModel('sales/order')->load($orderId);
                    Mage::helper('orderstatuses')->setOrderStatusConfirmed($order);
                    //send email even one item in order is confirmed by seller(s)
                    //prepare data for jsonld @RT
                    if (Mage::helper('progos_infotrust')->isEnableSellerAcceptEmail()) {
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
                            Mage::log('inside confirm after seller accept', null, 'jsonld.log');
                        }
                        //prepare json ld
                        $jsonld = Mage::helper('progos_infotrust')->getSellerAcceptEmailJsonld($order);
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('get Jsonld', null, 'jsonld.log');
                        }
                        //send email to kustomer hook
                        Mage::helper('progos_infotrust')->zendSend($jsonld, $order, 'Order Accept Notification', 'Seller item accept for order #'. $order->getIncrementId());
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('after confirm after seller accept', null, 'jsonld.log');
                        }
                    }
                }

                $result['call_status'] = true;
                $result['message'] = $this->__($msg);

                return $result;

            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'jsonld.log');
            }
        } else {
            return  $result['call_status'] = false;
        }

    }
}