<?php

/**
 * MageWorx
 * Admin Order Grid extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Block_Adminhtml_System_Config_Columns_Order extends Mage_Adminhtml_Block_System_Config_Form_Field
{    
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml();
    }

    protected function _getAddRowButtonHtml()
    {
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/mageworx_ordersgrid/columnsOrder/');

        $buttonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setLabel($this->__('Manage Columns'))
                ->setOnClick("window.location.href='" . $url . "'")
                ->toHtml();

        return $buttonHtml;
    }

}