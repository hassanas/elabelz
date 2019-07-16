<?php
/*
 * @author     Hassan Ali Shahzad
 * @package    Progos_AfterShipmentUpdates
 * Date    22-02-2017
 */
class Progos_AfterShipmentUpdates_Model_Observer
{

    public function updateMarketPlaceAfterShipment(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $currentDateTime =date('Y-m-d H:i:s');
        $marketplaceCollection = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter("increment_id", ["eq"=>$order->getIncrementId()]);
        if($marketplaceCollection->getSize()){
            foreach ($marketplaceCollection as $row) {
                //update order status and shipping date in commission table
                $row->setShippedFromElabelzDate($currentDateTime);
                $row->setOrderStatus('shipped_from_elabelz');
                //update order item status in commsiontable
                if($row->getItemOrderStatus() == 'processing' || $row->getItemOrderStatus() == 'ready'){
                    $row->setItemOrderStatus('shipped_from_elabelz');
                }
                $row->save ();
            }
        }
    }
}
