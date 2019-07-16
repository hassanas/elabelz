<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php
/**
 * Class Progos_OrdersEdit_Model_Edit
 */
class Progos_OrdersEdit_Model_Edit extends MageWorx_OrdersEdit_Model_Edit
{
    /**
     * Apply all the changes to order and save it
     *
     * @return $this
     * @throws Exception
     */
    public function saveOrder()
    {
        // Validate important parameters. Throws exception on error.
        $this->validateBeforeSave();

        // Start saving process. Save addresses, payment, items etc. (if has changes)
        $this->updateAddresses();
        $this->updatePayment();
        $this->addUpdateItems();
        $this->prepareBeforeSaving();

        // Finally save the order and the quote with updated data
        /** Begin: Adding Commisions from Apptha Marketplace extension */
        $this->changeCommission($this->getOrder());
        /** End: Adding Commisions from Apptha Marketplace extension */

        $this->getQuote()->save();
        $this->getOrder()->save();

        return $this;
    }

    /**
     * Apply all the changes to marketplace
     *
     * @return $this
     * @throws Exception
     */

    public function saveCommission( $order ){
        /** Begin: Adding Commisions from Apptha Marketplace extension */
        $this->changeCommission( $order );
        /** End: Adding Commisions from Apptha Marketplace extension */
        return true;
    }

    /**
     * Update and save result items with childes
     *
     * @return $this
     */
    protected function addUpdateItems()
    {
        $this->_savedOrderItems = array();

        $this->saveNewOrderItems();
        $this->saveOldOrderItems();

        /** Begin: Cancel order status via order edit */
        if (isset($this->changes['status']) && $this->changes['status'] == "canceled") {
            if ($this->getOrder()->getStatus() == 'pending') {
                $this->cancelAllOrderItem($this->getOrder());
            } else {
                $this->changes['status'] = $this->getOrder()->getStatus();
            }
        }
        /** End: Cancel order status via order edit */

        $this->postProcessItems();

        return $this;
    }

    /**
     * Add data from the changes to the order before saving
     *
     * @return $this
     */
    protected function prepareBeforeSaving()
    {
        $this->collectItemsQty();

        $this->changes['customer_id'] = empty($this->changes['customer_id']) ?
            $this->getOrder()->getCustomerId() :
            $this->changes['customer_id'];

        if (isset($this->changes['status'])) {
            /** Begin: Sync HighStreet extension code with Mageworx */
            if ($this->getOrder()->getState() == 'pending_payment' && $this->getOrder()->getStatus() == 'pending_payment') {
                $this->changes['state'] = 'new';
            } else {
                /** End: Sync HighStreet extension code with Mageworx */
                $this->changes['state'] = $this->changes['status'];
            }
        }

        $this->getOrder()->addData($this->changes);
        $this->getOrder()->setData('is_edited', 1);

        $this->getLogModel()->commitOrderChanges($this->getOrder());

        return $this;
    }

    /**
     * Add new products to order
     *
     * @return $this
     */
    protected function saveNewOrderItems()
    {
        if (empty($this->changes['product_to_add'])) {
            return $this;
        }

        $quote = $this->getQuote();
        $order = $this->getOrder();

        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = $order->getItemByQuoteItemId($quoteItem->getItemId());
            if ($orderItem && $orderItem->getItemId()) {
                continue;
            }

            $quoteItem->save();

            $orderItem = $this->getConverter()->itemToOrderItem($quoteItem, $orderItem);
            $order->addItem($orderItem);
            if ($orderItem->save()) {
                /** Begin: Adding Commisions from Apptha Marketplace extension */
                $this->removeQtyFromStock($orderItem->getProductId(), $orderItem->getQtyOrdered(), $orderItem->getSku());
                /** End: Adding Commisions from Apptha Marketplace extension */
            }

            /*** Add new items to log ***/
            $changedItem = $quoteItem;
            $itemChange = array(
                'name'       => $changedItem->getName(),
                'qty_before' => 0,
                'qty_after'  => $changedItem->getQty()
            );
            $this->getLogModel()->addItemChange($changedItem->getId(), $itemChange);

            $this->_savedOrderItems[] = $orderItem->getItemId();
        }

