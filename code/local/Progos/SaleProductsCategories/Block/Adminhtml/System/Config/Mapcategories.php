<?php

class Progos_SaleProductsCategories_Block_Adminhtml_System_Config_Mapcategories extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('progos/saleproductscategories/system/config/map.phtml');
    }

    /**
     * Remove scope label
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for synchronize button
     *
     * @return string
     */
    public function getMappingUrl()
    {
        return Mage::getSingleton('adminhtml/url')->getUrl('admin_saleproductscategories/adminhtml_onsale/map');
    }
    /**
     * Generate synchronize button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        /** @var $button Mage_Adminhtml_Block_Widget_Button */
        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(array(
                'id'        => 'map_button',
                'label'     => $this->helper('adminhtml')->__('Map Categories'),
                'onclick'   => 'javascript:map(); return false;'
            ));

        return $button->toHtml();
    }

}
