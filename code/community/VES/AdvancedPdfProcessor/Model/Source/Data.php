<?php

class VES_AdvancedPdfProcessor_Model_Source_Data
{
	const FONT_FAMILY 	= 1;
	const FONT_SIZE 	= 2;
	const FONT_ITALIC 	= 3;
	const FONT_BOLD 	= 4;
	
	public static function toOptionArray($type) {
		switch ($type) {
			case self::FONT_FAMILY:
				return array(
					array('label'		=>	 Mage::helper('advancedpdfprocessor')->__('San-serif'),'value'		=> 	 'san-serif',),
					array('label'		=>	 Mage::helper('advancedpdfprocessor')->__('Serif'),'value'		=> 	 'serif',)
				);
			break;
			case self::FONT_ITALIC:
				return array(
					array('label'		=>	 Mage::helper('advancedpdfprocessor')->__('No'),'value'		=> 	 '0',),
					array('label'		=>	 Mage::helper('advancedpdfprocessor')->__('Yes'),'value'		=> 	 '1',)
				);
			break;
			case self::FONT_BOLD:
				return array(
					array('label'		=>	 Mage::helper('advancedpdfprocessor')->__('No'),'value'		=> 	 '0',),
					array('label'		=>	 Mage::helper('advancedpdfprocessor')->__('Yes'),'value'		=> 	 '1',)
				);
			break;
			case self::FONT_SIZE:
				$return = array();
				foreach(array('8','10','12','14','16','18','24','32','48') as $_size) {
					$return[] = array('label'		=> 	Mage::helper('advancedpdfprocessor')->__($_size),'value'		=> 	$_size,);
				}
				return $return;
			break;
		}
	}
}