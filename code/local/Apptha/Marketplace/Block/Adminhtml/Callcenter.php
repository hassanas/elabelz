<?php
class Apptha_Marketplace_Block_Adminhtml_Callcenter extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_callcenter';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Call Center');
        parent::__construct();
        $this->_removeButton('add');
    }
}

