<?php
/**
 * Progos
 *
 * Order Items
 *
 *
 */

require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';

/**
 * Class Apptha_Marketplace_Adminhtml_OrderitemsController
 */
class Apptha_Marketplace_Adminhtml_OrderitemsController extends Mage_Adminhtml_Sales_OrderController
{
    /**
     * Init actions
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('marketplace/items')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Items Manager'),
                Mage::helper('adminhtml')->__('Item Manager')
            );

        return $this;
    }

    /**
     * Index action.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_initAction();

        $this->renderLayout();
    }

    /**
     * Confirming item from seller side on extended order grod or marketplace grids
     * Step# 1: Fetching all the parameters
     * Step# 2: Getting model of marketplace and order item details
     * Step# 3: Saving seller confirmation and confirmation date
     * Step# 4: Adding comment in order
     * Step# 5: Redirecting
     */
    public function confirm_sellerAction()
    {
        ini_set('memory_limit', '-1');
        //Step# 1
        $id = $this->getRequest()->getParam('id');
        $ref = $this->getRequest()->getParam("ref");
        $path = $this->getRequest()->getParam('path');
        $order_id = $this->getRequest()->getParam('order_id');
        $product_id = $this->getRequest()->getParam('product_id');
        $productsNeedConfirmation = [];
        $rejectedProducts = [];
        $model = null;
        //end
        if ($id >= 0) {
            try {
        
        //Step# 2
                //getting collection for marketplace
                $collection = Mage::getResourceModel('marketplace/commission_collection');
                /*checking if marketplace id yet not visible on extended order itrm grid like
                  when order is placed through admin
                */
                if ($id == 0) {
                    $collection->addFieldToFilter('product_id', $product_id)
                    ->addFieldToFilter('order_id', $order_id);
                    $model = $collection->getFirstItem();
                } else {
                    $model = Mage::getModel('marketplace/commission')->load($id);
                }
        //end
            //Step# 3
                // check before buyer confirmation seller need to be confirmed
                if ($model->getIsBuyerConfirmation() == 'No') {
                    $errorMsg = Mage::helper('marketplace')
                        ->__('Please take action on buyer request first in order to confirm seller');
                    Mage::getSingleton('adminhtml/session')->addError($errorMsg);
                } else {
                    $currentDateTime = date('Y-m-d H:i:s');
                    $model->setIsSellerConfirmationDate($currentDateTime)
                        ->setIsSellerConfirmation('Yes')
                        ->setItemOrderStatus('ready')
                        ->save();
            //end
            //Step# 4
                    // Add seller product confirmation comment to order
                    $order_id = $model->getOrderId();
                    //using order model to save comment in order
                    $order = Mage::getModel('sales/order')->load($order_id);
                    //using product model as we are getting product sku through it in comment 
                    $product = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect('sku')
                            ->addAttributeToFilter('entity_id',$model->getProductId())->getFirstItem();
                    if ($ref == "cs") {
                        $comment =
                            "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> on behalf of <strong>Marchant/Seller</strong> via Call Center";
                    } else {
                        $comment =
                            "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> by seller";
                    }
                    $order->addStatusHistoryComment($comment, $order->getStatus())
                        ->setIsVisibleOnFront(0)
                        ->setIsCustomerNotified(0);
                    $order->save();
            //end
                    $successMsg = Mage::helper('marketplace')->__('Order Confirmed from Seller');
                    Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }


            if(Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromBuyer($order_id) && Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromSeller($order_id)){
                $order = Mage::getModel('sales/order')->load($order_id);
                // Set 'Confirmed' order status
                Mage::helper('orderstatuses')->setOrderStatusConfirmed($order);

                //send email even if one item in order accepted by seller(s)
                //prepare data for jsonld @RT
                if (Mage::helper('progos_infotrust')->isEnableSellerCancelEmail()) {
                    //if enabled logs block
                    if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                        Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
                        Mage::log('inside confirm_sellerAction confirm seller accept', null, 'jsonld.log');
                    }
                    //prepare json ld
                    $jsonld = Mage::helper('progos_infotrust')->getSellerAcceptEmailJsonld($order);
                    //if enabled logs block
                    if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                        Mage::log('get Jsonld', null, 'jsonld.log');
                    }
                    //send email to kustomer hook
                    Mage::helper('progos_infotrust')->zendSend($jsonld, $order, 'Order Accept Notification #'. $order->getIncrementId(), 'Seller item accept for order #'. $order->getIncrementId());
                    //if enabled logs block
                    if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                        Mage::log('after confirm_sellerAction confirm seller accept', null, 'jsonld.log');
                    }
                }
            }
            //Step# 5
            if ($path == "sale_order") {
                if ($ref == "cs") {
                    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id, 'ref' => $ref));
                } else {
                    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                }
            } elseif ($path == "unconfirmed_from_buyer") {
                $this->_redirect('*/adminhtml_unconfirmedfrombuyer');
            } elseif ($path == "unconfirmed_from_seller") {
                $this->_redirect('*/adminhtml_unconfirmedfromseller');
            } elseif ($path == "all_orders") {
                $this->_redirect('*/adminhtml_orderitemsall');
            } else {
                $this->_redirect('*/*/');
            }
        }
        //end
    }

    /**
     * Rejecting item from seller side on extended order grod or marketplace grids
     * Step# 1: Fetching all the parameters
     * Step# 2: Getting model of marketplace and order item details
     * Step# 3: Saving seller confirmation and confirmation date
     * Step# 4: Adding comment in order
     * Step# 5: Checking if all item are cancelled/rejected and adding the failed delivery status and then cancel the qty
     * Step# 6: If all the items are rejected by seller sending a cancelation email to seller(s)
     * Step# 7: Redirecting
     */
    public function reject_sellerAction()
    {
        ini_set('memory_limit', '-1');
        //Step# 1

        $id = $this->getRequest()->getParam('id');
        $path = $this->getRequest()->getParam('path');
        $ref = $this->getRequest()->getParam("ref");
        $order_id = $this->getRequest()->getParam('order_id');
        $product_id = $this->getRequest()->getParam('product_id');
        $productsNeedConfirmation = [];
        $rejectedProducts = [];
        $model = null;
        
        //end

        if ($id >= 0) {
            try {

        //Step# 2
                //getting collection for marketplace
                $collection = Mage::getResourceModel('marketplace/commission_collection');

                /*checking if marketplace id yet not visible on extended order itrm grid like
                  when order is placed through admin
                */
                if ($id == 0) {
                    $collection->addFieldToFilter('product_id', $product_id)
                    ->addFieldToFilter('order_id', $order_id);
                    $model = $collection->getFirstItem();
                } else {
                    $model = Mage::getModel('marketplace/commission')->load($id);
                }
        //end

            //Step# 3

                if ($model->getIsBuyerConfirmation() == 'No') {
                    $errorMsg = Mage::helper('marketplace')->__('Please take action on buyer request first in order to reject seller');
                    Mage::getSingleton('adminhtml/session')->addError($errorMsg);
                } else {
                    $currentDateTime = date('Y-m-d H:i:s');
                    $model->setIsSellerConfirmation('Rejected')
                    ->setItemOrderStatus('rejected_seller')
                    ->setIsSellerConfirmationDate($currentDateTime)
                    ->save();
                //end

                //Step# 4
                    $order_id = $model->getOrderId();
                    // Add seller product rejection comment to order
                    $order = Mage::getModel('sales/order')->load($order_id);
                    // using product model for adding product sku in comment
                    $product = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect('sku')
                            ->addAttributeToFilter('entity_id',$model->getProductId())->getFirstItem();
                    if ($ref == "cs") {
                        $comment =
                            "Order Item having SKU '{$product->getSku()}' is <strong>rejected</strong> on behalf of <strong>Marchant/Seller</strong> via Call Center";
                    } else {
                        $comment =
                            "Order Item having SKU '{$product->getSku()}' is <strong>rejected</strong> by seller";
                    }

                    $order->addStatusHistoryComment($comment, $order->getStatus())
                        ->setIsVisibleOnFront(0)
                        ->setIsCustomerNotified(0);
                    $order->save();
                //end

                //Step# 5    
                    //adding failed delivery status checking if all order items are rejected or cancelled
                    $check_rejected = $this->getProductReject($order_id);
                    $product_id = $model->getProductId();
                    if ($check_rejected == "true_seller") {
                        $order_sales = Mage::getModel('sales/order')->load($order_id);
                        Mage::helper('progos_ordersedit')->getFailedDeliveryStatus($order_sales);
                    }
                    //cancelling the quantity of item removed
                    Mage::getSingleton('marketplace/order')->deleteOrderItem($order_id, $product_id, $check_rejected);

                    //sending email to suppliers@elabelz.com when an item is rejected
                    Mage::helper('marketplace/vieworder')->getSellerRejectedData($product->getSku(), $model->getSellerId(), $order->getIncrementId(),'admin');

                    //send email to kustomer on an order item reject @RT
                    if (Mage::helper('progos_infotrust')->isEnableSellerItemRejectEmailAdmin()) {
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
                            Mage::log('inside reject_sellerAction seller item reject', null, 'jsonld.log');
                        }
                        //send email to Kustomer for each item reject
                        $itemRejectJsonld = Mage::helper('progos_infotrust')->getSellerItemRejectEmailJsonld($order, ['product_sku' => $product->getSku(), 'seller_id' => $model->getSellerId()]);
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('get Jsonld', null, 'jsonld.log');
                        }
                        //send email to kustomer hook
                        Mage::helper('progos_infotrust')->zendSend($itemRejectJsonld, $order, 'Order Item Reject Notification. Order#'. $order->getIncrementId(), 'Admin Seller item reject for order #'. $order->getIncrementId());
                        //if enabled logs block
                        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                            Mage::log('after reject_sellerAction seller item reject', null, 'jsonld.log');
                        }
                    }
                //end
                    if(Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromBuyer($order_id) && Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromSeller($order_id) && $check_rejected != "true_seller"){
                        $order = Mage::getModel('sales/order')->load($order_id);
                        // Set 'Confirmed' order status
                        Mage::helper('orderstatuses')->setOrderStatusConfirmed($order);

                        //send email even if one item in order accepted by seller(s)
                        //prepare data for jsonld @RT
                        if (Mage::helper('progos_infotrust')->isEnableSellerCancelEmail()) {
                            //if enabled logs block
                            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                                Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
                                Mage::log('inside reject_sellerAction seller accept', null, 'jsonld.log');
                            }
                            //prepare json ld
                            $jsonld = Mage::helper('progos_infotrust')->getSellerAcceptEmailJsonld($order);
                            //if enabled logs block
                            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                                Mage::log('get Jsonld', null, 'jsonld.log');
                            }
                            //send email to kustomer hook
                            Mage::helper('progos_infotrust')->zendSend($jsonld, $order, 'Order Accept Notification #'. $order->getIncrementId(), 'Seller item accept for order #'. $order->getIncrementId());
                            //if enabled logs block
                            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                                Mage::log('after reject_sellerAction seller accept', null, 'jsonld.log');
                            }
                        }

                    }
                //Step# 6
                    //if all items are approved by seller
                    $rejected = $this->getProductConfirmSellerRejected($order_id,$model->getSellerId());
                    if($rejected){
                        //checking if rejected items are rejected by seller only
                       $rejected_items = Mage::helper('marketplace/vieworder')->allProductsSellerRejected($order_id);
                       if(!empty($rejected_items)){
                       $totalRejectedItems = count($rejected_items);
                           if($totalRejectedItems >=1){
                               //sending email
                               Mage::helper('marketplace/vieworder')->cancelOrderItemEmail($rejected_items);
                           }
                       }
                    }
                //end#
                    $successMsg = Mage::helper('marketplace')->__('Order Rejected by Seller');
                    Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'jsonld.log');
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }

            //Step# 7
                if ($path == "sale_order") {
                    if ($ref == "cs") {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id, 'ref' => $ref));
                    } else {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                    }
                } elseif ($path == "unconfirmed_from_buyer") {
                    $this->_redirect('*/adminhtml_unconfirmedfrombuyer');
                } elseif ($path == "unconfirmed_from_seller") {
                    $this->_redirect('*/adminhtml_unconfirmedfromseller');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall');
                } elseif ($path == "logistics_master") {
                    $successMsg = Mage::helper('marketplace')->__('Order Rejected by Buyer');
                } else {
                    $this->_redirect('*/*/');
                }
         //end
        }
    }

    /**
     * Rejecting item from seller side on extended order grod or marketplace grids
     * Step# 1: Fetching all the parameters
     * Step# 2: Getting model of marketplace and order item details
     * Step# 3: Saving seller confirmation and confirmation date
     * Step# 4: Adding comment in order
     * Step# 5: Checking all all items are confirmed from buyer end and send email then to seller(s)
     * Step# 6: If all the items are rejected by seller sending a cancelation email to seller(s)
     * Step# 7: Redirecting
     */
    public function confirm_buyerAction()
    {
    //Step# 1

        $id = $this->getRequest()->getParam('id');
        $path = $this->getRequest()->getParam('path');
        $ref = $this->getRequest()->getParam("ref");
        $order_id = $this->getRequest()->getParam('order_id');
        $product_id = $this->getRequest()->getParam('product_id');
    //end
        if ($id >= 0) {
            try {
        //Step# 2
                //getting collection for marketplace
                $collection = Mage::getResourceModel('marketplace/commission_collection');
                
                /*checking if marketplace id yet not visible on extended order itrm grid like
                  when order is placed through admin
                */
                if ($id == 0) {
                    $collection->addFieldToFilter('product_id', $product_id)
                    ->addFieldToFilter('order_id', $order_id);
                    $model = $collection->getFirstItem();
                } else {
                    $model = Mage::getModel('marketplace/commission')->load($id);
                }
        //end

        //Step# 3
                
                // 12-02-2016-added by Azhar for saving the buyer confirmation/rejected datetime
                $currentDateTime = date('Y-m-d H:i:s');
                $model->setIsBuyerConfirmation('Yes')
                ->setItemOrderStatus('pending_seller')
                ->setIsBuyerConfirmationDate($currentDateTime)
                ->save();

        //end

        //Step# 4
                // Add buyer product confirmation comment to order
                $order_id = $model->getOrderId();
                $order = Mage::getModel('sales/order')->load($order_id);
                //using product model for using it in comment
                $product = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect('sku')
                            ->addAttributeToFilter('entity_id',$model->getProductId())->getFirstItem();

                if ($ref == "cs") {
                    $comment =
                        "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> on behalf of <strong>Customer/Buyer</strong> via Call Center";
                } else {
                    $comment = "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> by buyer";
                }

                $order->addStatusHistoryComment($comment, $order->getStatus())
                    ->setIsVisibleOnFront(0)
                    ->setIsCustomerNotified(0);
                $order->save();
        //end

        //Step# 5
                $seller_id = $model->getSellerId();
                //confirming if all items are accepted from buyer side
                $confirm = $this->getProductConfirm($order_id, $seller_id);
                if ($confirm) {
                    
                    if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification')
                        == 1
                    ) {
                        $this->successAfter($order_id);
                    }
                }
        //end
                $successMsg = Mage::helper('marketplace')->__('Order Confirmed from Buyer');
                Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);

                if(Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromBuyer($order_id)){
                    // Set 'Pending Supplier Confirmation' order status
                    Mage::helper('orderstatuses')->setOrderStatusPendingSupplierConfirmation($order);
                }
        //Step# 6

                if ($path == "sale_order") {
                    if ($ref == "cs") {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id, 'ref' => $ref));
                    } else {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                    }
                } elseif ($path == "unconfirmed_from_buyer") {
                    $this->_redirect('*/adminhtml_unconfirmedfrombuyer');
                } elseif ($path == "unconfirmed_from_seller") {
                    $this->_redirect('*/adminhtml_unconfirmedfromseller');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall');
                } else {
                    $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if ($path == "sale_order") {
                    if ($ref == "cs") {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id, 'ref' => $ref));
                    } else {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                    }
                } elseif ($path == "unconfirmed_from_buyer") {
                    $this->_redirect('*/adminhtml_unconfirmedfrombuyer');
                } elseif ($path == "unconfirmed_from_seller") {
                    $this->_redirect('*/adminhtml_unconfirmedfromseller');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall');
                } else {
                    $this->_redirect('*/*/');
                }
            }
        }

        #end#
    }

    /**
     * Rejecting item from seller side on extended order grod or marketplace grids
     * Step# 1: Fetching all the parameters
     * Step# 2: Getting model of marketplace and order item details
     * Step# 3: Saving seller confirmation and confirmation date
     * Step# 4: Adding comment in order
     * Step# 5: Checking if all item are cancelled/rejected and adding the failed delivery status and then cancel the qty
     * Step# 6: Checking all all items are confirmed from buyer end and send email then to seller(s)
     * Step# 7: Redirecting
     */

    public function reject_buyerAction()
    {
        //Step# 1

        $id = $this->getRequest()->getParam('id');
        $path = $this->getRequest()->getParam('path');
        $ref = $this->getRequest()->getParam("ref");
        $order_id = $this->getRequest()->getParam('order_id');
        $product_id = $this->getRequest()->getParam('product_id');
        $model = null;
        //end

        if ($id >= 0) {
            try {
        
        //Step# 2
                //for getting collection of marketplace
                $collection = Mage::getResourceModel('marketplace/commission_collection');
                
                //for getting collection when marketplace id not added in extended order
                if ($id == 0) {
                    $collection->addFieldToFilter('product_id', $product_id)
                    ->addFieldToFilter('order_id', $order_id);
                    $model = $collection->getFirstItem();
                } else {
                    $model = Mage::getModel('marketplace/commission')->load($id);
                }
        //end#

            //Step# 3
                //if rejected by buyer automatically rejeted by seller
                $currentDateTime = date('Y-m-d H:i:s');
                $model->setIsBuyerConfirmation('Rejected')
                ->setIsSellerConfirmation('Rejected')
                ->setItemOrderStatus('rejected_customer')
                ->setIsBuyerConfirmationDate($currentDateTime)
                ->setIsSellerConfirmationDate($currentDateTime)
                ->save();
            //end#

            //Step# 4
                // Add buyer product rejection comment to order
                $order_id = $model->getOrderId();
                $order = Mage::getModel('sales/order')->load($order_id);
                //using product model for using it in comment
                $product = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect('sku')
                            ->addAttributeToFilter('entity_id',$model->getProductId())->getFirstItem();

                if ($ref == "cs") {
                    $comment =
                        "Order Item having SKU '{$product->getSku()}' is <strong>rejected</strong> on behalf of <strong>Customer/Buyer</strong> via Call Center";
                } else {
                    $comment = "Order Item having SKU '{$product->getSku()}' is <strong>rejected</strong> by buyer";
                }

                $order->addStatusHistoryComment($comment, $order->getStatus())
                    ->setIsVisibleOnFront(0)
                    ->setIsCustomerNotified(0);
                $order->save();

            //end

            //Step# 5
                $product_id = $model->getProductId();
                $check_rejected = $this->getProductReject($order_id);
                Mage::getSingleton('marketplace/order')->deleteOrderItem($order_id, $product_id, $check_rejected);

                if ($check_rejected == "true_seller") {
                    $order_sales = Mage::getModel('sales/order')->load($order_id);
                    $order_sales->setFailedDelivery(1);
                    $order_sales->save();
                }
            //end

            //Step# 6
                //Check if all items are confirmed from buyer end then send email
                $confirm = $this->getProductConfirm($order_id, $model->getSellerId());

                if ($confirm) {
                    if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification')
                        == 1
                    ) {
                        $this->successAfter($order_id);
                    }
                }
            //end

                $successMsg = Mage::helper('marketplace')->__('Order Rejected by Buyer');
                Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
                // if all items confirmed from buyer and not all items rejected or cancled (second condition)
                if(Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromBuyer($order_id) && $check_rejected != "true_seller"){
                    if(Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromSeller($order_id)){ // This case can appear if anyone revert last item status from Market place and then reject from admin of from market place
                        $order = Mage::getModel('sales/order')->load($order_id);
                        // Set 'Confirmed' order status
                        Mage::helper('orderstatuses')->setOrderStatusConfirmed($order);
                    }
                    else {
                        // Set 'Pending Supplier Confirmation' order status
                        Mage::helper('orderstatuses')->setOrderStatusPendingSupplierConfirmation($order);
                    }

                }
            //Step# 7

                if ($path == "sale_order") {
                    if ($ref == "cs") {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id, 'ref' => $ref));
                    } else {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                    }
                } elseif ($path == "unconfirmed_from_buyer") {
                    $this->_redirect('*/adminhtml_unconfirmedfrombuyer');
                } elseif ($path == "unconfirmed_from_seller") {
                    $this->_redirect('*/adminhtml_unconfirmedfromseller');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall');
                } else {
                    $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                if ($path == "sale_order") {
                    if ($ref == "cs") {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id, 'ref' => $ref));
                    } else {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                    }
                } elseif ($path == "unconfirmed_from_buyer") {
                    $this->_redirect('*/adminhtml_unconfirmedfrombuyer');
                } elseif ($path == "unconfirmed_from_seller") {
                    $this->_redirect('*/adminhtml_unconfirmedfromseller');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall');
                } else {
                    $this->_redirect('*/*/');
                }
            }
        //end#
        }
    }

    /**
     * @param $orderId
     */
    public function successAfter($orderId)
    {
        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $order = Mage::getModel('sales/order')->load($orderId);
        $itemCount = 0;
        $shippingCountryId = '';
        $items = $order->getAllVisibleItems();
        $orderEmailData = array();

        foreach ($items as $item) {
            $getProductId = $item->getProductId();
            $products = Mage::helper('marketplace/marketplace')->getProductInfo($getProductId);
            $productsNew = Mage::getModel('catalog/product')->load($item->getProductId());

            if ($productsNew->getTypeId() == "configurable") {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $getProductId = Mage::getModel('catalog/product')->getIdBySku($sku);
            } else {
                $getProductId = $item->getProductId();
            }

            $order_item_status = Mage::getModel('marketplace/commission')
                ->getCollection()
                ->addFieldToFilter("product_id", $getProductId)
                ->addFieldToFilter("order_id", $order->getId())->getFirstItem();

            $isBuyerConfirmation = $order_item_status->getIsBuyerConfirmation();
            if ($isBuyerConfirmation == "Yes") {
                $isBuyerConfirmation = "Accepted";
            }

            $sellerId = $products->getSellerId();
            $productType = $products->getTypeID();

            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');
            if ($sellerShippingEnabled == 1 && $productType == 'simple') {
                /**
                 * Get the product national shipping price
                 * and international shipping price
                 * and shipping country
                 */
                $nationalShippingPrice = $products->getNationalShippingPrice();
                $internationalShippingPrice = $products->getInternationalShippingPrice();
                $sellerDefaultCountry = $products->getDefaultCountry();
                $shippingCountryId = $order->getShippingAddress()->getCountry();
            }

            /**
             * Check seller id has been set
             */
            if ($sellerId) {
                $orderPrice = $item->getBasePrice() * $item->getQtyOrdered();
                $productAmt = $item->getBasePrice();
                $productQty = $item->getQtyOrdered();
                $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice(
                    $sellerDefaultCountry,
                    $shippingCountryId,
                    $orderPrice,
                    $nationalShippingPrice,
                    $internationalShippingPrice,
                    $productQty
                );

                /**
                 * Getting seller commission percent
                 */
                $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $shippingPrice - $commissionFee;

                /**
                 * Storing commission information in database table
                 */
                if ($commissionFee > 0 || $sellerAmount > 0) {
                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($getProductId);
                    $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);

                    if ($parent_product->getSpecialPrice()) {
                        $orderPrice_sp = $parent_product->getSpecialPrice() * $item->getQtyOrdered();
                        $orderPrice_base = $parent_product->getPrice() * $item->getQtyOrdered();

                        $commissionFee = $orderPrice_sp * ($percentperproduct / 100);
                        $sellerAmount = $orderPrice_sp - $commissionFee;
                    } else {
                        $orderPrice_base = $item->getBasePrice() * $item->getQtyOrdered();
                        $commissionFee = $orderPrice_base * ($percentperproduct / 100);
                        $sellerAmount = $shippingPrice - $commissionFee;
                    }
                  }


                if ($isBuyerConfirmation == "Accepted") {
                    $orderEmailData [$itemCount] ['seller_id'] = $sellerId;
                    $orderEmailData [$itemCount] ['product_qty'] = $productQty;
                    $orderEmailData [$itemCount] ['product_id'] = $getProductId;
                    $orderEmailData [$itemCount] ['product_amt'] = $productAmt;
                    $orderEmailData [$itemCount] ['commission_fee'] = $commissionFee;
                    $orderEmailData [$itemCount] ['seller_amount'] = $sellerAmount;
                    $orderEmailData [$itemCount] ['increment_id'] = $order->getIncrementId();
                    $orderEmailData [$itemCount] ['customer_firstname'] = $order->getCustomerFirstname();
                    $orderEmailData [$itemCount] ['customer_email'] = $order->getCustomerEmail();
                    $orderEmailData [$itemCount] ['product_id_simple'] = $getProductId;
                    $orderEmailData [$itemCount] ['is_buyer_confirmation'] = $isBuyerConfirmation;
                    $orderEmailData [$itemCount] ['itemCount'] = $itemCount;
                    $itemCount = $itemCount + 1;
                }
            }
        }

        if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
            $this->sendOrderEmail($orderEmailData);
        }
    }

    /**
     *
     */
    public function status_oldAction()
    {
        $id = $this->getRequest()->getParam('id');
        $path = $this->getRequest()->getParam('path');
        $status = $this->getRequest()->getParam('status');
        $seller_id = $this->getRequest()->getParam('seller_id');

        $status_ar = [];
        $status_ar["canceled"] = "Canceled";
        $status_ar["pending"] = "Pending Customer Confirmation";
        $status_ar["pending_seller"] = "Pending Seller Confirmation";
        $status_ar["ready"] = "Ready for Processing";
        $status_ar["shipped_from_elabelz"] = "Shipped from Elabelz";
        $status_ar["failed_delivery"] = "Failed Delivery";
        $status_ar["successful_delivery"] = "Successful Delivery";
        $status_ar["complete"] = "Completed Non Refundable";

        if ($id > 0) {
            $model = Mage::getModel('marketplace/commission')->load($id);
            $order_status = $model->getOrderStatus();
            $seller_confirmation = $model->getIsSellerConfirmation();
            $customer_confirmation = $model->getIsBuyerConfirmation();
            $order_id = $model->getOrderId();

            try {
                switch ($status) {
                    case 'rbc':
                        if ($order_status == 'pending') {
                            $model->setItemOrderStatus("pending")
                                ->setIsBuyerConfirmation("No")
                                ->setIsSellerConfirmation("No")
                                ->setIsBuyerConfirmationDate('0000-00-00 00:00:00')
                                ->setIsSellerBuyerConfirmationDate('0000-00-00 00:00:00')
                                ->save();
                            $info_msg = "Buyer Confirmation status is reverted successfully.";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg =
                                "Failed: You can revert Customer Confirmation status only for Pending Confirmation orders.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'rsc':
                        if ($order_status == 'pending') {
                            $model->setIsSellerConfirmation("No")
                                ->setIsSellerBuyerConfirmationDate('0000-00-00 00:00:00');

                            if ($customer_confirmation == "Yes") {
                                $model->setItemOrderStatus("pending_seller");
                            } elseif ($customer_confirmation == "Rejected") {
                                $model->setItemOrderStatus("rejected_customer");
                            } else {
                                $model->setItemOrderStatus("pending");
                            }

                            $model->save();

                            $info_msg = "Seller Confirmation status is reverted successfully.";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg =
                                "Failed: You can revert Seller Confirmation status only for Pending Confirmation orders.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'canceled':
                        if ($order_status == "pending"
                            && ($seller_confirmation == "Rejected"
                                || $customer_confirmation == "Rejected")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'ready':
                        if ($order_status == "pending"
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'shipped_from_elabelz':
                        if ($order_status == "shipped_from_elabelz"
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'complete':
                        if ($order_status == "complete"
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'failed_delivery':
                        if ($order_status == "failed_delivery"
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'successful_delivery':
                        if ($order_status == "successful_delivery"
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'refunded':
                        if (($order_status == "closed" || $order_status == "refunded")
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    case 'pending_payment':
                        if ($order_status == "pending"
                            && ($seller_confirmation == "Yes"
                                && $customer_confirmation == "Yes")
                        ) {
                            $model->setItemOrderStatus($status)->save();
                            $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                            $msg = Mage::helper('marketplace')->__($info_msg);
                        } else {
                            $error_msg = "Cannot change Order Item status, one or more criteria is not matching.";
                            $error = Mage::helper('marketplace')->__($error_msg);
                        }
                        break;
                    default:
                        $error_msg = "Unknown order status!";
                        $error = Mage::helper('marketplace')->__($error_msg);
                }

                if ($error_msg) {
                    Mage::getSingleton('adminhtml/session')->addError($error);
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess($msg);
                }

                if ($path == "sale_order") {
                    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                } else {
                    if ($path == "seller_order") {
                        $this->_redirect('*/adminhtml_order/', array('id' => $seller_id));
                    } elseif ($path == "completed_orders") {
                        $this->_redirect('*/adminhtml_orderview/');
                    } elseif ($path == "all_orders") {
                        $this->_redirect('*/adminhtml_orderitemsall/');
                    } else {
                        $this->_redirect('*/*/');
                    }
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if ($path == "sale_order") {
                    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                } elseif ($path == "seller_order") {
                    $this->_redirect('*/adminhtml_order/', array('id' => $seller_id));
                } elseif ($path == "completed_orders") {
                    $this->_redirect('*/adminhtml_orderview/');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall/');
                } else {
                    $this->_redirect('*/*/');
                }
            }
        }
    }

    /**
     *
     */
    public function statusAction()
    {
        $id = $this->getRequest()->getParam('id');
        $path = $this->getRequest()->getParam('path');
        $status = $this->getRequest()->getParam('status');
        $seller_id = $this->getRequest()->getParam('seller_id');

        $status_ar = [];
        $status_ar["canceled"] = "Canceled";
        $status_ar["processing"] = "Processing";
        $status_ar["pending_customer_confirmation"] = "Pending Customer Confirmation";
        $status_ar["pending_seller_confirmation"] = "Pending Seller Confirmation";
        $status_ar["ready"] = "Ready for Processing";
        $status_ar["shipped_from_elabelz"] = "Shipped from Elabelz";
        $status_ar["failed_delivery"] = "Failed Delivery";
        $status_ar["successful_delivery"] = "Successful Delivery";
        $status_ar["complete"] = "Completed Non Refundable";

        if ($id) {
            $model = Mage::getModel('marketplace/commission')->load($id);
            $order_id = $model->getOrderId();
            $order_status = $model->getOrderStatus();
            $seller_confirmation = $model->getIsSellerConfirmation();
            $customer_confirmation = $model->getIsBuyerConfirmation();
            // rbc : Revert Buyer Confirmation
            if ($status == 'rbc') {
                if ($order_status == 'pending' || $order_status == 'pending_seller_confirmation' || $order_status == 'pending_customer_confirmation' || $order_status == 'confirmed') {
                    if ($customer_confirmation === "Rejected") {
                        $error_msg = "Failed: You can't revert Customer Confirmation status once it is Rejected.";
                        $error = Mage::helper('marketplace')->__($error_msg);
                    } else {
                        $model->setIsBuyerConfirmation("No")
                              ->setItemOrderStatus("pending")
                              ->setIsSellerConfirmation("No")
                              ->setIsBuyerConfirmationDate('0000-00-00 00:00:00')
                              ->setIsSellerBuyerConfirmationDate('0000-00-00 00:00:00')
                              ->save();

                        $info_msg = "Buyer Confirmation status is reverted successfully.";
                        $msg = Mage::helper('marketplace')->__($info_msg);

                        // Add buyer product revert comment to order
                        $order = Mage::getModel('sales/order')->load($order_id);
                        $product = Mage::getModel("catalog/product")->load($model->getProductId());
                        $comment = "Order Item having SKU '{$product->getSku()}' is <strong>reverted</strong>";

                        try{
                            $order->addStatusHistoryComment($comment, $order->getStatus())
                                ->setIsVisibleOnFront(0)
                                ->setIsCustomerNotified(0);
                            $order->save();
                            // Set 'Pending buyer Confirmation' order status
                            Mage::helper('orderstatuses')->setOrderStatusPendingCustomerConfirmation($order);
                        }catch (Exception $e){
                            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        }

                    }
                } else {
                    $error_msg = "Failed: You can revert Customer Confirmation status only for Pending Confirmation orders.";
                    $error = Mage::helper('marketplace')->__($error_msg);
                }
            } elseif ($status == 'rsc') { // // rsc : Revert Seller Confirmation
                if ($order_status == 'pending' || $order_status == 'pending_seller_confirmation' || $order_status == 'pending_customer_confirmation' || $order_status == 'pending_buyer_confirmation' || $order_status == 'confirmed') {
                    if ($seller_confirmation === "Rejected") {
                        $error_msg = "Failed: You can't revert Seller Confirmation status once it is Rejected..";
                        $error = Mage::helper('marketplace')->__($error_msg);
                    } else {
                        $model->setIsSellerConfirmation("No")
                            ->setIsSellerConfirmationDate('0000-00-00 00:00:00');

                        if ($customer_confirmation == "Yes") {
                            $model->setItemOrderStatus("pending_seller");
                        } elseif ($customer_confirmation == "Rejected") {
                            $model->setItemOrderStatus("rejected_customer");
                        } else {
                            $model->setItemOrderStatus("pending");
                        }

                        $model->save();

                        $info_msg = "Seller Confirmation status is reverted successfully.";
                        $msg = Mage::helper('marketplace')->__($info_msg);

                        // Add buyer product revert comment to order
                        $order = Mage::getModel('sales/order')->load($order_id);
                        $product = Mage::getModel("catalog/product")->load($model->getProductId());
                        $comment = "Order Item having SKU '{$product->getSku()}' is <strong>reverted</strong>";
                        try{
                            $order->addStatusHistoryComment($comment, $order->getStatus())
                                ->setIsVisibleOnFront(0)
                                ->setIsCustomerNotified(0);
                            $order->save();
                            // Set 'Pending Seller Confirmation' order status,
                            // if at-least one item pending from buyer status buyer confirmation
                            // else seller confirmation
                            if(Mage::helper('marketplace/vieworder')->isAllItemsConfirmedFromBuyer($order_id)){
                                Mage::helper('orderstatuses')->setOrderStatusPendingSupplierConfirmation($order);
                            }
                            else{
                                Mage::helper('orderstatuses')->setOrderStatusPendingCustomerConfirmation($order);
                            }
                        }catch (Exception $e){
                            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        }

                    }
                } else {
                    $error_msg = "Failed: You can revert Seller Confirmation status only for Pending Seller/Buyer Confirmation orders.";
                    $error = Mage::helper('marketplace')->__($error_msg);
                }
            }

            if ($id > 0 && $status != 'rsc' && $status != 'rbc') {
                try {
                    switch ($order_status) {
                        case 'pending':
                            $ar_pending = [
                                "canceled",
                                "processing",
                            ];

                            if (in_array($status, $ar_pending) && $status == "processing") {
                                if ($seller_confirmation == "Yes" && $customer_confirmation == "Yes") {
                                    $model->setItemOrderStatus($status)->save();
                                    $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                    $msg = Mage::helper('marketplace')->__($info_msg);
                                } else {
                                    $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                        . ", it must be confirmed from seller and buyer.";
                                    $error = Mage::helper('marketplace')->__($error_msg);
                                }
                            } elseif (in_array($status, $ar_pending) && $status == "canceled") {
                                if ($seller_confirmation == "Rejected" || $customer_confirmation == "Rejected") {
                                    $model->setItemOrderStatus($status)->save();
                                    $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                    $msg = Mage::helper('marketplace')->__($info_msg);
                                } else {
                                    $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                        . ", item must be rejected from buyer or seller.";
                                    $error = Mage::helper('marketplace')->__($error_msg);
                                }
                            } else {
                                $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                    . ", according to current order status you can only set item status to 'Processing' and 'Canceled'.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'canceled':
                            if ($seller_confirmation == "Rejected" || $customer_confirmation == "Rejected") {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot set this item as " . $status_ar[$status];
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'refunded':
                            if ($seller_confirmation == "Yes" && $customer_confirmation == "Yes") {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot " . $status_ar[$status] . " this order item.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'shipped_from_elabelz':
                            $ar_shipped_from_elabelz = [
                                "shipped_from_elabelz",
                                "failed_delivery",
                                "successful_delivery",
                                "complete",
                                "refunded",
                                "canceled",
                            ];
                            if (in_array($status, $ar_shipped_from_elabelz)
                                && ($seller_confirmation == "Yes"
                                    && $customer_confirmation == "Yes")
                            ) {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                    . ", according to current order status you can only set item status to 'Shipped from Elabelz', 'Failed Delivery', 'Successful Delivery', 'Completed Non Refundable', 'Refunded' and 'Canceled'.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'complete':
                            $ar_complete = [
                                "complete",
                                "refunded",
                            ];
                            if (in_array($status, $ar_complete)
                                && ($seller_confirmation == "Yes"
                                    && $customer_confirmation == "Yes")
                            ) {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                    . ", according to current order status you can only set item status to 'Completed Non Refundable' and 'Refunded'.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'failed_delivery':
                            $ar_failed_delivery = [
                                "failed_delivery",
                                "canceled",
                            ];
                            if (in_array($status, $ar_failed_delivery)
                                && ($seller_confirmation == "Yes"
                                    && $customer_confirmation == "Yes")
                            ) {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                    . ", according to current order status you can only set item status to 'Failed Deliver' and 'Canceled'.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'successful_delivery':
                            $ar_successful_delivery = [
                                "successful_delivery",
                                "complete",
                                "refunded",
                            ];
                            if (in_array($status, $ar_successful_delivery)
                                && ($seller_confirmation == "Yes"
                                    && $customer_confirmation == "Yes")
                            ) {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                    . ", according to current order status you can only set item status to 'Completed Non Refundable', 'Successful Delivery' and 'Refunded'.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        case 'processing':
                            $ar_processing = [
                                "processing",
                                "canceled",
                                "ready",
                            ];

                            if (in_array($status, $ar_processing)) {
                                $model->setItemOrderStatus($status)->save();
                                $info_msg = "Order item status is successfully changed to " . $status_ar[$status] . ".";
                                $msg = Mage::helper('marketplace')->__($info_msg);
                            } else {
                                $error_msg = "Cannot change Order Item status to " . $status_ar[$status]
                                    . ", according to current order status you can only set item status to 'Processing', 'Canceled' and 'Ready for Processing'.";
                                $error = Mage::helper('marketplace')->__($error_msg);
                            }
                            break;
                        default:
                            $error_msg = "Unknown order status!";
                            $error = Mage::helper('marketplace')->__($error_msg);
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    if ($path == "sale_order") {
                        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
                    } elseif ($path == "seller_order") {
                        $this->_redirect('*/adminhtml_order/', array('id' => $seller_id));
                    } elseif ($path == "completed_orders") {
                        $this->_redirect('*/adminhtml_orderview/');
                    } elseif ($path == "all_orders") {
                        $this->_redirect('*/adminhtml_orderitemsall/');
                    } elseif ($path == "order_edit") {
                        $this->_redirect('adminhtml/orders/index', array('order_id' => $order_id));
                    } else {
                        $this->_redirect('*/*/');
                    }
                }
            }

            if ($error_msg) {
                Mage::getSingleton('adminhtml/session')->addError($error);
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            }

            if ($path == "sale_order") {
                $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
            } else {
                if ($path == "seller_order") {
                    $this->_redirect('*/adminhtml_order/', array('id' => $seller_id));
                } elseif ($path == "completed_orders") {
                    $this->_redirect('*/adminhtml_orderview/');
                } elseif ($path == "all_orders") {
                    $this->_redirect('*/adminhtml_orderitemsall/');
                } elseif ($path == "order_edit") {
                    $this->_redirect('adminhtml/orders/index', array('order_id' => $order_id));
                } else {
                    $this->_redirect('*/*/');
                }
            }
        }
    }

    /**
     * @param $getOrderId
     * @param $getSellerId
     * @return bool
     */
    public function getProductConfirm($getOrderId, $getSellerId)
    {
        $counter = 0;
        $i = 0;
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        $notConfirmedProducts = Mage::helper('marketplace/vieworder')->notConfirmedOrderProductIds($getSellerId, $getOrderId);
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedOrderProductIds($getSellerId, $getOrderId);
        $totalRejectedProducts = count($rejectedProducts);
        $totalOrder = count($orderDetails->getAllVisibleItems());

        foreach ($orderDetails->getAllVisibleItems() as $item) {
            if ($item->getStatus() == "Canceled") {
                $itemid[$i] = $item->getProductId();
                $i = $i + 1;
            }
        }

        foreach ($notConfirmedProducts as $not) {
            if (!in_array($not, $itemid)) {
                $counter = $counter + 1;
            }
        }

        if ($counter == 0) {
            $totalApproved = $totalOrder - $totalRejectedProducts;
            if ($totalApproved == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function getProductConfirmRejected($getOrderId, $getSellerId)
    {
        $orderDetails = Mage::getModel('marketplace/commission')->getCollection();
        $orderDetails->addFieldToFilter('order_id',$getOrderId);
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedOrderProduct($getOrderId);
        $totalRejectedProducts = count($rejectedProducts);
        $totalOrder = count($orderDetails);

        $notConfirmedProductsTotal = $totalOrder-$totalRejectedProducts;

        if($notConfirmedProductsTotal == 0) {
            return true;
        }
        else{
            return false;
        }
    }

    public function getProductConfirmSellerRejected($getOrderId, $getSellerId)
    {
        $orderDetails = Mage::getModel('marketplace/commission')->getCollection();
        $orderDetails->addFieldToFilter('order_id',$getOrderId);
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedOrderProductSeller($getSellerId, $getOrderId);
        $totalRejectedProducts = count($rejectedProducts);
        $totalOrder = count($orderDetails);

        $notConfirmedProductsTotal = $totalOrder-$totalRejectedProducts;

        if($notConfirmedProductsTotal == 0) {
            return true;
        }
        else{
            return false;
        }
    }


    /**
     * @param $getOrderId
     * @return string
     */
    public function getProductReject($getOrderId)
    {
        $orderDetails = Mage::getModel('marketplace/commission')->getCollection()
                                ->addFieldToFilter('order_id', $getOrderId);

        $rejectedProducts = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter('order_id', $getOrderId)
            ->addFieldToFilter('is_seller_confirmation', 'Rejected');

        $canceledProducts = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter('order_id', $getOrderId)
            ->addFieldToFilter('item_order_status', 'canceled')
            ->addFieldToFilter('is_seller_confirmation', array('in' => array("Yes", "No")));
        $totalRejectedProducts = count($rejectedProducts);
        $totalCanceledProducts = count($canceledProducts);
        $totalOrder = count($orderDetails);
        $totalRejectedProducts = $totalOrder - $totalRejectedProducts - $totalCanceledProducts;

        if ($totalRejectedProducts == 0) {
            return "true_seller";
        } else {
            return "false_seller";
        }
    }

    /**
     * @param $orderEmailData
     */
    public function sendOrderEmail($orderEmailData)
    {
        $sellerIds = array();
        $displayProductCommission = Mage::helper('marketplace')->__('Seller Commission Fee');
        $displaySellerAmount = Mage::helper('marketplace')->__('Seller Amount');
        $displayProductImage = Mage::helper('marketplace')->__('Product Image');
        $displayProductName = Mage::helper('marketplace')->__('Product Name');
        $displayProductQty = Mage::helper('marketplace')->__('Product QTY');
        $displayProductAmt = Mage::helper('marketplace')->__('Product Amount');
        $displayProductStatus = Mage::helper('marketplace')->__('Product Status');

        foreach ($orderEmailData as $data) {
            if (!in_array($data ['seller_id'], $sellerIds)) {
                $sellerIds [] = $data ['seller_id'];
            }
        }

        foreach ($sellerIds as $key => $id) {
            $totalProductAmt = $totalCommissionFee = $totalSellerAmt = 0;
            $productDetails =
                '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
            $productDetails .= '<thead><tr>';
            $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductImage
                . '</th><th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductName
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductQty
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductAmt . '</th>';
            $productDetails .= '<th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductCommission
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displaySellerAmount
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductStatus . '</th></tr></thead>';
            $productDetails .= '<tbody bgcolor="#F6F6F6">';
            $currencySymbol = Mage::app()->getLocale()
                ->currency(
                    Mage::app()->getStore()->getCurrentCurrencyCode()
                )->getSymbol();
            foreach ($orderEmailData as $data) {
                if ($id == $data ['seller_id']) {
                    $sellerId = $data ['seller_id'];
                    $incrementId = $data ['increment_id'];
                    $groupId = Mage::helper('marketplace')->getGroupId();
                    $productId = $data ['product_id'];
                    $product = Mage::helper('marketplace/marketplace')->getProductInfo($productId);
                    $productGroupId = $product->getGroupId();
                    $productName = $product->getName();
                    $productAmt = $data ['product_amt'] * $data ['product_qty'];
                    $productStatus = $data['is_buyer_confirmation'];
                    $productsNew = Mage::getModel('catalog/product')->load($productId);
                    $productImg = '';
                    //as sometimes image file does not exists in media gallery
                    //core file trhows exception, to avoid exception as it halts rest of the code 
                    //execution, this code will check image existance before the call is made
                    //to fetch image url
                    if ($productsNew->getImage() != null && $productsNew->getImage() != 'no_selection') {
                        $file = $productsNew->getImage();
                        $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();
                        $baseFile = $baseDir . $file;
                        if (file_exists($baseFile)) {
                            $productImg = $productsNew->getImageUrl();
                        }
                    }

                    if ($productsNew->getTypeId() == "configurable") {
                        $productsNew = Mage::getModel('catalog/product')->load($productId);
                        if ($productsNew->getSupplierSku() != "") {
                            $product_sku = $productsNew->getSupplierSku();
                        } else {
                            $product_sku = $productsNew->getSku();
                        }
                        //as sometimes image file does not exists in media gallery
                        //core file trhows exception, to avoid exception as it halts rest of the code 
                        //execution, this code will check image existance before the call is made
                        //to fetch image url
                        if ($productsNew->getImage() != null && $productsNew->getImage() != 'no_selection') {
                            $file = $productsNew->getImage();
                            $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();
                            $baseFile = $baseDir . $file;
                            if (file_exists($baseFile)) {
                                $productImg = $productsNew->getImageUrl();
                            }
                        }
                        $product_color = $productsNew->getAttributeText('color');
                        $product_size = $productsNew->getAttributeText('size');
                    } else {
                        if ($productsNew->getSupplierSku() != "") {
                            $product_sku = $productsNew->getSupplierSku();
                        } else {
                            $product_sku = $productsNew->getSku();
                        }
                        $product_color = $productsNew->getAttributeText('color');
                        $product_size = $productsNew->getAttributeText('size');
                    }

                    if ($product_sku) {
                        $product_sku = "<br/>SKU:&nbsp;" . $product_sku;
                    } else {
                        $product_sku = "";
                    }

                    if ($product_size) {
                        $product_size = "<br/>Size:&nbsp;" . $product_size;
                    } else {
                        $product_size = "";
                    }

                    if ($product_color) {
                        $product_color = "<br/>Color:&nbsp;" . $product_color;
                    } else {
                        $product_color = "";
                    }

                    $productOptions = $product_sku . $product_size . $product_color;
                    $productDetails .= '<tr>';
                    $productDetails .= '<td align="cenetr" valign="center" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">';
                    if ($productImg != '') {
                        $productDetails .= '<img src="'
                        . $productImg
                        . '" width="70px">';
                    }
                    $productDetails .= '</td><td align="left" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $productName . '<br/>' . $productOptions
                        . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . round($data ['product_qty']) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $currencySymbol . round($productAmt, 2)
                        . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $currencySymbol . round($data ['commission_fee'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $currencySymbol . round($data ['seller_amount'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $productStatus . '</td>';
                    $totalProductAmt = $totalProductAmt + $productAmt;
                    $totalCommissionFee = $totalCommissionFee + $data ['commission_fee'];
                    $totalSellerAmt = $totalSellerAmt + $data ['seller_amount'];

                    $customerEmail = $data ['customer_email'];
                    $customerFirstname = $data ['customer_firstname'];
                    $productDetails .= '</tr>';
                }
            }

            $productDetails .= '</tbody><tfoot>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Seller Commision Fee</td><td align="center" style="padding:3px 9px"><span>'
                . $currencySymbol . round($totalCommissionFee, 2) . '</span></td></tr>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Total Amount</td><td align="center" style="padding:3px 9px"><span>'
                . $currencySymbol . round($totalProductAmt, 2) . '</span></td></tr>';
            $productDetails .= '</tfoot></table>';

            if ($groupId == $productGroupId) {
                $templateId = (int)Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification_template_selection');
                $adminEmailId = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
                $toName = Mage::getStoreConfig("trans_email/ident_$adminEmailId/name");
                $toMailId = Mage::getStoreConfig("trans_email/ident_$adminEmailId/email");

                if ($templateId) {
                    $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateId);
                } else {
                    $emailTemplate = Mage::getModel('core/email_template')
                        ->loadDefault('marketplace_admin_approval_seller_registration_sales_notification_template_selection');
                }

                $customer = Mage::helper('marketplace/marketplace')->loadCustomerData($sellerId);
                $sellerName = $customer->getName();
                $sellerEmail = $customer->getEmail();
                $recipient = $toMailId;
                $sellerStore = Mage::app()->getStore()->getName();
                $recipientSeller = $sellerEmail;
                $emailTemplate->setSenderName($toName);
                $emailTemplate->setSenderEmail($toMailId);

                $emailTemplateVariablesValue = (array(
                    'ownername' => $toName,
                    'productdetails' => $productDetails,
                    'order_id' => $incrementId,
                    'seller_store' => $sellerStore,
                    'customer_email' => $customerEmail,
                    'customer_firstname' => $customerFirstname,
                ));
                $emailTemplate->setDesignConfig(array(
                    'area' => 'frontend',
                ));
                $emailTemplate->getProcessedTemplate($emailTemplateVariablesValue);

                /**
                 * Send email to the recipient
                 */
                $emailTemplate->send($recipient, $toName, $emailTemplateVariablesValue);
                $emailTemplateVariablesValue = (array(
                    'ownername' => $sellerName,
                    'productdetails' => $productDetails,
                    'order_id' => $incrementId,
                    'seller_store' => $sellerStore,
                    'customer_email' => $customerEmail,
                    'customer_firstname' => $customerFirstname,

                ));

                $emailTemplate->send($recipientSeller, $sellerName, $emailTemplateVariablesValue);
            }
        }
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}