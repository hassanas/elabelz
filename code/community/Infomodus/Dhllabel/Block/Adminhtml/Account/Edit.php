<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Account_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'dhllabel';
        $this->_controller = 'adminhtml_account';

        $this->_updateButton('save', 'account', Mage::helper('dhllabel')->__('Save Account'));
        $this->_updateButton('delete', 'account', Mage::helper('dhllabel')->__('Delete Account'));


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
        if (Mage::registry('account_data') && Mage::registry('account_data')->getId()) {
            return Mage::helper('dhllabel')->__("Edit Account '%s'", $this->htmlEscape(Mage::registry('account_data')->getCompanyname()));
        } else {
            return Mage::helper('dhllabel')->__('Add account');
        }
    }
}