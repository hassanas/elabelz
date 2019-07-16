<?php

/**
 * Progos
 * Get Order Items
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Unconfirmedfrombuyer extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
    public function __construct() {
     
        $this->_controller = 'adminhtml_unconfirmedfrombuyer';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Unconfirmed Order items from Customer side');
        parent::__construct();
        $this->_removeButton('add');

    }
    
}