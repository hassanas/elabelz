<?php

class VES_PdfProCustomVariables_Block_Adminhtml_Pdfprocustomvariables_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('pdfprocustomvariables_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('pdfprocustomvariables')->__('Variables Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('pdfprocustomvariables')->__('Variable Information'),
          'title'     => Mage::helper('pdfprocustomvariables')->__('Variable Information'),
          'content'   => $this->getLayout()->createBlock('pdfprocustomvariables/adminhtml_pdfprocustomvariables_edit_tab_form')->toHtml(),
      ));
     
      $this->addTab('itergration_section', array(
      		'label'     => Mage::helper('pdfprocustomvariables')->__('Implementation Code'),
      		'title'     => Mage::helper('pdfprocustomvariables')->__('Implementation Code'),
      		'content'   => $this->getLayout()->createBlock('pdfprocustomvariables/adminhtml_pdfprocustomvariables_edit_tab_intergration')->toHtml(),
      ));
      return parent::_beforeToHtml();
  }
}