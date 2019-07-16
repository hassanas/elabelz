<?php
class Progos_CustomOrderFlags_Block_Adminhtml_Aramexlabel_Lists extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_aramexlabel_lists';
        $this->_blockGroup = 'customorderflags';
        $this->_headerText = Mage::helper('customorderflags')->__('Aramex Shipping Labels');
        parent::__construct();
        $this->_removeButton('add');
    }
}