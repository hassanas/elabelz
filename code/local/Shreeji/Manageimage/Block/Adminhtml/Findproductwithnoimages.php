<?php
class Shreeji_Manageimage_Block_Adminhtml_Findproductwithnoimages extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_manageimage';
        $this->_blockGroup = 'manageimage';
        $this->_headerText = Mage::helper('manageimage')->__('Products With No Base Images');
        parent::__construct();
        $this->_removeButton('add');
    }

    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('manageimage/adminhtml_findproductwithnoimages_grid', 'findproductwithnoimages'));
    }
}