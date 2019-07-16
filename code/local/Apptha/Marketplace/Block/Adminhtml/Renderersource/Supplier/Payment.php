<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Payment extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        
        $entityId = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel("sales/order")->load($entityId);
        $payment = $order->getPayment()->getMethodInstance()->getTitle();
        return $payment;
    }

}

