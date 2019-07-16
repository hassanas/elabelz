<?php
class Progos_Ccache_Block_Adminhtml_Category extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_category';
        $this->_blockGroup = 'ccache';
        $this->_headerText = 'Warmup Category';
        parent::__construct();
        $this->_removeButton('add');
    }
}
