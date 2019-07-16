<?php

/**
 * Progos
 *
 * Order Items
 *
 *
 */
class Apptha_Marketplace_Adminhtml_OrdersrefundhistoryController extends Mage_Adminhtml_Controller_Action
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
    public function confirm_sellerAction()
    {
        $id = $this->getRequest()->getParam('id');

        die();

        if ($id > 0) {
            try {
                $model = Mage::getModel('marketplace/commission')->load($id);
                $model->setRefundRequestSellerConfirmation('1')->save();
                $successMsg = Mage::helper('marketplace')->__('Refund order item request has been approved by seller, Now you can refund the order item.');
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
     *
     */
    public function confirm_buyerAction()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id > 0) {
            try {
                $model = Mage::getModel('marketplace/commission')->load($id);
                $model->setRefundRequestCustomer('1')->save();
                $successMsg = Mage::helper('marketplace')->__('Refund order item request recrived by customer.');
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