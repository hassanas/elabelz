<?php
/*
** @author: Sooraj Malhi <sooraj.malhi@progos.org>
** @package: Rewrite_CSaleOrderShipment  
** @description: Rewrite Shipment controller startAction to prevent shipment before invoice 
*/
require_once (Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'ShipmentController.php');

class Rewrite_CSaleOrderShipment_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController {

    public function startAction() {
        
        $orderId = $this->getRequest()->getParam("order_id");
        $order  = Mage::getModel('sales/order')->load($orderId);
        if(!$order->hasInvoices()) {
            Mage::getSingleton('adminhtml/session')->addError('Before shipment please create invoice of this order.');
            $this->_redirect('adminhtml/sales_order/view/', array('order_id'=>$orderId));
        } else {
            /**
             * Clear old values for shipment qty's
             */
            
            $this->_redirect('*/*/new', array('order_id'=>$this->getRequest()->getParam('order_id')));
        } 		
    }
}
