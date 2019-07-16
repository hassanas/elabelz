<?php


class Progos_OrdersGrid_Model_Resource_Order_Grid extends MageWorx_OrdersGrid_Model_Resource_Order_Grid
{

    protected $newCondition;

    /**
     * override this function to optimize order sync functionality
     * @param string $orderIds
     * @param bool $isCallFromAppShellCrone  This argument is for app cron orders receive true from place_mobile_orders_cron.php
     * @return $this
     */
    public function syncOrders($orderIds = '', $isCallFromAppShellCrone=false)
    {
        if (!empty($orderIds)) {
            if (is_array($orderIds)) {
                $this->orderIds = implode(',', $orderIds);
            } else {
                $this->orderIds = $orderIds;
            }
            $this->condition = " AND grid.entity_id IN (".$this->orderIds.")";
            $this->newCondition = "grid.entity_id IN (".$this->orderIds.")";


            $actionName     = Mage::app()->getRequest()->getActionName();
            $controllerName = Mage::app()->getRequest()->getControllerName();
            //$actionName saveOrder for first time sync complete order
            if($isCallFromAppShellCrone == false && $actionName != NULL
                && $controllerName != NULL && Mage::registry('comment-added')=='yes'
                && $actionName != 'saveOrder' && ($controllerName != 'sales_order_create' && $actionName !='save')
                && ($actionName != 'runAppOrdersProcess' && $controllerName !='apporders')
                && ($actionName != 'runwebOrdersProcess' && $controllerName !='weborders')
                && ($actionName != 'processOrders' && $controllerName !='automateorderbackend')
            ):
                Mage::unregister('comment-added');
                $this->getCommentOrderGrid(); //Only updating the comment history on this action
            else:
                $this->syncOrderTable();
                $this->getAllOrderGrid(); // combine Eight update query into single query
                $this->syncAddressTable('shipping');
            endif;
        }
        return $this;
    }
    /**
     *  Getting data from all order table and use  in single update query
     */
    protected function getAllOrderGrid()
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
        $selectQuery = "select grid.entity_id as actualIdToUpdate,ship.*,address.*,pay.*,track.*,invoice.*,history.*,items.* from ".$this->getOrderGridTable()." grid 
            LEFT JOIN (
                SELECT `entity_id` AS shipment_id,
                    (IF(IFNULL(`entity_id`, 0)>0, 1, 0)) AS `shipped`,
                    SUM(`total_qty`) AS `total_qty_shipped`,
                    ".$this->getTable('sales/shipment_grid').".`order_id` AS `shipment_order_id`,
                    ".$this->getTable('sales/shipment_grid').".`shipping_name`
                FROM ".$this->getTable('sales/shipment_grid')."
                GROUP BY `shipment_order_id`
            ) AS ship
            ON grid.entity_id = ship.shipment_order_id
            LEFT JOIN (
                SELECT *
                FROM ".$tablePrefix."sales_flat_order_address
                WHERE address_type = 'billing'
            ) AS address
            ON grid.entity_id = address.parent_id
            LEFT JOIN (
                SELECT `entity_id` AS tracking_id,
                    GROUP_CONCAT(`track_number` SEPARATOR '\n') AS `tracking_number`,
                    ".$tablePrefix."sales_flat_shipment_track.`parent_id` AS `tracking_parent_id`,
                    ".$tablePrefix."sales_flat_shipment_track.`order_id` AS `tracking_order_id`
                FROM ".$tablePrefix."sales_flat_shipment_track
                GROUP BY `tracking_order_id`
            ) AS track
            ON grid.entity_id = track.tracking_order_id
            LEFT JOIN (SELECT * FROM ".$tablePrefix."sales_flat_order_payment) AS pay
            ON grid.entity_id = pay.parent_id
            LEFT JOIN (
                SELECT `entity_id` AS invoice_id,
                    GROUP_CONCAT(`increment_id` SEPARATOR '\n') AS `invoice_increment_id`,
                    ".$tablePrefix."sales_flat_invoice.`order_id` AS `invoice_order_id`
                FROM ".$tablePrefix."sales_flat_invoice
                GROUP BY `invoice_order_id`
            ) AS invoice
            ON grid.entity_id = invoice.invoice_order_id
            LEFT JOIN (
                SELECT `entity_id` AS comment_id,
                    GROUP_CONCAT(`comment` SEPARATOR '\n') AS `order_comment`,
                    ".$tablePrefix."sales_flat_order_status_history.`parent_id` AS `comment_parent_id`
                FROM ".$tablePrefix."sales_flat_order_status_history
                GROUP BY `comment_parent_id`
            ) AS history
            ON grid.entity_id = history.comment_parent_id
            LEFT JOIN (
                SELECT `item_id` AS entity_id,
                    ".$tablePrefix."sales_flat_order_item.`order_id`,
                    ".$tablePrefix."sales_flat_order_item.`parent_item_id`,
                    GROUP_CONCAT(`name` SEPARATOR '\n') AS `product_names`,
                    GROUP_CONCAT(`sku` SEPARATOR '\n') AS `skus`,
                    GROUP_CONCAT(`product_id` SEPARATOR '\n') AS `product_ids`,
                    GROUP_CONCAT(`product_options` SEPARATOR '^') AS `product_options`,
                    SUM(`qty_refunded`) AS `total_qty_refunded`,
                    SUM(`qty_ordered`) AS `total_qty_ordered_aggregated`,
                    SUM(`qty_canceled`) AS `total_qty_canceled`,
                    SUM(`qty_invoiced`) AS `total_qty_invoiced`
                FROM ".$tablePrefix."sales_flat_order_item
                WHERE (".$tablePrefix."sales_flat_order_item.`parent_item_id` IS NULL)
                GROUP BY `order_id`
            ) AS items
            ON grid.entity_id = items.order_id
            WHERE ".$this->newCondition;
        try {
            $readDatas=$this->_getWriteAdapter()->fetchAll($selectQuery); // getting the data from query
        } catch (Exception $e) {

        }

