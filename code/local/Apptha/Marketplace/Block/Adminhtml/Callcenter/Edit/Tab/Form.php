<?php

class Nabuns_Address_Block_Adminhtml_Addressbook_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('form_form', array('legend'=>Mage::helper('nabuns_address')->__('Item information')));
          
        $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('nabuns_address')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
        ));
          
        return parent::_prepareForm();
    }
}