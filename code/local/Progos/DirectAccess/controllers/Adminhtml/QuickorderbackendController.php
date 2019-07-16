<?php

class Progos_DirectAccess_Adminhtml_QuickorderbackendController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__("Quick Order Opener"));
        $this->renderLayout();
    }

    public function verifyOrderAction()
    {
        $orderIncrementId = $this->getRequest()->getParam("order_increment_id");
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $result = array();
        if ($order->getId()) {
            $result['redirectUrl'] = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $order->getId()));
            $result['status'] = true;
        } else {
            $result['status'] = false;
        }
        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}