<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Lists_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'dhllabel';
        $this->_controller = 'adminhtml_dhllabel';

        $this->_updateButton('save', 'dhllabel', Mage::helper('dhllabel')->__('Save label'));
        $this->_updateButton('delete', 'dhllabel', Mage::helper('dhllabel')->__('Delete label'));


        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        return Mage::helper('dhllabel')
            ->__("Edit label '%s'", $this->htmlEscape(Mage::registry('dhllabel_data')->getTitle()));
    }
}