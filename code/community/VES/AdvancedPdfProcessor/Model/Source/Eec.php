<?php
class VES_advancedpdfprocessor_Model_Source_Eec
{
	public function toOptionArray(){
		return array(
			"L"=>Mage::helper('advancedpdfprocessor')->__('L - Smallest'),
			'M'=>Mage::helper('advancedpdfprocessor')->__('M'),
			'Q'=>Mage::helper('advancedpdfprocessor')->__('Q'),
			'H'=>Mage::helper('advancedpdfprocessor')->__('H - Best'),
		);
	}
}