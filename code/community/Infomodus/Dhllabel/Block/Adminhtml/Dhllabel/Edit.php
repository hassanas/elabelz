<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Dhllabel_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'dhllabel';
        $this->_controller = 'adminhtml_dhllabel';
        
        $this->_updateButton('save', 'label', Mage::helper('dhllabel')->__('Save Item'));
        //$this->_updateButton('delete', 'label', Mage::helper('dhllabel')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('dhllabel_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'dhllabel_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'dhllabel_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('dhllabel_data') && Mage::registry('dhllabel_data')->getId() ) {
            return Mage::helper('dhllabel')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('dhllabel_data')->getTitle()));
        } else {
            //return Mage::helper('dhllabel')->__('Add Item');
        }
    }
}