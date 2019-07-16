<?php
class VES_AdvancedPdfProcessor_Block_Adminhtml_Grid_Renderer_Template extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Store
{
    public function render(Varien_Object $row)
    {
    	$vesHelper = Mage::helper('ves_core');
    	$value	= $row->getData($this->getColumn()->getIndex());
    	if($value) return Mage::getModel('advancedpdfprocessor/template')->load($value)->getName();
    }
    
}
