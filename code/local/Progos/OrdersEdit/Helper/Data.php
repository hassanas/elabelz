<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php
/**
 * Class Progos_OrdersEdit_Helper_Data
 */
class Progos_OrdersEdit_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Add Failed Delivery status for necessary order
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function getFailedDeliveryStatus(Mage_Sales_Model_Order $order)
    {
        $customer_email = $order->getCustomerEmail();
        $current_date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $fromDate = date('Y-m-d H:i:s', strtotime($order->getCreatedAt()));
        $toDate = date('Y-m-d H:i:s', strtotime($current_date));
        $orderId = $order->getId();
        
        $orderOrange = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('created_at', array('gteq' => $fromDate))
            ->addFieldToFilter('customer_email', $customer_email)
            ->addFieldToFilter('failed_delivery', array('neq' => 1));

        $orderGreen =  Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('created_at', array('gteq' => $fromDate))
            ->addFieldToFilter('entity_id',(array('neq'=>$orderId)))
            ->addFieldToFilter('customer_email', $customer_email)
            ->addFieldToFilter('failed_delivery', array('eq' => 1))->getFirstItem();

        if ($order->getStatus() == "failed_delivery") {
            foreach ($orderOrange as $orange) {
                /** @var Mage_Sales_Model_Order $orange */
                $orange->setFailedDelivery(3);
                $orange->save();
            }
            $order->setFailedDelivery(1);
        } elseif ($order->getStatus() != "failed_delivery") {
            $rejectedOrder = Mage::getModel('sales/order')->getCollection()
                ->addFieldToSelect(array('failed_delivery'))
                ->addFieldToFilter('customer_email', $customer_email)
                ->addFieldToFilter('failed_delivery', array('in' => array(1, 3, 2)))
                ->addFieldToFilter('entity_id',(array('neq'=>$orderId)))
                ->addAttributeToFilter('created_at', array('lteq' => $fromDate))
                ->setOrder('created_at', 'DESC')
                ->getFirstItem();

            if ($rejectedOrder->getId()) {
                if ($rejectedOrder->getFailedDelivery() == 1 || $rejectedOrder->getFailedDelivery() == 3) {
                    foreach ($orderOrange as $orange) {
                        /** @var Mage_Sales_Model_Order $orange */
                        if($orange->getStatus == "failed_delivery"){
                           $orange->setFailedDelivery(1);
                        }
                        else{
                        $orange->setFailedDelivery(3);
                        $orange->save();
                        }
                    }

                    $order->setFailedDelivery(3);
                } elseif ($rejectedOrder->getFailedDelivery() == 2) {
                    
                    if($orderGreen->getId()){
                        $orderFailed_time = date('Y-m-d H:i:s', strtotime($orderGreen->getCreatedAt()));

                        $orderOrange =  Mage::getModel('sales/order')->getCollection()
                                            ->addAttributeToFilter('created_at', array('lteq' => $orderFailed_time))
                                            ->addFieldToFilter('customer_email', $customer_email)
                                            ->addFieldToFilter('failed_delivery', array('neq' => 1));

                    }
                    foreach ($orderOrange as $green) {
                        /** @var Mage_Sales_Model_Order $green */
                        $green->setFailedDelivery(2);
                        $green->save();
                    }

                    $order->setFailedDelivery(2);
                }
            } elseif ($order->getStatus() == "successful_delivery"
                || $order->getStatus() == "successful_delivery_partially"
                || $order->getStatus() == "complete"
            ) {
                
                if($orderGreen->getId()){
                    $orderFailed_time = date('Y-m-d H:i:s', strtotime($orderGreen->getCreatedAt()));

                    $orderOrange =  Mage::getModel('sales/order')->getCollection()
                        ->addAttributeToFilter('created_at', array('lteq' => $orderFailed_time))
                        ->addFieldToFilter('customer_email', $customer_email)
                        ->addFieldToFilter('failed_delivery', array('neq' => 1));

                }

                foreach ($orderOrange as $green) {
                    /** @var Mage_Sales_Model_Order $green */
                    $green->setFailedDelivery(2);
                    $green->save();
                }

                $order->setFailedDelivery(2);
            }
        }

        $order->save();
    }
}