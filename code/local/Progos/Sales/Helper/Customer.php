<?php
class Progos_Sales_Helper_Customer extends Mage_Core_Helper_Abstract
{
    public function getOrderStatusHtml( $code , $orderStatuses ){
        if( $code == $orderStatuses['customer_orders_status'] )
            $code = $orderStatuses['customer_orders_status_change'];

        $status = Mage::getModel('sales/order_status')
            ->load($code);
        if( $status ){
            return $status->getStoreLabel();
        }
    }

    public function runtimeChangeOrderStatusLabel(){
        $result = array();
        $cOrderStatus           = Mage::getStoreConfig('customerdashboardorderstatus/general/customer_orders_status');
        $cOrderStatusChange     = Mage::getStoreConfig('customerdashboardorderstatus/general/customer_orders_status_change');
        $result['customer_orders_status']           = ( empty( $cOrderStatus )?'pending_seller_confirmation':$cOrderStatus );
        $result['customer_orders_status_change']    = ( empty( $cOrderStatusChange )?'confirmed':$cOrderStatusChange );
        return $result;
    }
}
