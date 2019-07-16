<?php
class Progos_Ccache_Block_Adminhtml_Manufacturer extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_manufacturer';
        $this->_blockGroup = 'ccache';
        $this->_headerText = 'Warmup Manufacturer';
        parent::__construct();
        $this->_removeButton('add');
    }
}
