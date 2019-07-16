<?php
class VES_AdvancedPdfProcessor_Model_Source_Orientation
{
	public function toOptionArray(){
		return array(
			'portrait'=> Mage::helper('advancedpdfprocessor')->__('Portrait'),
			'landscape'=> Mage::helper('advancedpdfprocessor')->__('Landscape'),
		);
	}
}