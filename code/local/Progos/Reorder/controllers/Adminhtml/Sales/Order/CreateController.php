<?php
/*
** @package: Progos_Reorder
** @description: Rewrite Sales Order Create controller reorderAction to stop the exception which comes due to product out of stock.
*/
require_once (Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'CreateController.php');

class Progos_Reorder_Adminhtml_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController {
    
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }
    
    public function reorderAction()
    {
        $this->_getSession()->clear();
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!Mage::helper('sales/reorder')->canReorder($order)) {
            return $this->_forward('noRoute');
        }

        if ($order->getId()) {
            $order->setReordered(true);
            $this->_getSession()->setUseOldShippingMethod(true);
            $this->_getOrderCreateModel()->initFromOrder($order);

            $this->_redirect('*/*');
        }
        else {
            $this->_redirect('*/sales_order/');
        }
    }
}