<?php

/**
 * Progos
 *
 *
 *
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemSellerConfirm extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $path = $this->getColumn()->getPath();

        $indexId = $row->getId();
        $productId = $row->getProductId();
        $orderId = $row->getOrderId();

        if ($path == "unconfirmed_from_seller") {
            $url_accept = $this->getUrl('*/adminhtml_orderitems/confirm_seller', array('id' => $indexId, 'path' => 'unconfirmed_from_seller'));
            $url_reject = $this->getUrl('*/adminhtml_orderitems/reject_seller', array('id' => $indexId, 'path' => 'unconfirmed_from_seller'));
        } elseif ($path == "all_orders") {
            $url_accept = $this->getUrl('*/adminhtml_orderitems/confirm_seller', array('id' => $indexId, 'product_id' => $productId, 'order_id' => $orderId, 'path' => 'all_orders'));
            $url_reject = $this->getUrl('*/adminhtml_orderitems/reject_seller', array('id' => $indexId, 'product_id' => $productId, 'order_id' => $orderId, 'path' => 'all_orders'));
        } else {
            $url_accept = $this->getUrl('*/*/confirm_seller', array('id' => $indexId));
            $url_reject = $this->getUrl('*/*/reject_seller', array('id' => $indexId));
        }

        $item = Mage::getModel('marketplace/commission')->load($indexId);

        if ($item->getIsSellerConfirmation() == "Yes") {
            $result = Mage::helper('marketplace')->__('Accepted');
        } elseif ($item->getIsSellerConfirmation() == "Rejected") {
            $result = Mage::helper('marketplace')->__('Rejected');
        } else {
            $orderId = $item->getOrderId();
            if ($orderId) {
                $itemCollection = Mage::getModel('marketplace/commission')
                                      ->getCollection()
                                      ->addFieldToFilter('order_id', $orderId);
                $productsNeedSellerConfirmation = [];
                $rejectedProducts = [];
                foreach ($itemCollection as $item) {
                    // Check all order items if they are confirmed.
                    if ($item->getIsSellerConfirmation() == 'No') {
                        $productsNeedSellerConfirmation[] = $item->getId();
                        // Or rejected, but we still can proceed with this order.
                    } elseif ($item->getIsSellerConfirmation() == 'Rejected') {
                        $rejectedProducts[] = $item->getId();
                    }
                }
                if (count($productsNeedSellerConfirmation) == 1 && $itemCollection->getSize() !== count($rejectedProducts)) {
                    $session = Mage::getSingleton('adminhtml/session');
                    if (is_null($session->getLastOrderItems())) {
                        $orderLastItems = [];
                        $orderLastItems[] = $productsNeedSellerConfirmation[0];
                        $session->setLastOrderItems($orderLastItems);
                    } else {
                        $orderLastItems = $session->getLastOrderItems();
                        if (!in_array($productsNeedSellerConfirmation[0], $orderLastItems)) {
                            $orderLastItems[] = $productsNeedSellerConfirmation[0];
                            $session->setLastOrderItems($orderLastItems);
                        }
                    }
                }
            }
            $orderLastItems = Mage::getSingleton('adminhtml/session')->getLastOrderItems();
            $result = "";
            if ($orderLastItems && in_array($value, $orderLastItems)) {
                $result .=
                    "<a onclick='
                    var r = confirm(\"After accepting this item invoice will be created if there are any accepted items.\");
                    if (r) { return confirm(\"Accepting this Item on behalf of Seller/Merchant?\"); } else { return false }' href='"
                    . $url_accept . "' title='" . Mage::helper('marketplace')->__('Click to Accept') . "'>"
                    . Mage::helper('marketplace')->__('Accept') . "</a>&nbsp;Or&nbsp;";
                $result .= "<a onclick='
                var r = confirm(\"After rejecting this item invoice will be created if there are any accepted items.\");
                if (r) { return confirm(\"Rejecting this Item on behalf of Seller/Merchant? Once the order is Rejected it cannot be reverted back\"); } else { return false }' href='"
                           . $url_reject . "' title='" . Mage::helper('marketplace')->__('Click to Reject') . "'>"
                           . Mage::helper('marketplace')->__('Reject') . "</a>";
            } else {
                $result .=
                    "<a onclick='javascript:return confirm(\"Accepting this Item on behalf of Seller/Merchant?\");' href='"
                    . $url_accept . "' title='" . Mage::helper('marketplace')->__('Click to Accept') . "'>"
                    . Mage::helper('marketplace')->__('Accept') . "</a>&nbsp;Or&nbsp;";
                $result .= "<a onclick='javascript:return confirm(\"Rejecting this Item on behalf of Seller/Merchant? Once the order is Rejected it cannot be reverted back\");' href='"
                           . $url_reject . "' title='" . Mage::helper('marketplace')->__('Click to Reject') . "'>"
                           . Mage::helper('marketplace')->__('Reject') . "</a>";
            }
        }

        return $result;
    }
}