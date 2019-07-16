<?php
class Progos_Ccache_Block_Adminhtml_Product extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_product';
        $this->_blockGroup = 'ccache';
        $this->_headerText = 'Warmup Product';
        parent::__construct();
        $this->_removeButton('add');
    }
}
