<?php

require_once (Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'InvoiceController.php');

class Rewrite_CSaleOrderInvoice_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController {

    public function startAction() {
         // restrict the admin to create invoices if rejected order item not removed or not all order item confirmed from seller and buyer
        $order_id = $this->getRequest()->getParam("order_id");
        $order_sales = Mage::getModel("sales/order")->load($order_id);

        $items = Mage::getModel("marketplace/commission")->getCollection()
        ->addFieldToFilter("increment_id", $order_sales->getIncrementId())
        ->addFieldToFilter(array("is_buyer_confirmation","is_seller_confirmation"),
        array(array("eq"=>"No"),array("eq"=>"No")))
        ->addFieldToFilter(array("item_order_status"),
         array(array("neq"=>"canceled")));

        $items_seller_rejected = Mage::getModel("marketplace/commission")->getCollection()
        ->addFieldToFilter("increment_id", $order_sales->getIncrementId())
        ->addFieldToFilter(array("is_buyer_confirmation","is_seller_confirmation"),
         array(array("eq"=>"Rejected"),array("eq"=>"Rejected")))      
        ->addFieldToFilter(array("item_order_status"),
         array(array("neq"=>"canceled")));
        
        if ($items->getSize()) {
            
            $this->initInvoice = false;
            Mage::getSingleton('adminhtml/session')->addError('Cannot create invoice, confirm every order item from both customer and merchant.');
            $this->_redirect('adminhtml/sales_order/view/', array('order_id'=>$order_id));
        }else if ($items_seller_rejected->getSize()){
            $this->initInvoice = false;
            Mage::getSingleton('adminhtml/session')->addError('Cannot create invoice, every rejected order item must be removed via edit order.');
            $this->_redirect('adminhtml/sales_order/view/', array('order_id'=>$order_id));
        } else {
            /**
             * Clear old values for invoice qty's
             */
            $this->_getSession()->getInvoiceItemQtys(true);
            $this->_redirect('*/*/new', array('order_id'=>$this->getRequest()->getParam('order_id')));            
        }

    }

}
