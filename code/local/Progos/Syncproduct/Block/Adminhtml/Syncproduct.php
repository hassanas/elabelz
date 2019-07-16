<?php

class Progos_Syncproduct_Block_Adminhtml_Syncproduct extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_syncproduct';
        $this->_blockGroup = 'progos_syncproduct';
        $this->_headerText = Mage::helper('progos_syncproduct')->__('Sync SKU Manager');
        parent::__construct();
    }
}