<?php
class VES_AdvancedPdfProcessor_Block_Adminhtml_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Store
{
    public function render(Varien_Object $row)
    {
    	$vesHelper = Mage::helper('ves_core');
    	$value	= $row->getData($this->getColumn()->getIndex());
    	if($value) return '<img width="100" src="'.Mage::getBaseUrl('media').$value.'" />';
    	return '<img width="100" src="'.$this->getSkinUrl('images/placeholder/thumbnail.jpg').'" />';
    }
    
}
