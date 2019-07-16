<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Shipping_Tracker extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{

    public function render(Varien_Object $row) {
        $entityId = $row->getData($this->getColumn()->getIndex());
		// $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
		// ->setOrderFilter($order)
		// ->load();




        $order = Mage::getModel("sales/order")->load($entityId);
		$order->getShipmentsCollection();

		foreach ($shipmentCollection as $shipment) {
			foreach($shipment->getAllTracks() as $tracknum) {
				$tracknums[]=$tracknum->getNumber();
			}
		}

		var_dump($tracknums);


        // $shipping = $order->getShippingAddress()->getData();

        // return $order->getShippingAddress()->getAllTracks();
    }

}

