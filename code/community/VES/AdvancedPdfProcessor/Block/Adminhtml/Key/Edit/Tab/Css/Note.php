<?php
class VES_AdvancedPdfProcessor_Block_Adminhtml_Key_Edit_Tab_Css_Note extends Varien_Data_Form_Element_Abstract{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
    }
    public function getElementHtml()
    {
        $sku = $this->getSku();
        if(!$sku) {
        	$html = '<p id="' . $this->getHtmlId() . '"'.'>You do <b>not</b> choose template.</p>';
        }else {
        	$html = '<p id="' . $this->getHtmlId() . '"'.'>Your classes must be start by <b>.'.$sku.
        	'</b> . Ex .'.$sku.' .your_class</p>';
        }
        $vesHelper = Mage::helper('ves_core');
        return $html;
    }
}