<?php

/**
 * This controller is created to repair the orders that have the configurable products issue
 * @category      Progos
 * @package       Progos_Emapi
 * @copyright     Progos TechCopyright (c) 07-12-2017
 * @author        Naveed Abbas
 */
class Progos_Emapi_Adminhtml_RepairOrderController extends Mage_Adminhtml_Controller_Action
{
    /*
     * For patch SUPEE-6285 mandatory for custom modules
     *
     * */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('marketplace/emapi');
    }

    /**
     * This function will repair orders
     *
     * @access public
     * @return void
     *
     */
    public function indexAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $reservedOrderId = $order->getIncrementId();
        $mdlEmapi = Mage::getModel('restmob/quote_index');
        $id = $mdlEmapi->getIdByRealOrderId($reservedOrderId);
        if($id) {
            $savedOrder = Mage::getModel('restmob/quote_index')->load($id);
            $cartItems = $savedOrder->getCartItems();
            $quoteId = $savedOrder->getQid();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            $storeId = $quote->getStoreId();
            if(!is_null($cartItems) && $cartItems != "") {
                /*
                 * Remove all existing items
                 */
                $items = $quote->getItemsCollection(false);
                foreach ($items as $item) {
                    //if ($item->getProductType() == "configurable") {
                    $item->delete();
                    //$item_id = $item->getItemId();
                        //$quote->removeItem($item_id);
                    //}
                }
                $quote->save();
                $orderItems = $order->getItemsCollection(false);
                foreach($orderItems as $orderItem) {
                    $orderItem->delete();
                }
                $order->save();
                //exit;
                $cartItems = json_decode($cartItems, true);
                foreach ($cartItems as $cartItem) {
                    $productId = (int)$cartItem['id'];
                    $qty = $cartItem['qty'];
                    //changing the stock for empty cart products
                    $this->updateProductStock($productId, $quoteId, $qty);
                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                    $mainProductId = $parentIds[0];
                    $product = $product = Mage::getModel('catalog/product')->load($mainProductId);
                    $attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                    $child = Mage::getModel('catalog/product')->load($productId);
                    $options = array();
                    $custom_options = array();
                    foreach ($attributes as $attribute) {
                        $custom_options[$attribute['attribute_id']] = $child->getData($attribute['attribute_code']);
                    }
                    ksort($custom_options);
                    $products = array(array(
                        'product_id' => $mainProductId,
                        'qty' => $qty,
                        'options' => null,
                        'super_attribute' => $custom_options,
                        'bundle_option' => null,
                        'bundle_option_qty' => null,
                        'links' => null
                    ));
                    $productMdl = Mage::getModel('emapi/product');
                    $productMdl->add($quoteId, $products, $storeId);
                }
                sleep(2);
                /**
                 * Sync quote items with order items
                 */
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $items = $quote->getItemsCollection(false);
                foreach ($items as $item) {
                    $order = Mage::getModel('sales/order')->load($orderId);
                    $orderItem = Mage::getModel('sales/convert_quote')->itemToOrderItem($item);
                    if ($item->getParentItem()) {
                        $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
                    }
                    $orderItem->setOrder($order)->save();
                }
                $order->save();
                $convertor = Mage::getSingleton('sales/convert_quote');
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $order = Mage::getModel('sales/order')->load($orderId);
                $quote->collectTotals();
                $convertor->toOrder($quote, $order);

                $order->setSubtotal($quote->getSubtotal())
                    ->setBaseSubtotal($quote->getBaseSubtotal())
                    ->setGrandTotal($quote->getGrandTotal())
                    ->setBaseGrandTotal($quote->getBaseGrandTotal());
                $order->save();

                Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrderById($orderId);
            }
            $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
        }
    }
    /**
     * This function will repair orders
     *
     * @access public
     * @return void
     *
     */
    public function updateProductStock($productId, $quoteId, $qty){
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        if ($stock->getQty() == 0 || $stock->getQty() < $qty) {
            $stock->setQty($qty);
        }
        if ($stock->getIsInStock() == 0) {
            $stock->setIsInStock(1);
        }
        $stock->save();
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        $storeId = $quote->getStoreId();
        Mage::getModel('catalog/product_status')->updateProductStatus($productId, $storeId, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
    }
}