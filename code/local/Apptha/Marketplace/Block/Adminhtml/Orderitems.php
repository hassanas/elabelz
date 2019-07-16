<?php

/**
 * Progos
 * Get Order Items
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Orderitems extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
    public function __construct() {
     
        $this->_controller = 'adminhtml_orderitems';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Incomplete Order Items');
        parent::__construct();
        $this->_removeButton('add');

    }
    
}