<?php
/**
 * Progos_OrdersGrid
 * Rewrite Extension
 *
 * @category   Progos
 * @package    Progos_OrdersGrid
 * @copyright  Copyright (c) 2017 Elabelz (https://www.elabelz.com)
 * @author     Hassan Ali Shahzad (hassan.ali@progos.org)
 * @created_at 08/01/2018
 */

class Progos_OrdersGrid_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * This function will sync orders in extended order grid from Mobile cron request
     * Its will only sync non synced orders
     * $orders can be array of order ids or one id
     */
    public function syncMobileCronOrders($orderIds)
    {
        if(is_array($orderIds)){
            $ids = implode(',',$orderIds);
        }
        else{

            $ids = $orderIds;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table = Mage::getSingleton('core/resource')->getTableName('mageworx_ordersgrid/order_grid');
        $sql = "SELECT entity_id FROM {$table} where entity_id in (". $ids .")" ;
        $alreadySyncedIds = $connection->fetchCol($sql);
        $needToSync = array();
        if(!empty($alreadySyncedIds)){
            $needToSync =  array_diff($orderIds,$alreadySyncedIds);
        }
        else{
            $needToSync = $orderIds;
        }
        if(!empty($needToSync)){
            Mage::getResourceModel('mageworx_ordersgrid/order_grid')->syncOrders($needToSync,true);
        }
    }


    /**
     * This function will return order ids from given date
     *
     * @param $fromDate
     * @param $toDate
     * @return array order ids
     *
     */
    public function syncOrdersToExtendedGrid($fromDate, $toDate){
        $fromDate = date('Y-m-d H:i:s', strtotime(str_replace('-', '/', $fromDate)));
        $toDate = date('Y-m-d 23:59:59', strtotime(str_replace('-', '/', $toDate)));
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT sales_flat_order.entity_id
                FROM
                    sales_flat_order
                    LEFT OUTER JOIN
                    mageworx_ordersgrid_order_grid ON sales_flat_order.entity_id = mageworx_ordersgrid_order_grid.entity_id
                WHERE mageworx_ordersgrid_order_grid.entity_id IS NULL
                 AND sales_flat_order.created_at >= '".$fromDate."' AND sales_flat_order.created_at <= '".$toDate."'" ;
        $needToSync = array();
        $needToSync = $connection->fetchCol($sql);
        if(!empty($needToSync)){
            Mage::getResourceModel('mageworx_ordersgrid/order_grid')->syncOrders($needToSync,true);
        }

    }
}