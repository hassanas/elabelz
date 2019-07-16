<?php

/**
 * Progos
 *
 *
 *
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_SpecialPrice extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {

        $_product = Mage::getModel('catalog/product')->load($row->getData($this->getColumn()->getProductid()));

        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($_product->getId());

        if (isset($parentIds[0])) {
            $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
            if ($parent->getSpecialPrice() > 0) {
                return '<span style="color: red;">' . Mage::helper('core')->currency($parent->getSpecialPrice(), true, false) . '</span>';
            } else {
                return 'NA';
            }
        } else {
            if ($_product->getSpecialPrice() > 0) {
                return '<span style="color: red;">' . Mage::helper('core')->currency($_product->getSpecialPrice(), true, false) . '</span>';
            } else {
                return 'NA';
            }
        }

    }
     public function renderExport(Varien_Object $row)
    {
       $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($row->getProductId());
        if (isset($parentIds[0])) {
            $_resource = Mage::getSingleton('catalog/product')->getResource();
            $pId=$parentIds[0];
            $optionValue = $_resource->getAttributeRawValue($pId,'special_price', Mage::app()->getStore());
            if ($optionValue > 0) {
                return  Mage::helper('core')->currency($optionValue, true, false) ;
            } else {
                return 'NA';
            }
        } else {
              $_resource = Mage::getSingleton('catalog/product')->getResource();
            $optionValue = $_resource->getAttributeRawValue($row->getProductId(),  'special_price', Mage::app()->getStore());
            if ($optionValue > 0) {
                 Mage::helper('core')->currency($optionValue, true, false) ;
            } else {
                return 'NA';
            }
        }
    }
}

