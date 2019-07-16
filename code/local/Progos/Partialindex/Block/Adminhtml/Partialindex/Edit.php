<?php
class Progos_Partialindex_Block_Adminhtml_Partialindex_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
   public function __construct()
   {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'partialindex';
        $this->_controller = 'adminhtml_partialindex';
        $this->_updateButton('save', 'label',Mage::helper('partialindex')->__('Save product'));
        $this->_updateButton('delete', 'label', Mage::helper('partialindex')->__('Delete product'));
    }

    public function getHeaderText()
    {
        return Mage::helper('partialindex')->__('Manage product');
    }
}
