<?php

class VES_PdfProCustomVariables_Block_Adminhtml_Pdfprocustomvariables_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('pdfprocustomvariables_form', array('legend'=>Mage::helper('pdfprocustomvariables')->__('Item information')));
     
      /*name*/
      $fieldset->addField('name', 'text', array(
          'label'     => Mage::helper('pdfprocustomvariables')->__('Variable Name'),
          'class'     => 'required-entry validate-code',
          'required'  => true,
          'name'      => 'name',
      ));
	  $disabled	= Mage::registry('pdfprocustomvariables_data')->getId()?true:false;
      /*variable type*/
      $fieldset->addField('variable_type', 'select', array(
          'label'     => Mage::helper('pdfprocustomvariables')->__('Type'),
          'required'  => true,
          'name'      => 'variable_type',
      	  'disabled'  => $disabled,
      	  'options'   => array(
                'attribute'    			=> Mage::helper('catalogrule')->__('Product Attribute'),
      			'customer'    			=> Mage::helper('catalogrule')->__('Customer Attribute'),
                /*'static'     			=> Mage::helper('catalogrule')->__('Static'),*/
            ),
      		'onchange' 	=> 'toggleActionsSelect(this.value)',
	  ));
		
      /*static value hidden*/
      $fieldset->addField('static_value', 'text', array(
      		'label'     => Mage::helper('pdfprocustomvariables')->__('Static Value'),
      		'required'  => false,
      		'name'      => 'static_value',
      ));
      	
      /*get all attribute to select*/
      $collection = Mage::getResourceModel('catalog/product_attribute_collection')
      ->addVisibleFilter();
      foreach($collection as $att) {
      	$attribute['value'] = $att->getAttributeId();
      	$attribute['label'] = $att->getFrontendLabel();
      	$attributes[] = $attribute;
      }
      
      /*attribute id*/
      $fieldset->addField('attribute_id', 'select', array(
      		'label'     => Mage::helper('pdfprocustomvariables')->__('Attributes'),
      		'required'  => true,
      		'name'      => 'attribute_id',
      		'values'    => $attributes
      ));
     
      /*customer attributes*/
  	  $collection_customer = Mage::getModel('customer/entity_attribute_collection')
      ->addVisibleFilter();
      foreach($collection_customer as $att) {
      	$attribute_customer['value'] = $att->getAttributeId();
      	$attribute_customer['label'] = $att->getFrontendLabel();
      	$attributes_customer[] = $attribute_customer;
      }
      
      $fieldset->addField('attribute_id_customer', 'select', array(
      		'label'     => Mage::helper('pdfprocustomvariables')->__('Attributes'),
      		'required'  => true,
      		'name'      => 'attribute_id_customer',
      		'values'    => $attributes_customer
      ));
      
      if ( Mage::getSingleton('adminhtml/session')->getPdfProCustomVariablesData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getPdfProCustomVariablesData());
          Mage::getSingleton('adminhtml/session')->setPdfProCustomVariablesData(null);
      } elseif ( Mage::registry('pdfprocustomvariables_data') ) {
          $form->setValues(Mage::registry('pdfprocustomvariables_data')->getData());
      }
      return parent::_prepareForm();
  }
}