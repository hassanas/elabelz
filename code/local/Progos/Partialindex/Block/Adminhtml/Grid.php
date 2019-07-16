<?php
class Progos_Partialindex_Block_Adminhtml_Grid extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
     $this->_controller = 'adminhtml_partialindex';
     $this->_blockGroup = 'partialindex';
     $this->_headerText = Mage::helper('partialindex')->__('Product to partial reindex');
     $this->_addButtonLabel = Mage::helper('partialindex')->__('Add product');
     parent::__construct();
     }
}