        /** @var Mage_Sales_Model_Quote_Item $childQuoteItem */
        foreach ($quote->getAllItems() as $childQuoteItem) {
            /** @var Mage_Sales_Model_Order_Item $childOrderItem */
            $childOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getItemId());

            /*** Add items relations for configurable and bundle products ***/
            if ($childQuoteItem->getParentItemId()) {
                /** @var Mage_Sales_Model_Order_Item $parentOrderItem */
                $parentOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getParentItemId());

                $childOrderItem->setParentItemId($parentOrderItem->getItemId());
                $childOrderItem->save();
            }
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param int|float $newQty
     * @param int|float $origQtyOrdered
     * @return Mage_Sales_Model_Order_Item
     */
    protected function changeItemQty($orderItem, $newQty, $origQtyOrdered)
    {
        $orderItemQty = $this->getQtyRest($orderItem);

        if ($newQty < $orderItemQty) {
            $qtyToRemove = $orderItemQty - $newQty;
            $orderItem->setQtyOrdered($origQtyOrdered);
            $this->returnOrderItem($orderItem, $qtyToRemove);
            if($orderItem->getProductType() == 'simple'){
               $this->addQtyToStock($orderItem->getProductId(), $qtyToRemove, $orderItem->getSku());
            }
        } else {
            $qtyDiff = $newQty - $orderItemQty;
            /** Begin: Loading product from product SKU */
            if($orderItem->getProductType() == 'simple'){
              $this->removeQtyFromStock($orderItem->getProductId(), $qtyDiff, $orderItem->getSku());
            }
            /** End: Loading product from product SKU */
            $orderItem->setQtyOrdered($origQtyOrdered + $qtyDiff);
        }

        return $this;
    }

    /**
     * Change product commission for Apptha Marketplace extension
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function changeCommission(Mage_Sales_Model_Order $order)
    {
        $i = 0;
        $orderData = array();
        foreach ($order->getAllItems() as $sItem) {
            /** @var Mage_Sales_Model_Order_Item $sItem */
            if ($sItem->getProductType() == "simple") {
                $orderData[$i]['product_type'] = $sItem->getProductType();
                $orderData[$i]['order_id'] = $sItem->getOrderId();
                $orderData[$i]['increment_id'] = $order->getIncrementId();
                $orderData[$i]['order_status'] = $order->getStatus();
                $orderData[$i]['order_state'] = $order->getState();
                $orderData[$i]['order_grand_total'] = $order->getGrandTotal();

                if ($order->getCustomerId() == "") {
                    $customerId = 0;
                } else {
                    $customerId = $order->getCustomerId();
                }

                $orderData[$i]['customer_id'] = $customerId;
                $orderData[$i]['product_id'] = $sItem->getProductId();
                $orderData[$i]['item_id'] = $sItem->getId();
                $orderData[$i]['item_name'] = $sItem->getName();
                $orderData[$i]['item_sku'] = $sItem->getSku();
                $orderData[$i]['item_price'] = $sItem->getPrice();
                $orderData[$i]['parent_item_price'] = $sItem->getParentItemId();

                $pItemId = $sItem->getParentItemId();

                /** @var Mage_Sales_Model_Order_Item $item */
                $item = Mage::getModel('sales/order_item')->load("$pItemId");

                $nProduct = Mage::getModel('catalog/product')->load($item->getProductId());

                $orderData[$i]['p_product_type'] = $item->getProductType();
                $orderData[$i]['p_product_id'] = $item->getProductId();
                $orderData[$i]['p_item_id'] = $item->getId();
                $orderData[$i]['p_item_sku'] = $item->getSku();
                $orderData[$i]['seller_id'] = $nProduct->getSellerId();
                $orderData[$i]['p_item_price'] = $item->getBasePrice();
                $orderData[$i]['qty_cancelled'] = $item->getQtyCanceled();
                $qty = intval($item->getQtyOrdered());

                $orderData[$i]['p_quantity'] = $qty;

                $i++;
            }
        }
        // Performing the operation
        foreach ($orderData as $_order) {
            $orderId = $_order['order_id'];
            $productId = $_order['product_id'];

            if ($productId > 0) {
                // Check if product Exists in the order. If not add it
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT * FROM `marketplace_commission` WHERE order_id='$orderId' and product_id = '$productId'";
                $commissionData = $connection->fetchAll($sql);

                if ($commissionData[0]['id']) {
                    if ($_order['qty_cancelled'] > 0 && $_order['product_id'] == $commissionData[0]['product_id']) {
                        $quantity = $_order['p_quantity'] - $_order['qty_cancelled'];
                        $productPrice = $_order['p_item_price'] * $quantity;

                        $data = $this->calculateCommission($_order['seller_id'], $productId, $quantity);
                        $commissionFee = $data['commissionFee'];
                        $sellerAmount = $data['sellerAmount'];

                        //Updating existing record
                        try {
                            $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                            $commission_arr = array();
                            $commission_arr['product_qty'] = $quantity;
                            $commission_arr['product_amt'] = $productPrice;
                            $commission_arr['commission_fee'] = $commissionFee;
                            $commission_arr['seller_amount'] = $sellerAmount;
                            $commission_arr['credited'] = 0;
                            $commission_arr['order_total'] = $_order['order_grand_total'];

                            if ($quantity == 0) {
                                $commission_arr['item_order_status'] = 'canceled';
                            }

                            // Incase item is removed
                            $where = array();
                            $where[] = $write_connection->quoteInto('order_id =?', $orderId);
                            $where[] = $write_connection->quoteInto('product_id =?', $productId);
                            $write_connection->update('marketplace_commission', $commission_arr, $where);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    } else {
                        if ($commissionData[0]['product_qty'] < $_order['p_quantity']) {
                            $productPrice = $_order['p_item_price'] * $_order['p_quantity'];
                            $data = $this->calculateCommission($_order['seller_id'], $productId, $_order['p_quantity']);
                            $commissionFee = $data['commissionFee'];
                            $sellerAmount = $data['sellerAmount'];

                            try {
                                $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                                $commission_arr = array();
                                $commission_arr['product_qty'] = $_order['p_quantity'];
                                $commission_arr['product_amt'] = $productPrice;
                                $commission_arr['commission_fee'] = $commissionFee;
                                $commission_arr['seller_amount'] = $sellerAmount;
                                $commission_arr['credited'] = 0;
                                $commission_arr['order_total'] = $_order['order_grand_total'];
                                $where = array();
                                $where [] = $write_connection->quoteInto('order_id =?', $orderId);
                                $where [] = $write_connection->quoteInto('product_id =?', $productId);
                                $write_connection->update('marketplace_commission', $commission_arr, $where);
                            } catch (Exception $e) {
                                echo $e->getMessage();
                            }
                        }
                    }
                } else {
                    try {
                        $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $quantity = $_order['p_quantity'] - $_order['qty_cancelled'];
                        $productPrice = $_order['p_item_price'] * $quantity;
                        $data = $this->calculateCommission($_order['seller_id'], $productId, $_order['p_quantity']);
                        $commissionFee = $data['commissionFee'];
                        $sellerAmount = $data['sellerAmount'];
                        $changesdate = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                        $commission_arr = array();
                        $commission_arr['seller_id'] = $_order['seller_id'];
                        $commission_arr['product_id'] = $productId;
                        $commission_arr['product_qty'] = $_order['p_quantity'];
                        $commission_arr['product_amt'] = $productPrice;
                        $commission_arr['order_id'] = $orderId;
                        $commission_arr['increment_id'] = $_order['increment_id'];
                        $commission_arr['commission_fee'] = $commissionFee;
                        $commission_arr['seller_amount'] = $sellerAmount;
                        $commission_arr['credited'] = 0;
                        $commission_arr['order_total'] = $_order['order_grand_total'];
                        $commission_arr['order_status'] = $_order['order_status'];
                        $commission_arr['customer_id'] = $_order['customer_id'];
                        $commission_arr['is_seller_confirmation'] = 'Yes';
                        $commission_arr['is_buyer_confirmation'] = 'Yes';
                        $commission_arr['is_seller_confirmation_date'] = $changesdate;
                        $commission_arr['is_buyer_confirmation_date'] = $changesdate;
                        $commission_arr['status'] = 1;
                        $commission_arr['created_at'] = $changesdate;
                        if( $_order['order_status'] == 'shipped_from_elabelz' )
                            $commission_arr['item_order_status'] = 'shipped_from_elabelz';
                        else if( $_order['order_status'] == 'successful_delivery' )
                            $commission_arr['item_order_status'] = 'successful_delivery';
                        else
                            $commission_arr['item_order_status'] = 'ready';

                        $commission_arr['commission_percentage'] = $data['commission'];
                        $write_connection->insert('marketplace_commission', $commission_arr);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }

                // Updating Order Total for all products in commission Table after all operations performed
                try {
                    $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $commission_arr = array();
                    $commission_arr['order_total'] = $_order['order_grand_total'];
                    $where = array();
                    $where [] = $write_connection->quoteInto('order_id =?', $orderId);
                    $where [] = $write_connection->quoteInto('product_id =?', $productId);
                    $write_connection->update('marketplace_commission', $commission_arr, $where);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }

    /**
     * Calculate product commission for Apptha Marketplace extension
     *
     * @param $seller_id
     * @param $productId
     * @param $orderItemQty
     * @return mixed
     */
    public function calculateCommission($seller_id, $productId, $orderItemQty)
    {
        $sellerData = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);

        // Load parent product by parent Product ID
        /** @var Mage_Catalog_Model_Product $parent_product */
        $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);
        if ($parent_product->getSpecialPrice()) {
            $orderPrice_sp = $parent_product->getSpecialPrice() * $orderItemQty;
            $orderPrice_base = $parent_product->getPrice() * $orderItemQty;

            $commissionFee = $orderPrice_base * ($sellerData['commission'] / 100);
            $discount_price = $orderPrice_base - $orderPrice_sp;

            $commissionFee = $commissionFee - $discount_price;
            $sellerAmount = $orderPrice_sp - $commissionFee;
        } else {
            $orderPrice_base = $parent_product->getPrice() * $orderItemQty;
            $commissionFee = $orderPrice_base * ($sellerData['commission'] / 100);
            $sellerAmount = $orderPrice_base - $commissionFee;
        }

        $data['commissionFee'] = $commissionFee;
        $data['sellerAmount'] = $sellerAmount;
        $data['commission'] = $sellerData['commission'];

        return $data;
    }

    /**
     * Cancel all order items during order edit
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function cancelAllOrderItem(Mage_Sales_Model_Order $order)
    {
        foreach ($order->getAllItems() as $orderItem) {
            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $qtyToCancel = $orderItem->getData('qty_ordered') - $orderItem->getQtyCanceled();

            if ($orderItem->getStatusId() !== Mage_Sales_Model_Order_Item::STATUS_CANCELED) {
                if (!$qtyToCancel) {
                    $qtyToCancel = $orderItem->getQtyToCancel();
                }

                $origQtyCancelled = $orderItem->getQtyCanceled();
                $orderItem->setQtyCanceled($origQtyCancelled + $qtyToCancel);
                Mage::dispatchEvent('sales_order_item_cancel', array('item' => $orderItem));

                $orderItem->setTaxCanceled(
                    $orderItem->getTaxCanceled() +
                    $orderItem->getBaseTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
                );

                $orderItem->setHiddenTaxCanceled(
                    $orderItem->getHiddenTaxCanceled() +
                    $orderItem->getHiddenTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
                );
            }

            if ($orderItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
                $this->addQtyToStock($orderItem->getProductId(), $qtyToCancel, $orderItem->getSku());
            }
        }
    }

    /**
     * Remove specific qty of order item from order
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param null $qtyToReturn
     * @return $this
     * @internal param Mage_Sales_Model_Order $order
     */
    protected function returnOrderItem(Mage_Sales_Model_Order_Item $orderItem, $qtyToReturn = null)
    {
        $delete = false;
        $qtyToStock = '';

        if (is_null($qtyToReturn)) {
            $qtyToReturn = $orderItem->getQtyToRefund() + $orderItem->getQtyToCancel();
            $qtyToStock = $orderItem->getQtyToRefund() + $orderItem->getQtyToCancel();
        }

        if ($qtyToReturn > 0) {

            if ($orderItem->getParentItem()) {
                $qtyToCancel = $qtyToReturn;
                $qtyToReturn -= $qtyToCancel;
            } else {
                $qtyToCancel = min($qtyToReturn, $orderItem->getQtyToCancel());
                $qtyToReturn -= $qtyToCancel;
            }

            $this->cancelOrderItem($orderItem, $qtyToCancel);
            if ($orderItem->getQtyOrdered() && $orderItem->getQtyOrdered() == $orderItem->getQtyCanceled()) {
                $delete = true;
            }
        }

        if ($qtyToReturn > 0 && $orderItem->getQtyToRefund() > 0) {
            $this->refundOrderItem($orderItem, $qtyToReturn);
        } elseif ($delete) {
            if ($orderItem->getChildrenItems()) {
                /** @var Mage_Sales_Model_Order_Item $childOrderItem */
                foreach ($orderItem->getChildrenItems() as $childOrderItem) {
                    Mage::getModel('sales/quote_item')->load($childOrderItem->getQuoteItemId())->delete();
                    $childOrderItem->delete();
                }
            }
            Mage::getModel('sales/quote_item')->load($orderItem->getQuoteItemId())->delete();
            $orderItem->delete();
        }

        /** Begin: Add qty to stock on product return */
        if ($qtyToStock) {
            $this->addQtyToStock($orderItem->getProductId(), $qtyToStock, $orderItem->getSku());
        }
        /** End: Add qty to stock on product return */

        return $this;
    }

    /**
     * Add qty to stock on product return
     *
     * @param $productId
     * @param $qty
     */
     protected function addQtyToStock($productId, $qty, $sku)
    {
        
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        $stockItem_new = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product->getId());

        $product = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($_product->getId());
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product[0]);

        $qtyAfter = $stockItem_new->getQty() + $qty;

        if ($qtyAfter <= 0) {
            $stockItem_new->setIsInStock(0);
            $stockItem_new->setQty(0);
        } else {
            $stockItem_new->setIsInStock(1);
            $stockItem_new->setQty($qtyAfter);
            $stockItem->setIsInStock(1);
        }
        $stockItem->save();
        $stockItem_new->save();

    }

    protected function postProcessItems()
    {
        $quote = $this->getQuote();
        $order = $this->getOrder();

        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {

            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = $order->getItemByQuoteItemId($quoteItem->getItemId());

            if (isset($orderItem) && (in_array($orderItem->getItemId(), $this->_savedOrderItems))) {
                continue;
            }

            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = $this->getConverter()->itemToOrderItem($quoteItem, $orderItem);
            $orderItem->setOrderId($order->getId());
            $orderItem->save();

            $quoteChildrens = $quoteItem->getChildren();
            $orderChildrens = array();
            foreach ($quoteChildrens as $childQuoteItem) {

                /** @var Mage_Sales_Model_Order_Item $childOrderItem */
                $childOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getItemId());

                if (isset($childOrderItem) && in_array($childOrderItem->getItemId(), $this->_savedOrderItems)) {
                    continue;
                }

                /** @var Mage_Sales_Model_Order_Item $childOrderItem */
                $childOrderItem = $this->getConverter()->itemToOrderItem($childQuoteItem, $childOrderItem);
                $childOrderItem->setOrderId($order->getId());
                $childOrderItem->setParentItem($orderItem);
                $childOrderItem->setParentItemId($orderItem->getId());
                $childOrderItem->save();
                $orderChildrens[] = $childOrderItem;
            }

            if (!empty($orderChildrens)) {
                foreach ($orderChildrens as $child) {
                    $orderItem->addChildItem($child);

                }
                $orderItem->save();
            }

            /** @var Mage_Sales_Model_Resource_Order_Item_Collection $orderItemsCollection */
            $orderItemsCollection = $order->getItemsCollection();
            if ($orderItemsCollection->getItemById($orderItem->getId())) {
                $orderItemsCollection->removeItemByKey($orderItem->getId());
            }
            $orderItemsCollection->addItem($orderItem);

            /*** Add new items to log ***/
            $changedItem = $quoteItem;
            $itemChange = array(
                'name'       => $changedItem->getName(),
                'qty_before' => 0,
                'qty_after'  => $changedItem->getQty()
            );
            $this->getLogModel()->addItemChange($changedItem->getId(), $itemChange);
        }

    }

    protected function removeQtyFromStock($productId, $qty,$productSku)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */

        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $productSku);
        $stockItem_new = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product->getId());

        $product = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($_product->getId());
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product[0]);
        $product = Mage::getModel('catalog/product')->load($product[0]);
        
        $qtyAfter = $stockItem_new->getQty() - $qty;
        if ($qtyAfter <= 0) {
            $stockItem_new->setIsInStock(0);
            $stockItem_new->setQty(0);
            $stockItem_new->save();
            $i = 0;
            $j = 0;
            $allProducts = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getid());
            foreach ($allProducts[0] as $productId) {
                $i = $i + 1;
                $products = Mage::getModel('catalog/product')->load($productId);
                $inStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($products)->getQty();
                if ($inStock <=0) {
                    $j = $j + 1;
                }
            }
        
        $z = $i - $j;
        if($z == 0){
            $stockItem->setIsInStock(0);
            $stockItem->save();
        }
        } else {
            $stockItem_new->setIsInStock(1);
            $stockItem_new->setQty($qtyAfter);
            $stockItem_new->save();
        }
    }

    protected function saveOldOrderItems()
    {
        $quote = $this->getQuote();
        $order = $this->getOrder();
        $log = $this->getLogModel();
        $helper = Mage::helper('mageworx_ordersedit/edit');
        $quoteItemsChanges = $this->getChanges('quote_items');
        if (empty($quoteItemsChanges)) {
            return $this;
        }
        foreach ($quoteItemsChanges as $itemId => $params) {

            /**
             * @var Mage_Sales_Model_Quote_Item $quoteItem
             * @var Mage_Sales_Model_Order_Item $orderItem
             * @var MageWorx_OrdersEdit_Model_Edit_Quote_Convert $converter
             * @var MageWorx_OrdersEdit_Model_Edit_Log $log
             * @var MageWorx_OrdersEdit_Helper_Edit $helper
             * @var Mage_Sales_Model_Quote_Item $childQuoteItem
             * @var Mage_Sales_Model_Order_Item $childOrderItem
             */
            $quoteItem  = $quote->getItemById($itemId);
            $orderItem  = $order->getItemByQuoteItemId($itemId);
            $converter  = $this->getConverter();

            if (!$orderItem || !$helper->checkOrderItemForCancelRefund($orderItem)) {
                continue;
            }

            $orderItemQty = $this->getQtyRest($orderItem);

            if (isset($params['qty']) && $params['qty'] < 0.001) {
                $params['action'] = 'remove';
            }

            if ((isset($params['action']) && $params['action'] == 'remove')) {
                /* Get Marketplace Product To be Changed */
                $cancledProductArray = $this->getCancledItem( $order, $orderItem );
                $this->removeOrderItem($order, $orderItem);
                if (!empty($params['super_attribute'])) {
                    $product = Mage::getModel("catalog/product")->load($orderItem->getProductId());
                    $childProduct = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($params['super_attribute'], $product);
                    if($childProduct->getSku() !== $orderItem->getSku() ){
                        $this->removeQtyFromStock($product->getId(),$params['qty'],$childProduct->getSku());
                        //sync item with marketplace (Humaira Batool 17/04/2017)
                       Mage::helper("marketplace/marketplace")->addOrderItem($orderItem,$params['qty'],$childProduct,$cancledProductArray);
                    };
                }
                $price = $this->getItemPricesConsideringTax($orderItem);
                $itemChange = array(
                    'name'         => $orderItem->getName(),
                    'qty_before'   => $orderItemQty,
                    'qty_after'    => '',
                    'price_before' => $price['before'],
                    'price_after'  => $price['after']
                );
                $log->addItemChange($orderItem->getId(), $itemChange);

                continue;
            }

            if (isset($params['qty']) && $params['qty'] != $orderItemQty) {

                $origQtyOrdered = $orderItem->getQtyOrdered();
                $orderItem = $converter->itemToOrderItem($quoteItem, $orderItem);

                /* Change main item qty */
                $this->changeItemQty($orderItem, $params['qty'], $origQtyOrdered);

                /* Change qty of child products if exists */
                if ($orderItem->getProductType() == 'bundle' || $orderItem->getProductType() == 'configurable') {
                    foreach ($quote->getAllItems() as $childQuoteItem) {
                        if ($childQuoteItem->getParentItemId() == $quoteItem->getId()) {

                            /* Recalculate totals of new order item */
                            $childQuoteItem->save();
                            $childOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getId());
                            $origChildQtyOrdered = $childOrderItem->getQtyOrdered();
                            $childOrderItem = $converter->itemToOrderItem($childQuoteItem, $childOrderItem);

                            /* Change child item qty and save */
                            $this->changeItemQty($childOrderItem, $params['qty'] * $childQuoteItem->getQty(), $origChildQtyOrdered);
                            $childOrderItem->save();
                            $this->_savedOrderItems[] = $childOrderItem->getItemId();

                        }
                    }
                }
            } elseif ($quoteItem->getPrice() != $orderItem->getPrice()) {
                $orderItem = $converter->itemToOrderItem($quoteItem, $orderItem);
            }

            if (isset($params['instruction'])) {
                $orderItem->setData('instruction', trim($params['instruction']));
            }

            $price = $this->getItemPricesConsideringTax($orderItem);
            $itemChange = array(
                'name'         => $orderItem->getName(),
                'qty_before'   => $orderItemQty,
                'qty_after'    => $quoteItem->getQty(),
                'price_before' => $price['before'],
                'price_after'  => $price['after']
            );

            /* Check Discount changes */
            if (isset($params['use_discount'])
                && $params['use_discount'] == 1
                && $quoteItem->getOrigData('discount_amount') == 0
                && $quoteItem->getData('discount_amount') > 0
            ) {
                $itemChange['discount'] = 1;
            } elseif ($quoteItem->getData('discount_amount') < 0.001 && $quoteItem->getOrigData('discount_amount') > 0) {
                $itemChange['discount'] = -1;
            }

            /* Add item changes to log */
            if ($itemChange['qty_before'] != $itemChange['qty_after']
                || $itemChange['price_before'] != $itemChange['price_after']
                || isset($itemChange['discount'])
            ) {
                $log->addItemChange($orderItem->getId(), $itemChange);
            }

            $quoteItem->save();
            $orderItem->save();

            $this->_savedOrderItems[] = $orderItem->getItemId();
        }

        return $this;
    }

    /**
     * Get Marketplace Item which is going to cancle/Remove
     *
     * @param Mage_Sales_Model_Order $order
     * @param null Mage_Sales_Model_Order_Item $orderItem
     * @return $product
     * @modifiedBy : Saroop Chand <saroop.chand@progos.org>
     * @task# ELABELZ-1931
     */

    public function getCancledItem(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Item $orderItem){
        if ($orderItem->getProductType() == 'bundle' || $orderItem->getProductType() == 'configurable') {
            /** @var Mage_Sales_Model_Order_Item $childOrderItem */
            $products = array();
            foreach ($orderItem->getChildrenItems() as $childOrderItem) {
                if ($childOrderItem->getStatusId() !== Mage_Sales_Model_Order_Item::STATUS_CANCELED) {
                    $order = $childOrderItem->getData();
                    $sku = $order['sku'];
                    $product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
                    $order_id = $order['order_id'];
                    $products = Mage::getModel('marketplace/commission')->getCollection()
                                ->addFieldToSelect('*')
                                ->addFieldToFilter('order_id',$order_id)
                                ->addFieldToFilter('product_id',$product_id);
                }
            }
            if( !empty( $products->getData() ) )
                return $products->getData();
            return $products;
        }
    }
}