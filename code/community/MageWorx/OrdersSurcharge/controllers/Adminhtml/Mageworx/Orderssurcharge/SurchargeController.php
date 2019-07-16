<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Adminhtml_Mageworx_Orderssurcharge_SurchargeController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Action for surcharges grid
     */
    public function indexAction()
    {
        $helper = $this->getHelper();
        $this->loadLayout();
        $this->_setActiveMenu('sales');
        $this->_title($helper->__('Order Surcharge'));
        $this->renderLayout();
    }

    /**
     * Surcharge grid (for ajax update)
     */
    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * View surcharge details
     */
    public function viewAction()
    {
        $helper = $this->getHelper();
        $this->_initSurcharge();
        $this->loadLayout();
        $this->_setActiveMenu('sales');
        $this->_title($helper->__('Order Surcharge'));
        $this->renderLayout();
    }

    //////////////////////////////////////////////////// Mass Actions //////////////////////////////////////////////////

    public function massRestoreAction()
    {
        $helper = $this->getHelper();

        if (!$helper->isAllowed('sales/mageworx_orderssurcharge/actions/restore')) {
            $this->_getSession()->addError($helper->__('Permission denied'));
            $this->_redirect('*/*');
            return;
        }

        $surchargeIds = $this->getRequest()->getPost('surcharge_ids', array());

        if (empty($surchargeIds)) {
            $this->_getSession()->addError($helper->__('Empty Surcharge(s)'));
            $this->_redirect('*/*');
            return;
        }

        Mage::getModel('mageworx_orderssurcharge/surcharge')->restoreByIds($surchargeIds);
        $message = $helper->__('Surcharges %s was successfully restored', implode(', ', $surchargeIds));
        $this->_getSession()->addSuccess($message);
        $this->_redirect('*/*');
    }

    public function massDeleteAction()
    {
        $helper = $this->getHelper();

        if (!$helper->isAllowed('sales/mageworx_orderssurcharge/actions/delete')) {
            $this->_getSession()->addError($helper->__('Permission denied'));
            $this->_redirect('*/*');
            return;
        }

        $surchargeIds = $this->getRequest()->getPost('surcharge_ids', array());

        if (empty($surchargeIds)) {
            $this->_getSession()->addError($helper->__('Empty Surcharge(s)'));
            $this->_redirect('*/*');
            return;
        }

        Mage::getModel('mageworx_orderssurcharge/surcharge')->deleteByIds($surchargeIds);
        $message = $helper->__('Surcharges %s was successfully deleted', implode(', ', $surchargeIds));
        $this->_getSession()->addSuccess($message);
        $this->_redirect('*/*');
    }

    public function removeAction()
    {
        $helper = $this->getHelper();
        $response = array();

        if (!$helper->isAllowed('sales/mageworx_orderssurcharge/actions/delete')) {
            $response['error'] = 1;
            $response['message'] = $helper->__('Permission denied');
            $jsonData = Zend_Json::encode($response);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($jsonData);

            return;
        }

        $surchargeId = $this->getRequest()->getPost('surcharge_id', array());

        if (!$surchargeId) {
            $response['error'] = 1;
            $response['message'] = $helper->__('Empty Surcharge');
            $jsonData = Zend_Json::encode($response);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($jsonData);

            return;
        }

        $surcharge = $this->_initSurcharge();
        if (!$surcharge || !$surcharge->getId()) {
            $response['error'] = 1;
            $response['message'] = $helper->__('Surcharge does not exist');
            $jsonData = Zend_Json::encode($response);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($jsonData);

            return;
        }

        $surcharge->delete();

        $order = $this->_initOrder();
        if (!$order || !$order->getId()) {
            $response['error'] = 1;
            $response['message'] = $helper->__('Order does not exist');
            $jsonData = Zend_Json::encode($response);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($jsonData);

            return;
        }

        $logger = $helper->getLogger();
        $text = $helper->__('The payment link with ID #%s was canceled.', $surchargeId);
        $logger->log($text, $order, 1);

        $this->loadLayout('empty');
        $this->renderLayout();
    }

    /**
     * Initialize surcharge model instance
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge|false
     */
    protected function _initSurcharge()
    {
        $id = $this->getRequest()->getParam('surcharge_id');
        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->load($id);

        if (!$surcharge->getId()) {
            if (!$this->getRequest()->getParam('is_ajax')) {
                $this->_getSession()->addError($this->__('This surcharge no longer exists.'));
                $this->_redirect('*/*/');
                $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            }

            return false;
        }

        Mage::register('surcharge', $surcharge);
        Mage::register('current_surcharge', $surcharge);

        return $surcharge;
    }

    /**
     * Initialize order model instance
     *
     * @return Mage_Sales_Model_Order || false
     */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            if (!$this->getRequest()->getParam('is_ajax')) {
                $this->_getSession()->addError($this->__('This order no longer exists.'));
                $this->_redirect('*/*/');
                $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            }

            return false;
        }

        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);

        return $order;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/mageworx_orderssurcharge/view');
    }

    /**
     * Get module helper
     *
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}
