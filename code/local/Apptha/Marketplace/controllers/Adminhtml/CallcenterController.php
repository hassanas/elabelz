<?php

/**
 * Class Apptha_Marketplace_Adminhtml_CallcenterController
 */
class Apptha_Marketplace_Adminhtml_CallcenterController extends Mage_Adminhtml_Controller_Action
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

        $this->_addLeft($this->getLayout()->createBlock('marketplace/adminhtml_callcenter_edit_tabs'));

        $this->renderLayout();
    }

    /**
     *
     */
    public function morningAction()
    {
        $this->_initAction();
        $this->getLayout()->getBlock('callcenter.morning');
        $this->renderLayout();
    }

    /**
     *
     */
    public function afternoonAction()
    {
        $this->_initAction();
        $this->getLayout()->getBlock('callcenter.afternoon');
        $this->renderLayout();
    }

    /**
     *
     */
    public function eveningAction()
    {
        $this->_initAction();
        $this->getLayout()->getBlock('callcenter.evening');
        $this->renderLayout();
    }

    /**
     *
     */
    public function plusAction()
    {
        $this->_initAction();
        $this->getLayout()->getBlock('callcenter.plus');
        $this->renderLayout();
    }

    /**
     *
     */
    public function onholdAction()
    {
        $this->_initAction();
        $this->getLayout()->getBlock('callcenter.holded');
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
        $replaceItems = Mage::app()->getRequest()->getParam("replaceItems");

        echo $replaceItems;
        exit;

        $order = Mage::getModel("sales/order")->load($orderId);
        $preComments = unserialize($order->getCallLog());
        $preComments[] = [
            "timestamp" => $date,
            "comment" => $comment,
            "status" => (int)$callResponse
        ];

        $marketplace = Mage::getModel("marketplace/commission")->getCollection()
            ->addFieldToFilter("order_id", array("eq" => $orderId))
            ->addFieldToFilter("is_buyer_confirmation", array("eq" => "No"));

        if ($order->canHold() && $holdOrder == "1") {
            if (count($marketplace) && $callResponse != "1") {
                echo "cannot hold";
            } else {
                $holdComment = ", Order is ";
                $holdComment .= "holded";
                $order->hold();
            }
        } else {
            if ($order->canUnhold() && $holdOrder == "0") {
                $holdComment = ", Order is ";
                $holdComment .= "unholded";
                $order->unhold();
            }
        }
        $cr = $callResponse;
        if ($callResponse == "1") {
            $callResponse = "Call Response: <strong>Customer approved all items</strong>{$holdComment}";
            $marketplace = Mage::getModel("marketplace/commission")->getCollection()
                ->addFieldToFilter("order_id", array("eq" => $orderId));
            foreach ($marketplace as $m) {
                $m->setIsBuyerConfirmation("Yes");
                $m->setIsBuyerConfirmationDate($date);
                $m->setItemOrderStatus('pending_seller');
                $m->save();
            }
            $confirm = Mage::helper("marketplace/marketplace")->getProductConfirm($marketplace->getOrderId(), $marketplace->getSellerId());
            if ($confirm):
                if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
                    Mage::helper("marketplace/marketplace")->successAfter($marketplace->getOrderId());
                }
            endif;
        }

        if ($callResponse == "0") {
            $callResponse = "<strong>No answer from customer</strong>{$holdComment}";

            if (is_null($order->getSession())) {
                $next_session = Mage::helper('marketplace/marketplace')->getSession($order->getCreatedAt(), true);
            } else {
                if ($order->getSession() == "Morning") {
                    $next_session = "Afternoon";
                } elseif ($order->getSession() == "Afternoon") {
                    $next_session = "Evening";
                } else {
                    $next_session = "Morning";
                }
            }
            $order->setSession($next_session);
            $order->save();
        }

        if ($callResponse == "2") {
            $callResponse = "Call Response: <strong>Customer want changes</strong>{$holdComment}";
        }

        if ($callResponse == "3") {
            $callResponse = "Call Response: <strong>Wrong number</strong>{$holdComment}";
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
        $rejected = false;
        $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $id = Mage::app()->getRequest()->getParam("id");
        $value = Mage::app()->getRequest()->getParam("value");
        $marketplace = Mage::getModel("marketplace/commission")->load($id);
        $order = Mage::getModel("sales/order")->load($marketplace->getOrderId());

        if ($value == "Yes") {
            $comment = "<strong>Order is Confirmed</strong> via Call Center";
        } elseif ($value == "No") {
            $comment = "<strong>Order is set as Pending</strong> via Call Center";
        } else {
            $rejected = true;
            $comment = "<strong>Order is Rejected and deleted from Marketplace and Magento Orders</strong> via Call Center";
        }
        try {
            if ($rejected) {
                $marketplace->setIsBuyerConfirmation($value);
                $marketplace->setIsSellerConfirmation($value);
                $marketplace->setItemOrderStatus('rejected_customer');
                $marketplace->setIsBuyerConfirmationDate($date);
                $marketplace->setIsSellerConfirmationDate($date);
                $marketplace->save();

                $check_rejected = Mage::helper("marketplace/marketplace")->getProductReject($marketplace->getOrderId());
                Mage::getSingleton('marketplace/order')->deleteOrderItem($marketplace->getOrderId(), $marketplace->getProductId(), $check_rejected);
                $confirm = Mage::helper("marketplace/marketplace")->getProductConfirm($marketplace->getOrderId(), $marketplace->getSellerId());
                if ($confirm):
                    if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
                        Mage::helper("marketplace/marketplace")->successAfter($marketplace->getOrderId());
                    }
                endif;
            } else {
                $marketplace->setIsBuyerConfirmation($value);
                if ($value == "Yes") {
                    $confirm = Mage::helper("marketplace/marketplace")->getProductConfirm($marketplace->getOrderId(), $marketplace->getSellerId());
                    if ($confirm):
                        if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
                            Mage::helper("marketplace/marketplace")->successAfter($marketplace->getOrderId());
                        }
                    endif;
                    $marketplace->setIsBuyerConfirmationDate($date);
                    $marketplace->setItemOrderStatus('pending_seller');
                } else {
                    $marketplace->setIsBuyerConfirmationDate("0000-00-00 00:00:00");
                }
                $marketplace->save();
            }

            $order->addStatusHistoryComment($comment, $order->getStatus())
                ->setIsVisibleOnFront(0)
                ->setIsCustomerNotified(0);
            $order->save();
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