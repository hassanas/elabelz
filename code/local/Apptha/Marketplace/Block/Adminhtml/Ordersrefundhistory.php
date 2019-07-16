<?php

/**
 * Progos
 * Get Order Items
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Ordersrefundhistory extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
    public function __construct() {
     
        $this->_controller = 'adminhtml_ordersrefundhistory';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Order Refund Requests');
        parent::__construct();
        $this->_removeButton('add');

    }
    
}