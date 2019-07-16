<?php
class Progos_Partialindex_Block_Adminhtml_Partialindex_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
     public function __construct()
     {
          parent::__construct();
          $this->setId('partialindex_tabs');
          $this->setDestElementId('edit_form');
          $this->setTitle(Mage::helper('partialindex')->__('Product Information'));
      }
      protected function _beforeToHtml()
      {
        $this->addTab('form_section', array(
                'label' => Mage::helper('partialindex')->__('Product Information'),
                'title' => Mage::helper('partialindex')->__('Product Information'),
                'content' => $this->getLayout()
                            ->createBlock('partialindex/adminhtml_partialindex_edit_tab_form')
                            ->toHtml()
        ));
         return parent::_beforeToHtml();
    }
}
