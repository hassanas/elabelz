<?php

class VES_AdvancedPdfProcessor_Block_Adminhtml_Template_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'advancedpdfprocessor';
        $this->_controller = 'adminhtml_template';
        
        $this->_updateButton('save', 'label', Mage::helper('advancedpdfprocessor')->__('Save Template'));
        $this->_updateButton('delete', 'label', Mage::helper('advancedpdfprocessor')->__('Delete Template'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);
		$vesCoreHelper = Mage::helper('ves_core');
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('template_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'template_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'template_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('template_data') && Mage::registry('template_data')->getId() ) {
            return Mage::helper('advancedpdfprocessor')->__("Edit Template '%s'", $this->htmlEscape(Mage::registry('template_data')->getName()));
        } else {
            return Mage::helper('advancedpdfprocessor')->__('Add Template');
        }
        $vesCoreHelper = Mage::helper('ves_core');
    }
}