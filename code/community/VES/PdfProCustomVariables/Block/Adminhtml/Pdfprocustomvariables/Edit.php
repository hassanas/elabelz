<?php

class VES_PdfProCustomVariables_Block_Adminhtml_Pdfprocustomvariables_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'pdfprocustomvariables';
        $this->_controller = 'adminhtml_pdfprocustomvariables';
        
        $this->_updateButton('save', 'label', Mage::helper('pdfprocustomvariables')->__('Save Variable'));
        $this->_updateButton('delete', 'label', Mage::helper('pdfprocustomvariables')->__('Delete Variable'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('pdfprocustomvariables_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'pdfprocustomvariables_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'pdfprocustomvariables_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        		
        	function toggleActionsSelect(action) {
        		if(action == 'attribute') {
        			$('attribute_id').parentNode.parentNode.show();
        			$('static_value').parentNode.parentNode.hide();
        			$('attribute_id_customer').parentNode.parentNode.hide();
        		}else if(action == 'static') {
			        $('static_value').parentNode.parentNode.show();
        			$('attribute_id').parentNode.parentNode.hide();
        			$('attribute_id_customer').parentNode.parentNode.hide();
				}else if(action == 'customer') {
        			$('static_value').parentNode.parentNode.hide();
        			$('attribute_id').parentNode.parentNode.hide();
        			$('attribute_id_customer').parentNode.parentNode.show();
    			}	
        	}
        		
        		toggleActionsSelect($('variable_type').value);
        ";
      //  $this->_formInitScripts [] = "toggleActionsSelect($('type').value)";
    }

    public function getHeaderText()
    {
        if( Mage::registry('pdfprocustomvariables_data') && Mage::registry('pdfprocustomvariables_data')->getId() ) {
            return Mage::helper('pdfprocustomvariables')->__("Edit Variable '%s'", $this->htmlEscape(Mage::registry('pdfprocustomvariables_data')->getName()));
        } else {
            return Mage::helper('pdfprocustomvariables')->__('Add Variable');
        }
    }
}