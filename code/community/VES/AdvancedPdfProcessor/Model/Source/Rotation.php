<?php
class VES_AdvancedPdfProcessor_Model_Source_Rotation
{
	public function toOptionArray(){
		return array(
		    '0'		=> Mage::helper('advancedpdfprocessor')->__('No rotation'),
			'90'	=> Mage::helper('advancedpdfprocessor')->__('90 degree clockwise'),
			'180'	=> Mage::helper('advancedpdfprocessor')->__('180 degree clockwise'),
			'270'	=> Mage::helper('advancedpdfprocessor')->__('270 degree clockwise'),
		);
	}
}