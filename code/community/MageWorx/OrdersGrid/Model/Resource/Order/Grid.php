<?php
/**
 * MageWorx
 * Admin Order Grid extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersGrid_Model_Resource_Order_Grid extends Mage_Core_Model_Mysql4_Abstract
{
    CONST MAGEWORX_ORDERSGRID_ORDER_GRID_TABLE  = 'mageworx_ordersgrid/order_grid';
    CONST SALES_ORDER_TABLE                     = 'sales/order';

    /**
     * Order grid columns
     *
     * @var array|null
     */
    protected $gridColumns = null;
    protected $orderIds;
    protected $condition;

    protected function _construct() 
    {        
        $this->_init(self::MAGEWORX_ORDERSGRID_ORDER_GRID_TABLE, 'entity_id');
    }

    /** Synchronize orders data from magento tables to mageworx_ordersgrid_order_grid table
     *
     * @return $this
     * @throws Exception
     */
    public function syncOrders($orderIds = '')
    {
        if ($orderIds) {
            if (is_array($orderIds)) {
                $this->orderIds = implode(',', $orderIds);
            } else {
                $this->orderIds = $orderIds;
            }
            $this->condition = " AND grid.entity_id IN (".$this->orderIds.")";
        }

        $this->syncOrderTable();
        $this->syncShippingTable($this->orderIds);
        $this->syncAddressTable('shipping');
        $this->syncAddressTable('billing');
        $this->syncTrackTable();
        $this->syncPaymentTable();
        $this->syncInvoiceTable();
        $this->syncCommentTable($this->orderIds);
        $this->syncOrderItemsTable();
        $this->updateShipped();

        return $this;
    }

    /**
     * Synchronize sales order grid table
     */
    protected function syncOrderTable()
    {
        $columnsToSelect = array();
        $orderGridTable = $this->getOrderGridTable();
        $select = $this->getUpdateRecordsSelect(self::SALES_ORDER_TABLE, $columnsToSelect);
        $this->_getWriteAdapter()->query($select->insertFromSelect($orderGridTable, $columnsToSelect, true));
    }

    /**
     * Synchronize sales order grid table
     */
    protected function getUpdateRecordsSelect($mainTable, &$flatColumnsToSelect)
    {
        $gridColumns = $this->getOrderGridColumns();

        $flatColumns = array_keys($this->_getReadAdapter()
            ->describeTable(
                $this->getTable($mainTable)
            )
        );
        $flatColumnsToSelect = array_intersect($gridColumns, $flatColumns);

        if ($this->orderIds){
            $select = $this->_getWriteAdapter()->select()
                ->from(array('main_table' => $this->getTable($mainTable)), $flatColumnsToSelect)
                ->where('main_table.' . $this->getIdFieldName() . ' IN('.$this->orderIds.')');
        } else {
            $select = $this->_getWriteAdapter()->select()
                ->from(array('main_table' => $this->getTable($mainTable)), $flatColumnsToSelect);
        }

        return $select;
    }

    /**
     * Retrieve list of order grid columns
     *
     * @return array
     */
    protected function getOrderGridColumns()
    {
        if ($this->gridColumns === null) {
            $this->gridColumns = array_keys(
                $this->_getReadAdapter()->describeTable($this->getOrderGridTable())
            );
        }

        return $this->gridColumns;
    }

    /**
     * Retrieve order grid table
     *
     * @return string
     */
    protected function getOrderGridTable()
    {
        return $this->getTable(self::MAGEWORX_ORDERSGRID_ORDER_GRID_TABLE);
    }

    /**
     * Synchronize sales shipment grid table
     */
    protected function syncShippingTable($orderIds)
    {
        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid 
            LEFT JOIN (
                SELECT `entity_id` AS shipment_id,
                    (IF(IFNULL(`entity_id`, 0)>0, 1, 0)) AS `shipped`,
                    SUM(`total_qty`) AS `total_qty_shipped`,
                    ".$this->getTable('sales/shipment_grid').".`order_id` AS `shipment_order_id`,
                    ".$this->getTable('sales/shipment_grid').".`shipping_name`
                FROM ".$this->getTable('sales/shipment_grid')."
                WHERE `order_id` IN (".$orderIds.") GROUP BY `shipment_order_id`
            ) AS ship
            ON grid.entity_id = ship.shipment_order_id
            SET grid.shipping_name = ship.shipping_name,
                grid.shipped = ship.shipped,
                grid.total_qty_shipped = ship.total_qty_shipped
            WHERE ship.shipment_id IS NOT NULL";
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Synchronize sales order address table
     */
    protected function syncAddressTable($type)
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
            LEFT JOIN (
                SELECT *
                FROM ".$tablePrefix."sales_flat_order_address
                WHERE address_type = '".$type."'
            ) AS address
            ON grid.entity_id = address.parent_id
            SET grid.".$type."_name = CONCAT_WS(' ', address.firstname, address.lastname),
                grid.".$type."_company = address.company,
                grid.".$type."_street = address.street,
                grid.".$type."_city = address.city,
                grid.".$type."_region = address.region,
                grid.".$type."_country = address.country_id,
                grid.".$type."_postcode = address.postcode,
                grid.".$type."_telephone = address.telephone
            WHERE address.entity_id IS NOT NULL".$this->condition;
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Synchronize sales shipment track table
     */
    protected function syncTrackTable()
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
            LEFT JOIN (
                SELECT `entity_id` AS tracking_id,
                    GROUP_CONCAT(`track_number` SEPARATOR '\n') AS `tracking_number`,
                    ".$tablePrefix."sales_flat_shipment_track.`parent_id` AS `tracking_parent_id`,
                    ".$tablePrefix."sales_flat_shipment_track.`order_id` AS `tracking_order_id`
                FROM ".$tablePrefix."sales_flat_shipment_track
                GROUP BY `tracking_order_id`
            ) AS track
            ON grid.entity_id = track.tracking_order_id
            SET grid.tracking_number = track.tracking_number
            WHERE track.tracking_id IS NOT NULL".$this->condition;
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Synchronize sales order payment table
     */
    protected function syncPaymentTable()
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
            LEFT JOIN (SELECT * FROM ".$tablePrefix."sales_flat_order_payment) AS pay
            ON grid.entity_id = pay.parent_id
            SET grid.payment_method = pay.method
            WHERE pay.entity_id IS NOT NULL".$this->condition;
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Synchronize sales invoice table
     */
    protected function syncInvoiceTable()
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
            LEFT JOIN (
                SELECT `entity_id` AS invoice_id,
                    GROUP_CONCAT(`increment_id` SEPARATOR '\n') AS `invoice_increment_id`,
                    ".$tablePrefix."sales_flat_invoice.`order_id` AS `invoice_order_id`
                FROM ".$tablePrefix."sales_flat_invoice
                GROUP BY `invoice_order_id`
            ) AS invoice
            ON grid.entity_id = invoice.invoice_order_id
            SET grid.invoice_increment_id = invoice.invoice_increment_id
            WHERE invoice.invoice_id IS NOT NULL".$this->condition;
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Synchronize sales order status history table
     */
    protected function syncCommentTable($orderIds)
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
            LEFT JOIN (
                SELECT `entity_id` AS comment_id,
                    GROUP_CONCAT(`comment` SEPARATOR '\n') AS `order_comment`,
                    ".$tablePrefix."sales_flat_order_status_history.`parent_id` AS `comment_parent_id`
                FROM ".$tablePrefix."sales_flat_order_status_history
            WHERE `parent_id` IN (".$orderIds.") GROUP BY `comment_parent_id` ) AS history
            ON grid.entity_id = history.comment_parent_id
            SET grid.order_comment = history.order_comment
            WHERE history.comment_id IS NOT NULL";
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Synchronize sales order items table
     */
    protected function syncOrderItemsTable()
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

        $this->_getWriteAdapter()->query('SET group_concat_max_len = 32768');
        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
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
                WHERE (".$tablePrefix."sales_flat_order_item.`parent_item_id` IS NULL) GROUP BY `order_id`
            ) AS items
            ON grid.entity_id = items.order_id
            SET grid.product_names = items.product_names,
            grid.skus = items.skus,
            grid.product_ids = items.product_ids,
            grid.product_options = items.product_options,
            grid.total_qty_ordered_aggregated = items.total_qty_ordered_aggregated,
            grid.total_qty_canceled = items.total_qty_canceled,
            grid.total_qty_invoiced = items.total_qty_invoiced,
            grid.total_qty_refunded = items.total_qty_refunded
            WHERE items.entity_id IS NOT NULL".$this->condition;
        $this->_getWriteAdapter()->query($updateQuery);
    }

    /**
     * Update shipped status to partial
     */
    protected function updateShipped()
    {
        $updateQuery = "UPDATE ".$this->getOrderGridTable()." grid
            SET shipped = IF(
                shipped = 1 OR shipped = 2,
                IF(total_qty_ordered_aggregated = total_qty_shipped OR
                    total_qty_ordered_aggregated - total_qty_canceled = total_qty_shipped OR
                    total_qty_ordered_aggregated - total_qty_canceled - total_qty_refunded = total_qty_shipped, 1, 2),
                0
            )
            WHERE 1".$this->condition;
        $this->_getWriteAdapter()->query($updateQuery);
    }
}