        foreach($readDatas as $readData){
            $data=array(
                'entity_id' =>  $readData['actualIdToUpdate'],
                'shipping_name'=>$readData['shipping_name'],
                'shipped'=>$readData['shipped'],
                'total_qty_shipped'=>$readData['total_qty_shipped'],
                'billing_name'=>$readData['firstname'].' '.$readData['lastname'],
                'billing_company'=>$readData['company'],
                'billing_street'=>$readData['street'],
                'billing_city'=>$readData['city'],
                'billing_region'=>$readData['region'],
                'billing_country'=>$readData['country_id'],
                'billing_postcode'=>$readData['postcode'],
                'billing_telephone'=>$readData['telephone'],
                'tracking_number'=>$readData['tracking_number'],
                'order_comment'=>$readData['order_comment'],
                'payment_method'=>$readData['method'],
                'invoice_increment_id'=>$readData['invoice_increment_id'],
                'product_names'=>$readData['product_names'],
                'skus'=>$readData['skus'],
                'product_ids'=>$readData['product_ids'],
                'product_options'=>$readData['product_options'],
                'total_qty_ordered_aggregated'=>$readData['total_qty_ordered_aggregated'],
                'total_qty_canceled'=>$readData['total_qty_canceled'],
                'total_qty_invoiced'=>$readData['total_qty_invoiced'],
                'total_qty_refunded'=>$readData['total_qty_refunded'],
            );
            $model = Mage::getModel('mageworx_ordersgrid/order_grid')->load($readData['actualIdToUpdate']);
            $model->addData($data)->save();
            unset($data);
        }
        return true;
    }

    /**
     * This function will only sync the orders comments and now its status
     * Hassan added update status in the function
     * @return bool
     */
    protected function getCommentOrderGrid()
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
        $selectQuery = "select grid.entity_id,history.*,sfog.status AS order_current_status from ".$this->getOrderGridTable()." grid    
            JOIN (
                SELECT `entity_id` AS comment_id,
                    GROUP_CONCAT(`comment` SEPARATOR '\n') AS `order_comment`,
                    ".$tablePrefix."sales_flat_order_status_history.`parent_id` AS `comment_parent_id`
                FROM ".$tablePrefix."sales_flat_order_status_history
                GROUP BY `comment_parent_id`
            ) AS history
            ON grid.entity_id = history.comment_parent_id
           JOIN ".$tablePrefix."sales_flat_order_grid AS sfog  ON grid.entity_id = sfog.entity_id
            WHERE ".$this->newCondition;
        $readData=$this->_getWriteAdapter()->fetchRow($selectQuery); // getting the data from query
        $data=array(
            'order_comment'=>$readData['order_comment'],
            'status'=>$readData['order_current_status']
        );

        // updating the mageworx order grid table
        $model = Mage::getModel('mageworx_ordersgrid/order_grid')->load($this->orderIds);
        $model->addData($data)->save();
        return true;
    }


}