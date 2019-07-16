<?php
class Progos_Sizeguide_Block_Adminhtml_Sizeguide extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{	
		// The blockGroup must match the first half of how we call the block, and controller matches the second half
        // ie. contactform/adminhtml_contactform
	
		$this->_blockGroup = 'sizeguide';
		$this->_controller = 'adminhtml_sizeguide';
		$this->_headerText = Mage::helper('sizeguide')->__('Size Guide Manager');
		$this->_addButtonLabel = Mage::helper('sizeguide')->__('Add Size Guide');
		parent::__construct();

	}
}