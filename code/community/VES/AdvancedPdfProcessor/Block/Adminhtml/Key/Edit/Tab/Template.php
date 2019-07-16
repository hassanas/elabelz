<?php

class VES_AdvancedPdfProcessor_Block_Adminhtml_Key_Edit_Tab_Template extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareLayout() {
		parent::_prepareLayout();
	}
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $vesHelper = Mage::helper('ves_core');
      $fieldset = $form->addFieldset('template', array('legend'=>Mage::helper('pdfpro')->__('Template information')));

      $fieldset->addField('template_id', 'select', array(
    			'label'     => Mage::helper('advancedpdfprocessor')->__('Base PDF Template'),
    			'required'  => false,
    			'name'      => 'template_id',
    			'values'	=> Mage::getModel('advancedpdfprocessor/source_template')->toOptionArray(),
      			'note'		=> Mage::helper('advancedpdfprocessor')->__('Templates from EasyPDF -> Manage PDF Template'),
    	));

	  $fieldset->addField('rtl', 'select', array(
		  'label'     => Mage::helper('advancedpdfprocessor')->__('RTL support'),
		  'required'  => false,
		  'name'      => 'rtl',
		  'values'	=> Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
	  ));
    	
      
      //load config from default
      $config = Mage::getSingleton('cms/wysiwyg_config')->getConfig(
      		array('tab_id' => 'form_section'));
     // $config['add_images'] = '0';		//show image button
      
      /*Attach CSS file from selected template*/
      $config['content_css']	= $this->getSkinUrl('ves_pdfpros/default.css');
      if($apiKey = Mage::registry('key_data')){
      	$template = Mage::getModel('advancedpdfprocessor/template')->load($apiKey->getTemplateId());
      	//$config['content_css']	.= ','.$this->getSkinUrl($template->getData('css_path'));
      	
      	/*Body class*/
      	$config['body_class']	= $template->getSku(); 
      }
      $config["widget_window_url"] = Mage::getSingleton('adminhtml/url')->getUrl('advancedpdfprocessor/adminhtml_widget/index');
      $plugins = $config->getData('plugins');
      $plugins[0]["options"]["url"] = '';
      $plugins[0]["options"]["onclick"]["subject"] = "MagentovariablePlugin.loadChooser('{{html_id}}');";
      
      /*Add Easy PDF plugin*/
      $plugins[] = array(
      	'name'		=> 'easypdf',
      	'src'		=> $this->getJsUrl('ves_advancedpdfprocessor/tiny_mce/plugins/easypdf/editor_plugin.js'),
      	'options'	=> array('logo_url'=>$this->getJsUrl('ves_advancedpdfprocessor/tiny_mce/plugins/easypdf/images/logo_bg.gif')),
      );
      
      $plugins = $config->setData('plugins' , $plugins);
      $fieldset1 = $form->addFieldset('order_template-fieldset', array('legend'=>Mage::helper('pdfpro')->__('Order PDF Template')));
      $fieldset1->addType('ves_editor','VES_AdvancedPdfProcessor_Block_Form_Element_Editor');
      
      $orderField = $fieldset1->addField('order_template', 'ves_editor', array(
          	'label'     => Mage::helper('pdfpro')->__('Order template'),
          	'class'     => 'required-entry',
      		'style'		=> 'width: 900px; height: 200px;',
          	'required'  => true,
          	'name'      => 'order_template',
      		'config'    => $config,
      		'wysiwyg'   => true,
      ));
	  $fieldset2 = $form->addFieldset('invoice_template-fieldset', array('legend'=>Mage::helper('pdfpro')->__('Invoice PDF Template')));
      $fieldset2->addType('ves_editor','VES_AdvancedPdfProcessor_Block_Form_Element_Editor');
      $invoiceField = $fieldset2->addField('invoice_template', 'ves_editor', array(
      		'label'     => Mage::helper('pdfpro')->__('Invoice template'),
      		'class'     => 'required-entry',
      		'style'		=> 'width: 900px; height: 500px;',
      		'required'  => true,
      		'name'      => 'invoice_template',
      		'config'    => $config,
      		'wysiwyg'   => true,
      ));
      $fieldset3 = $form->addFieldset('shipment_template-fieldset', array('legend'=>Mage::helper('pdfpro')->__('Shipment PDF Template')));
      $fieldset3->addType('ves_editor','VES_AdvancedPdfProcessor_Block_Form_Element_Editor');
      $shipmentField = $fieldset3->addField('shipment_template', 'ves_editor', array(
      		'label'     => Mage::helper('pdfpro')->__('Shipment template'),
      		'class'     => 'required-entry',
      		'style'		=> 'width: 900px; height: 500px;',
      		'required'  => true,
      		'name'      => 'shipment_template',
      		'config'    => $config,
      		'wysiwyg'   => true,
      ));
      $fieldset4 = $form->addFieldset('creditmemo_template-fieldset', array('legend'=>Mage::helper('pdfpro')->__('Creditmemo PDF Template')));
      $fieldset4->addType('ves_editor','VES_AdvancedPdfProcessor_Block_Form_Element_Editor');
     $creditMemoField = $fieldset4->addField('creditmemo_template', 'ves_editor', array(
      		'label'     => Mage::helper('pdfpro')->__('Credit memo template'),
      		'class'     => 'required-entry',
      		'style'		=> 'width: 900px; height: 500px;',
      		'required'  => true,
      		'name'      => 'creditmemo_template',
      		'config'    => $config,
      		'wysiwyg'   => true,
      ));
	  
	  //add custom template field
	  $customPrintouts = Mage::getStoreConfig('pdfpro/custom');
	  $i = 1;
	foreach($customPrintouts as $printout=>$title){
		$fieldset5 = $form->addFieldset('custom'.$i.'_template-fieldset', array('legend'=>Mage::helper('pdfpro')->__('Custom PDF Template %s',$i)));
		$fieldset5->addType('ves_editor','VES_AdvancedPdfProcessor_Block_Form_Element_Editor');
		$creditMemoField = $fieldset5->addField('custom'.$i.'_template', 'ves_editor', array(
			'label'     => Mage::helper('pdfpro')->__('Custom PDF Template %s',$i),
			'class'     => 'required-entry',
			'style'		=> 'width: 900px; height: 500px;',
			'required'  => true,
			'name'      => 'custom'.$i.'_template',
			'config'    => $config,
			'wysiwyg'   => true,
		));
		$i ++;
	}
	
	
      $vesHelper = Mage::helper('ves_core');
      // Setting custom renderer for content field to remove label column
      $renderer = $this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset_element')
                    ->setTemplate('ves_advancedpdfprocessor/form/renderer/content.phtml');
      $orderField->setRenderer($renderer);
      $invoiceField->setRenderer($renderer);
      $shipmentField->setRenderer($renderer);
      $creditMemoField->setRenderer($renderer);
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