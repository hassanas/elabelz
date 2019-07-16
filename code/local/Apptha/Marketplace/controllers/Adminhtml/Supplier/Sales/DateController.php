<?php

/**
 * Class Apptha_Marketplace_Adminhtml_Supplier_Sales_DateController
 */
class Apptha_Marketplace_Adminhtml_Supplier_Sales_DateController extends Mage_Adminhtml_Controller_Action
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
     *
     */
    public function commentsAction()
    {
        $this->loadLayout()->renderLayout();
    }

    /**
     *
     */
    public function saveAction()
    {
        $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $orderId = Mage::app()->getRequest()->getParam("orderId");
        $comment = Mage::app()->getRequest()->getParam("comment");
        $holdOrder = Mage::app()->getRequest()->getParam("holdOrder");
        $callResponse = Mage::app()->getRequest()->getParam("callResponse");

        $order = Mage::getModel("sales/order")->load($orderId);
        $preComments = unserialize($order->getCallLog());
        $preComments[] = [
            "timestamp" => $date,
            "comment" => $comment,
            "status" => (int)$callResponse
        ];

        if ($order->canHold() && $holdOrder == "1") {
            $holdComment = " and Order is ";
            $holdComment .= "holded";
            $order->hold();
        } else {
            if ($order->canUnhold() && $holdOrder == "0") {
                $holdComment = " and Order is ";
                $holdComment .= "unholded";
                $order->unhold();
            }
        }

        if ($callResponse == "1") {
            $callResponse = "<strong>Call connected{$holdComment}</strong>";
        } else {
            $callResponse = "<strong>Call failed {$holdComment}</strong>";
        }

        if (strlen(trim($comment))) {
            $comment = $callResponse . ", {$comment} via Call Center.";
        } else {
            $comment = $callResponse . " via Call Center.";
        }

        $order->addStatusHistoryComment($comment, $order->getStatus())
            ->setIsVisibleOnFront(0)
            ->setIsCustomerNotified(0);
        $order->save();

        $order->setCallLog(serialize($preComments));
        $order->save();
    }

    /**
     *
     */
    public function statusAction()
    {
        $id = Mage::app()->getRequest()->getParam("id");
        $value = Mage::app()->getRequest()->getParam("value");
        $marketplace = Mage::getModel("marketplace/commission")->load($id);
        $order = Mage::getModel("sales/order")->load($marketplace->getOrderId());
        if ($value == "Yes") {
            $comment = "<strong>Order is Confirmed</strong> via Call Center";
        } elseif ($value == "No") {
            $comment = "<strong>Order is set as Pending</strong> via Call Center";
        } else {
            $comment = "<strong>Order is Rejected</strong> via Call Center";
        }
        try {
            $order->addStatusHistoryComment($comment, $order->getStatus())
                ->setIsVisibleOnFront(0)
                ->setIsCustomerNotified(0);
            $order->save();

            $marketplace->setIsBuyerConfirmation($value);
            $marketplace->save();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
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