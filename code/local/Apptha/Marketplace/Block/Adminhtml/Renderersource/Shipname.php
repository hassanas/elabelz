<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Shipname extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->loadByIncrementId($value);
        return $order->getShippingAddress()->getName();
    }

}

