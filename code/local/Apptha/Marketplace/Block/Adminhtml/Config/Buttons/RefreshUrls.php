<?php
/**
 * Created By Azhar 11-10-2016.
 * For refresh configureable product images url
 *
 */

class Apptha_Marketplace_Block_Adminhtml_Config_Buttons_RefreshUrls extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('apptha/config/button-test-refreshurls.phtml');
        }
        return $this;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('marketplace')->__($originalData['button_label']),
            'html_id' => $element->getHtmlId(),
            'url' => Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/zendesk/checkOutbound')
        ));

        return $this->_toHtml();
    }
}