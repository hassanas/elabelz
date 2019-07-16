<?php

/**
 * Progos
 * Get Order Items
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Orderitemscancel extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
    public function __construct() {
     
        $this->_controller = 'adminhtml_orderitemscancel';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Cancelled Order Items');
        parent::__construct();
        $this->_removeButton('add');

    }
    
}