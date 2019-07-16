<?php

class Progos_Syncproduct_Block_Adminhtml_Syncproduct_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('syncproduct_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('progos_syncproduct')->__('SKU'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('progos_syncproduct')->__('SKU Information'),
          'title'     => Mage::helper('progos_syncproduct')->__('SKU Information'),
          'content'   => $this->getLayout()->createBlock('progos_syncproduct/adminhtml_syncproduct_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}