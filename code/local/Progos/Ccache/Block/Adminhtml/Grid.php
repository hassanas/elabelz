<?php
class Progos_Ccache_Block_Adminhtml_Grid extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_ccache';
        $this->_blockGroup = 'ccache';
        $this->_headerText = Mage::helper('ccache')->getHeaderText();
        parent::__construct();
        $this->_removeButton('add');
    }
}
