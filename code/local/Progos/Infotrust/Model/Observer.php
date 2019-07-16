<?php

class Progos_Infotrust_Model_Observer
{
    const XML_PATH_KUSTOMER_EMAIL_ENABLE = 'infotrust/infotrust/kustomer_email_enable';
    const XML_PATH_KUSTOMER_SHIPMENT_EMAIL_ENABLE = 'infotrust/infotrust/kustomer_shipment_email_enable';
    const XML_PATH_KUSTOMER_CREDITMEMO_EMAIL_ENABLE = 'infotrust/infotrust/kustomer_creditmemo_email_enable';

    public function afterSalesOrderSaved(Varien_Event_Observer $observer)
    {
        ini_set('memory_limit', '-1');
        $event = $observer->getEvent();
        /** get Order ID */
        $orderId = $event->getOrder()->getId();
        /** Load Order Id*/
        $order = Mage::getModel('sales/order')->load($orderId);
        $isKustomerEmailEnable = Mage::getStoreConfig(self::XML_PATH_KUSTOMER_EMAIL_ENABLE);
        //if enabled logs block
        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
            Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
            Mage::log('inside afterSalesOrderSaved', null, 'jsonld.log');
        }

        if ($isKustomerEmailEnable == 1) {
            /** Load Customer */
            $json = Mage::helper('progos_infotrust')->getJSONLD($order);
            //send email using zend
            Mage::helper('progos_infotrust')->zendSend($json, $order);
        }
        //if enabled logs block
        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
            Mage::log('after afterSalesOrderSaved', null, 'jsonld.log');
        }
        return $this;
    }
    /**
     * observer method to get track data against
     * order and send to email hook
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function saleOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer)
    {
        ini_set('memory_limit', '-1');
        $event = $observer->getEvent();
        $track = $event->getTrack();
        $order = Mage::getModel('sales/order')->load($track->getOrderId());
        //if enabled logs block
        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
            Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
            Mage::log('inside saleOrderShipmentTrackSaveAfter', null, 'jsonld.log');
        }
        $isKustomerShipmentEmailEnable = Mage::getStoreConfig(self::XML_PATH_KUSTOMER_SHIPMENT_EMAIL_ENABLE);
        if ($isKustomerShipmentEmailEnable == 1) {

            $shipmentJsonld = Mage::helper('progos_infotrust')->getShipmentTrackingJsonld($track);
            $subject = 'Shipment for order #' . $order->getIncrementId();
            $text = '<p>Order shipped for <b>' . $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname() . '</b></p>';
            //send email using zend
            Mage::helper('progos_infotrust')->zendSend($shipmentJsonld, $order, $subject, $text);
        }
        //if enabled logs block
        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
            Mage::log('after saleOrderShipmentTrackSaveAfter', null, 'jsonld.log');
        }
        return $this;
    }
    /**
     * observer method to get creditmemo data against
     * order and send to email hook
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function salesOrderCreditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        ini_set('memory_limit', '-1');
        $event = $observer->getEvent();
        $creditmemo = $event->getCreditmemo();
        $order = $creditmemo->getOrder();
        //if enabled logs block
        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
            Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
            Mage::log('inside salesOrderCreditmemoSaveAfter', null, 'jsonld.log');
        }
        $isKustomerCreditmemotEmailEnable = Mage::getStoreConfig(self::XML_PATH_KUSTOMER_CREDITMEMO_EMAIL_ENABLE);
        if ($isKustomerCreditmemotEmailEnable == 1) {

            $creditmemoJsonld = Mage::helper('progos_infotrust')->getCreditmemoJsonld($creditmemo);

            $subject = 'Credit memo for order #' . $order->getIncrementId();
            $text = '<p>Credit memo for <b>' . $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname() . '</b></p>';
            //send email using zend
            Mage::helper('progos_infotrust')->zendSend($creditmemoJsonld, $order, $subject, $text);
        }
        //if enabled logs block
        if (Mage::helper('progos_infotrust')->isKustomerLog()) {
            Mage::log('after salesOrderCreditmemoSaveAfter', null, 'jsonld.log');
        }
        return $this;
    }
}