<?php

class Rewrite_CSaleOrder_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function __construct() {
        parent::__construct();
        // $this->_removeButton("order_cancel");
        // $this->_removeButton("back");
        $this->_removeButton("order_invoice");
        $this->_removeButton("order_ship");
        $this->_removeButton("print_aramex_label");
        $this->_removeButton("create_aramex_shipment");
        $this->_removeButton("send_notification");
		// if(!Mage::getStoreConfig('pdfpro/config/enabled') || !Mage::getStoreConfig('pdfpro/config/admin_print_order')) return;
		// $this->addButton('pdfpro_print', array(
  //               // 'label'     => 'Easy PDF - '.Mage::helper('sales')->__('Print Order'),
  //               'label'     => Mage::helper('sales')->__('Destination/COD Invoice'),
  //               'class'     => 'save',
  //               'onclick'   => 'setLocation(\''.$this->getPrintUrl().'\')'
  //               )
  //       );
        $this->_removeButton("pdfpro_print");
    }


}
