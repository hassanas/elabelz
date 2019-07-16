<?php

class Progos_Syncproduct_Block_Adminhtml_Syncproduct_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'progos_syncproduct';
        $this->_controller = 'adminhtml_syncproduct';
        
        $this->_updateButton('save', 'label', Mage::helper('progos_syncproduct')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('progos_syncproduct')->__('Delete'));

        $this->_removeButton('saveandcontinue');
    }

    public function getHeaderText()
    {
        if( Mage::registry('syncproduct_data') && Mage::registry('syncproduct_data')->getId() ) {
            return Mage::helper('progos_syncproduct')->__("Edit SKU '%s'", $this->htmlEscape(Mage::registry('syncproduct_data')->getSku()));
        } else {
            return Mage::helper('progos_syncproduct')->__('Add');
        }
    }
}