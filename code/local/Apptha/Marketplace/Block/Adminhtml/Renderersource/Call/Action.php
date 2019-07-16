<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        
        $orderId = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel("sales/order")->load($orderId);

        $html = "Confirm Customer";
        return $html;
    }

}

