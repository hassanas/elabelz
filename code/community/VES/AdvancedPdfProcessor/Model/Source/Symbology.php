<?php
class VES_AdvancedPdfProcessor_Model_Source_Symbology
{
	
	public function toOptionArray(){
		return array(
		    'BCGcodabar'	=> Mage::helper('advancedpdfprocessor')->__('Codabar'),
			'BCGcode11'		=> Mage::helper('advancedpdfprocessor')->__('Code 11'),
			'BCGcode39'		=> Mage::helper('advancedpdfprocessor')->__('Code 39'),
			'BCGcode39extended'		=> Mage::helper('advancedpdfprocessor')->__('Code 39 Extended'),
			'BCGcode93'		=> Mage::helper('advancedpdfprocessor')->__('Code 93'),
			'BCGcode128'	=> Mage::helper('advancedpdfprocessor')->__('Code 128'),
			'BCGean8'		=> Mage::helper('advancedpdfprocessor')->__('EAN-8'),
			'BCGean13'		=> Mage::helper('advancedpdfprocessor')->__('EAN-13'),
			'BCGgs1128'		=> Mage::helper('advancedpdfprocessor')->__('GS1-128 (EAN-128)'),
			'BCGisbn'		=> Mage::helper('advancedpdfprocessor')->__('ISBN'),
			'BCGi25'		=> Mage::helper('advancedpdfprocessor')->__('Interleaved 2 of 5'),
			'BCGs25'		=> Mage::helper('advancedpdfprocessor')->__('Standard 2 of 5'),
			'BCGmsi'		=> Mage::helper('advancedpdfprocessor')->__('MSI Plessey'),
			'BCGupca'		=> Mage::helper('advancedpdfprocessor')->__('UPC-A'),
			'BCGupce'		=> Mage::helper('advancedpdfprocessor')->__('UPC-E'),
			'BCGupcext2'	=> Mage::helper('advancedpdfprocessor')->__('UPC Extenstion 2 Digits'),
			'BCGupcext5'	=> Mage::helper('advancedpdfprocessor')->__('UPC Extenstion 5 Digits'),
			'BCGpostnet'	=> Mage::helper('advancedpdfprocessor')->__('Postnet'),
			'BCGintelligentmail'	=> Mage::helper('advancedpdfprocessor')->__('Intelligent Mail'),
			'BCGothercode'	=> Mage::helper('advancedpdfprocessor')->__('Other Barcode'),
		);
	}
}