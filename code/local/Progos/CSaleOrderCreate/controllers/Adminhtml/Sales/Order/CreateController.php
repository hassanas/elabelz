<?php
/*
** @package: Progos_CSaleOrderCreate  
** @description: Rewrite Sales Order Create controller reorderAction to stop the exception which comes due to product out of stock.
*/
require_once (Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'CreateController.php');

class Progos_CSaleOrderCreate_Adminhtml_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController {
    public function reorderAction(){
        $this->_getSession()->clear();
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!Mage::helper('sales/reorder')->canReorder($order)) {
            return $this->_forward('noRoute');
        }

        if ($order->getId()) {
            try{
                $order->setReordered(true);
                $this->_getSession()->setUseOldShippingMethod(true);
                $this->_getOrderCreateModel()->initFromOrder($order);
                $this->_redirect('*/*');
            }catch(Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/sales_order/view/', array('order_id' => $order->getId()));
            }
        }else {
            $this->_redirect('*/sales_order/');
        }
    }
}