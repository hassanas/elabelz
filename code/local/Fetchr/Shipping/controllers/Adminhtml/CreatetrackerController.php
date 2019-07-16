<?php

/**
 * Progos
 *
 * Order Items
 *
 * 
*/

class Fetchr_Shipping_Adminhtml_CreatetrackerController extends Mage_Adminhtml_Controller_action {

	public function createtrackingnoAction(){
       $order_id = Mage::app()->getRequest()->getParam('order_id');
       $order = Mage::getModel('sales/order')->load($order_id );
       $invoiceIds = $order->getInvoiceCollection()->getAllIds();
        if($invoiceIds):
            $shipment_id = Mage::getModel('fetchr_shipping/shipping')->orderShippment($order_id);
            $this->_redirect ( 'adminhtml/sales_order/view', array('order_id' => $order_id));
        else:
            $errorMsg = "Invoice of the order has not been created yet. Please first create invoice";
            Mage::getSingleton ( 'adminhtml/session' )->addError ( $errorMsg);
            $this->_redirect ( 'adminhtml/sales_order/view', array('order_id' => $order_id));
        endif;
    }
}