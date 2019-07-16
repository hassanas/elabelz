<?php

class VES_AdvancedPdfProcessor_Block_Adminhtml_Template_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('template_form', array('legend'=>Mage::helper('advancedpdfprocessor')->__('Template information')));
      $vesCoreHelper = Mage::helper('ves_core');
      $fieldset->addField('name', 'text', array(
          'label'     => Mage::helper('advancedpdfprocessor')->__('Name'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'name',
      ));

      if(!$this->getRequest()->getParam('id')) {
	      	$fieldset->addField('template', 'file', array(
	          'label'     => Mage::helper('advancedpdfprocessor')->__('XML File'),
	      	  'class'	  => 'required-entry',
	      	  'required'  => true,
	          'name'      => 'template',
		  ));
      }
      if ( Mage::getSingleton('adminhtml/session')->getTemplateData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getTemplateData());
          Mage::getSingleton('adminhtml/session')->setTemplateData(null);
      } elseif ( Mage::registry('template_data') ) {
          $form->setValues(Mage::registry('template_data')->getData());
      }
      return parent::_prepareForm();
  }
}