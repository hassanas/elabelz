<?php

class Apptha_CustomMenu_Block_Adminhtml_Menu extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_custommenu';
        $this->_headerText = Mage::helper('core')->__('Generate Menu');
        parent::__construct();
        $this->_removeButton('add');
        $this->_addButton('build_menu', array(
            'label'     => Mage::helper('core')->__('Generate Menu'),
            'onclick'   => 'setLocation(\'' . $this->getBuildActionUrl() .'\')' ,
            'class'     => 'add',
        ));
    }
    protected function _prepareLayout()
    {
        
    }
    
    public function getBuildActionUrl()
    {
        return $this->getUrl('*/*/buildMenu');
    }
}
