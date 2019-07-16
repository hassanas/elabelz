<?php
class VES_AdvancedPdfProcessor_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Element extends Mage_Adminhtml_Block_Template
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('ves_advancedpdfprocessor/widget/form/renderer/fieldset/element.phtml');
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
    	$vesCoreHelper = Mage::helper('ves_core');
        $this->_element = $element;
        return $this->toHtml();
    }
}
