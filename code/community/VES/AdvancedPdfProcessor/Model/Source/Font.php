<?php
class VES_AdvancedPdfProcessor_Model_Source_Font
{
	public function toOptionArray(){
		return array(
		    '0'			=> Mage::helper('advancedpdfprocessor')->__('No Label'),
			'Arial.ttf'	=> Mage::helper('advancedpdfprocessor')->__('Arial'),
		);
	}
}