<?php

/**
 * Progos
 * Get Order Items
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Requestordercancel extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
    public function __construct() {
     
        $this->_controller = 'adminhtml_requestordercancel';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Order Cancel Requests');
        parent::__construct();
        $this->_removeButton('add');

    }
    
}