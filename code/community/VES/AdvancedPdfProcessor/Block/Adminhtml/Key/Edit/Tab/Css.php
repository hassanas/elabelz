<?php

class VES_AdvancedPdfProcessor_Block_Adminhtml_Key_Edit_Tab_Css extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
  		  $templateId = Mage::registry('key_data')->getTemplateId();
  		  $sku = Mage::getModel('advancedpdfprocessor/template')->load($templateId)->getSku();
  		  
  	      $form = new Varien_Data_Form();
	      $this->setForm($form);
	      $fieldset = $form->addFieldset('apikey_form', array('legend'=>Mage::helper('pdfpro')->__('Additional CSS')));
	      $fieldset->addType('css_note', 'VES_AdvancedPdfProcessor_Block_Adminhtml_Key_Edit_Tab_Css_Note');
	      $vesHelper = Mage::helper('ves_core');
	      $fieldset->addField('css', 'textarea', array(
	          'label'     	=> Mage::helper('pdfpro')->__('Additional CSS'),
	      	  'style'		=> 'width: 700px; height: 300px;',
	          'required'  	=> false,
	          'name'      	=> 'css',
	      ));
	      
	      $fieldset->addField('css_note', 'css_note', array(
	      		'label'     => Mage::helper('pdfpro')->__(''),
	      		'name'      => 'css_note',
	      		'sku'		=> $sku,
	      ));
	      
	      if ( Mage::getSingleton('adminhtml/session')->getFormData() )
	      {
	          $form->setValues(Mage::getSingleton('adminhtml/session')->getFormData());
	          Mage::getSingleton('adminhtml/session')->setFormData(null);
	      } elseif ( Mage::registry('key_data') ) {
	          $form->setValues(Mage::registry('key_data')->getData());
	      }
	      return parent::_prepareForm();
  }
}