<?php

class VES_PdfProCustomVariables_Block_Adminhtml_Pdfprocustomvariables_Edit_Tab_Intergration extends Mage_Adminhtml_Block_Widget_Form
{
	public function __construct(){
		$this->setTemplate('ves_pdfprocustomvariables/intergrationcode.phtml');
	}
	
	public function getPdfProCustomVariables() {
		return Mage::registry('pdfprocustomvariables_data');
	}
	
	public function getPdfProCustomVariableLabel($variable_code) {
		
	}
}