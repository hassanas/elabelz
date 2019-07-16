<?php
class VES_AdvancedPdfProcessor_Block_Adminhtml_Template extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_template';
    $this->_blockGroup = 'advancedpdfprocessor';
    $this->_headerText = Mage::helper('advancedpdfprocessor')->__('Template Manager');
    $this->_addButtonLabel = Mage::helper('advancedpdfprocessor')->__('Add Template');
    Mage::helper('ves_core');
    parent::__construct();
  }
}