<?php

class VES_AdvancedPdfProcessor_Block_Adminhtml_Template_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('template_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('advancedpdfprocessor')->__('Template Information'));
      $vesCoreHelper = Mage::helper('ves_core');
  }

  protected function _beforeToHtml()
  {
	  	$this->addTab('form', array(
	  			'label'     => Mage::helper('advancedpdfprocessor')->__('Template Information'),
	  			'title'     => Mage::helper('advancedpdfprocessor')->__('Template Information'),
	  			'content'   => $this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_template_edit_tab_form')->toHtml(),
	  	));
	  	$vesCoreHelper = Mage::helper('ves_core');
      	return parent::_beforeToHtml();
  }
}