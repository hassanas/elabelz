<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Order_Country extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{

    public function render(Varien_Object $row) {
        $entityId = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel("sales/order")->load($entityId);
        // var_dump($order->getShippingAddress()->getCountryId());
        return Mage::app()->getLocale()->getCountryTranslation($order->getShippingAddress()->getCountryId());
    }

}

