<?php

/**
 * Progos
 *
 * Order Items
 *
 *
 */
class Apptha_Marketplace_Adminhtml_RequestordercancelController extends Mage_Adminhtml_Controller_Action
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

    public function cancelAction()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id > 0) {
            try {
                $model = Mage::getModel('marketplace/commission')->load($id);
                $model->setItemOrderStatus("canceled")->save();
                $successMsg = Mage::helper('marketplace')->__('Order Item status has been successfully update on Marketplace, Now you cancel the order from Magento.');

                Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);

                $this->_redirect('adminhtml/sales_order/view/order_id/' . $model->getOrderId());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                $this->_redirect('*/*/');
            }
        } else {
            $this->_redirect('*/*/');
        }
    }

    public function canceloldAction()
    {
        $id = $this->getRequest()->getParam('id');
        $product = Mage::getModel('marketplace/commission')->load($id);

        $orderId = $product->getOrderId();
        $produtId = $product->getProductId();

        if ($orderId) {
            try {
                $product->setItemOrderStatus("canceled");
                $product->save();

                $OrderItemsSeller = Mage::getModel('marketplace/commission')->getCollection();
                $OrderItemsSeller->addFieldToSelect('*');
                $OrderItemsSeller->addFieldToFilter('id', $product->getId());
                $OrderItemsSellerTotal = $OrderItemsSeller->getSize();
                $cnt = 0;
                foreach ($OrderItemsSeller as $item) {
                    if ($item->getItemOrderStatus() == "canceled") {
                        $cnt++;
                    }
                }

                if ($OrderItemsSellerTotal == $cnt) {
                    $product->setOrderStatus("canceled");
                    $product->save();
                }

                $_order = Mage::getModel('sales/order')->load($orderId);
                $allMageOrders = count($_order->getAllVisibleItems());

                // $_order->getAllItems();
                foreach ($_order->getAllVisibleItems() as $item) {
                    if ($produtId == $item->getProductId()) {
                        $item->setQtyCanceled($item->getQtyOrdered());
                        $item->save();
                    }
                }

                $cnt = 0;
                foreach ($_order->getAllVisibleItems() as $item) {
                    if ($item->getQtyCanceled()) {
                        $cnt++;
                    }
                }

                if ($allMageOrders == $cnt) {
                    $_order->setStatus("canceled");
                    $_order->setState("canceled");
                    $_order->save();
                }

                /**
                 * Redirect to order view page
                 */
                Mage::getSingleton('adminhtml/session')->addSuccess('Order has been cancelled successfully');

                $this->_redirect('*/*/');

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                $this->_redirect('*/*/');
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong!');

            $this->_redirect('*/*/');

            return false;
        }
    }

    public function confirm_sellerAction()
    {
        $id = $this->getRequest()->getParam('id');

        die();

        if ($id > 0) {
            try {
                $model = Mage::getModel('marketplace/commission')->load($id);
                $model->setCancelRequestSellerConfirmation('1')->save();
                $successMsg = Mage::helper('marketplace')->__('Cancel order request has been approved by seller, Now you can cancel the order.');
                Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
            }
        } else {
            $this->_redirect('*/*/');
        }
    }

    public function confirm_buyerAction()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id > 0) {
            try {
                $model = Mage::getModel('marketplace/commission')->load($id);
                $model->setCancelRequestCustomer('1')->save();
                $successMsg = Mage::helper('marketplace')->__('Order item has been cancel by customer.');
                Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
            }
        } else {
            $this->_redirect('*/*/');
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