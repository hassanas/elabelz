<?php
/**
 * @category   Fetchr
 * @package    Fetchr_Shipping
 * @author     Islam Khalil
 * @website    Fetchr.us
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
    */
class Fetchr_Shipping_Model_Shipping extends Mage_Shipping_Model_Shipping
{
    public function collectCarrierRates($carrierCode, $request)
    {
        if (!$this->_checkCarrierAvailability($carrierCode, $request)) {
            return $this;
        }
        return parent::collectCarrierRates($carrierCode, $request);
    }
 
    protected function _checkCarrierAvailability($carrierCode, $request = null)
    {
        $showInFronend  = Mage::getStoreConfig('carriers/fetchr/showinfrontend');
        if(!$showInFronend){
            if($carrierCode == 'fetchr'){ #Hide Flat Rate for non logged in customers
                return false;
            }
        }
        return true;
    }

    public function orderShippment($order_id){
        $order=Mage::getModel('sales/order')->load($order_id);
         
        $qty=array();
        foreach($order->getAllItems() as $eachOrderItem){
         
         $Itemqty=0;
         $Itemqty = $eachOrderItem->getQtyOrdered()
                    - $eachOrderItem->getQtyShipped()
                    - $eachOrderItem->getQtyRefunded()
                    - $eachOrderItem->getQtyCanceled();
         $qty[$eachOrderItem->getId()]=$Itemqty;
         
        }
         
        /*
        echo "<pre>";
        print_r($qty);
        echo "</pre>";
        */
        /* check order shipment is prossiable or not */
         
        $email=true;
        $includeComment=true;
        $comment="test Shipment";
         
        if ($order->canShip()) {
                 /* @var $shipment Mage_Sales_Model_Order_Shipment */
         /* prepare to create shipment */
         $shipment = $order->prepareShipment($qty);
           if ($shipment) {
           $shipment->register();
           $shipment->addComment($comment, $email && $includeComment);
           $shipment->getOrder()->setIsInProcess(true);
           $shipment_created = "no";
           Mage::helper("fetchr_shipping/tracker")->pushOrderAfterShipmentCreation($shipment,$shipment_created);
            try {
                $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();
                    if(!$shipment->getEmailSent()){
                        $shipment->sendEmail(true);
                        $shipment->setEmailSent(true);
                        $shipment->save();
                    }
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton ( 'adminhtml/session' )->addError ($e->getMessage ());
            }
         
           }
         
        }
        else{
            $shipment_created = "yes";
            $shipment = $order->getShipmentsCollection()->getFirstItem();
            Mage::helper("fetchr_shipping/tracker")->pushOrderAfterShipmentCreation($shipment,$shipment_created);
        }
    }
}
