<?php
class VES_PdfProCustomVariables_Block_Adminhtml_Pdfprocustomvariables extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_pdfprocustomvariables';
    $this->_blockGroup = 'pdfprocustomvariables';
    $this->_headerText = Mage::helper('pdfprocustomvariables')->__('Variables Manager');
    $this->_addButtonLabel = Mage::helper('pdfprocustomvariables')->__('Add Variable');
    parent::__construct();
  }
}