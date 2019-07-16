<?php

/**
 * Progos
 * Get Order Items
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Unconfirmedfromseller extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
    public function __construct() {
     
        $this->_controller = 'adminhtml_unconfirmedfromseller';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Unconfirmed Order items from Seller side');
        parent::__construct();
        $this->_removeButton('add');

    }
    
}