<?php

class VES_AdvancedPdfProcessor_Model_Source_Widget_Optiontype
{
	const OPTION_TEXT 	= 'text';
	const OPTION_IMAGE 	= 'image';
	
	public function toOptionArray() {
		return array(
				self::OPTION_TEXT		=>		array('code' => 'text','title' => 'Default'),
				self::OPTION_IMAGE		=>		array('code' => 'image','title' => 'Image'),
		);
	}
}