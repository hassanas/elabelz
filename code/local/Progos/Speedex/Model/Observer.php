<?php

class Progos_Speedex_Observer 
{
    public function addSpeedexButton($object)
    {
        $orderBlock = $object->getBlock();
        $speedex = Mage::registry('speedex_register');
        if($orderBlock instanceof Mage_Adminhtml_Block_Sales_Order_View && empty($speedex)) {
            $itemscount 	= 0;
            $totalWeight 	= 0;
            $_order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));				
            $itemsv = $_order->getAllVisibleItems();
            foreach($itemsv as $itemvv){
                    if($itemvv->getQtyOrdered() > $itemvv->getQtyShipped()){
                            $itemscount += $itemvv->getQtyOrdered() - $itemvv->getQtyShipped();
                    }
                    if($itemvv->getWeight() != 0){
                            $weight =  $itemvv->getWeight()*$itemvv->getQtyOrdered(); 
                    } else {
                            $weight =  0.5*$itemvv->getQtyOrdered();
                    }
                    $totalWeight 	+= $weight;
             }
            $orderBlock->addButton('create_speedex_shipment', array(
							'label'     => Mage::helper('Sales')->__('Prepare Speedex Shipment'),
							'onclick'   => 'speedexpop('.$itemscount.')',
							'class'     => 'go'
						), 0, 100, 'header', 'header');
            Mage::register('speedex_register', 1);
        }
    }
}

