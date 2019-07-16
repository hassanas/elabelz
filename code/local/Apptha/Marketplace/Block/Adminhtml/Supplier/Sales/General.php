<?php
class Apptha_Marketplace_Block_Adminhtml_Supplier_Sales_General extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_supplier_sales_general';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('General Sales');
        parent::__construct();
        $this->_removeButton('add');
    }
}

