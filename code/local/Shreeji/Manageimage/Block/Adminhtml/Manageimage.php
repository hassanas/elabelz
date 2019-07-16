<?php
class Shreeji_Manageimage_Block_Adminhtml_Manageimage extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_manageimage';
    $this->_blockGroup = 'manageimage';
    $this->_headerText = Mage::helper('manageimage')->__('Duplicate Image Manager');
    $this->_addButtonLabel = Mage::helper('manageimage')->__('Find Duplicate Image');
    parent::__construct();
  }
}