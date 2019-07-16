<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancedreports
 * @version    2.7.3
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancedreports_Model_Mysql4_Collection_Product extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function reInitSelect()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(array('main_table' => $orderTable), array());
        return $this;
    }

    /**
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function addItems($joinParentItem = false, $dateFrom, $dateTo, $isAllStores = false)
    {
        if ($isAllStores) {
            $currencyRate = "main_table.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("1");
        }

        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $_joinCondition = "main_table.entity_id = item.order_id AND item.parent_item_id IS NULL";
        if (true === $joinParentItem) {
            $_joinCondition = "main_table.entity_id = item.order_id";
        }

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        $storeIdsCondition = '1=1';
        if ($storeIds = $this->getStoreIds()) {
            $storeIdsCondition = "(t_order.store_id in ('" . implode("','", $storeIds) . "'))";
        }

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                $_joinCondition,
                array(
                    'sum_qty'         => 'SUM(item.qty_ordered)',
                    'sum_total'       => "SUM((item.base_row_total - COALESCE((t_discount.item_discount),0) + item.base_tax_amount) * $currencyRate)",
                    'name'            => 'name', 'sku' => 'sku',
                    'item_product_id' => 'item.product_id',
                    'product_type'    => 'item.product_type',
                    'product_options' => 'item.product_options',
                )
            )
            ->joinLeft(
                array('t_discount' => new Zend_Db_Expr(
                    "(SELECT IF(t_item.base_discount_amount = 0, SUM(t_item2.base_discount_amount), t_item.base_discount_amount) AS `item_discount`,
                        t_item.item_id AS `discount_item_id`
                        FROM {$orderTable} AS `t_order`
                        INNER JOIN {$itemTable} AS `t_item` ON (t_item.order_id = t_order.entity_id AND t_item.parent_item_id IS NULL)
                        LEFT JOIN {$itemTable} AS `t_item2` ON (t_item2.order_id = t_order.entity_id AND t_item2.parent_item_id IS NOT NULL AND t_item2.parent_item_id = t_item.item_id AND t_item.product_type IN ('configurable', 'bundle'))
                        WHERE (t_order.{$filterField} >= '{$dateFrom}' AND t_order.{$filterField} <= '{$dateTo}') AND (t_order.status IN ('{$orderStatusList}'))
                        AND {$storeIdsCondition}
                        GROUP BY t_item.item_id)"
                )),
                'item.item_id = t_discount.discount_item_id',
                array()
            )
            ->group('item.product_id')
            ->group('item.order_id')
            //->group('item.sku')
        ;
        if (true === $joinParentItem) {
            $this->getSelect()
                ->joinLeft(
                    array('item_parent' => $itemTable),
                    "main_table.entity_id = item_parent.order_id AND item.parent_item_id = item_parent.item_id",
                    array(
                        'parent_sum_total'  => "(item_parent.base_row_total - item_parent.base_discount_amount + item_parent.base_tax_amount) * $currencyRate",
                        'parent_product_id' => 'item_parent.product_id',
                    )
                )
                //->group('item_parent.sku')
            ;
        }
        return $this;
    }

    public function addProfitInfo($dateFrom, $dateTo, $isAllStores = false)
    {
        if ($isAllStores) {
            $currencyRate = "order.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("1");
        }

        $costAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'cost');
        $costTable = $costAttr->getBackendTable();
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        $groupBy = 'item.product_id';
        $additionalJoinCondition = '1=1';
        $skuTypeCondition = '1=1';
        $storeIdsCondition = '1=1';
        $itemProductIdField = "IFNULL(item.product_id, item2.product_id)";
        $typeList = "'configurable'";

        if ($storeIds = $this->getStoreIds()) {
            $storeIdsCondition = "(order.store_id in ('" . implode("','", $storeIds) . "'))";
        }

        $itemProductIdField = "item.product_id";
        $groupBy = 'item.item_id, item.order_id';
        $additionalJoinCondition = 'item.order_id = t.item_order_id ';

        $this->getSelect()
            ->joinLeft(
                new Zend_Db_Expr(
                    "(SELECT ((IFNULL(COALESCE(IF(item.product_type ='bundle' AND (bundle_item.base_price)<=0, NULL,
                        IF(item.product_type ='bundle', COALESCE(SUM(child_cost_store.value), SUM(child_cost_def.value), 0), IFNULL(SUM(child_cost_store.value), SUM(child_cost_def.value)))),
                        cost_store.value, cost_def.value, cost_parent_store.value, cost_parent_def.value) * $currencyRate, 0)) * (IFNULL(item.qty_ordered,item2.qty_ordered))
                    ) AS `total_cost`,
                    (
                        (IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                        - (COALESCE(IF(item.product_type ='bundle' AND bundle_item.base_price>0,bundle_item.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                        - ((IFNULL(COALESCE(IF(item.product_type ='bundle' AND (bundle_item.base_price)<=0, NULL, IF(item.product_type ='bundle', COALESCE(SUM(child_cost_store.value), SUM(child_cost_def.value), 0),
                           IFNULL(SUM(child_cost_store.value), SUM(child_cost_def.value)))), cost_store.value, cost_def.value, cost_parent_store.value, cost_parent_def.value) * $currencyRate, 0)) * (IFNULL(item.qty_ordered,item2.qty_ordered)))
                    ) AS `total_profit`,
                    (
                        100
                        * (
                            (IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                            - (COALESCE(IF(item.product_type ='bundle' AND bundle_item.base_price>0,bundle_item.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                            - ((IFNULL(COALESCE(IF(item.product_type ='bundle' AND (bundle_item.base_price)<=0, NULL, IF(item.product_type ='bundle', COALESCE(SUM(child_cost_store.value), SUM(child_cost_def.value), 0),
                                IFNULL(SUM(child_cost_store.value), SUM(child_cost_def.value)))), cost_store.value, cost_def.value, cost_parent_store.value, cost_parent_def.value) * $currencyRate, 0)) * (IFNULL(item.qty_ordered,item2.qty_ordered)))
                        )
                        / (
                            (IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                            - (COALESCE(IF(item.product_type ='bundle' AND bundle_item.base_price>0,bundle_item.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                        )
                    ) AS `total_margin`,
                    (
                        (IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                        - (COALESCE(IF(item.product_type ='bundle' AND bundle_item.base_price>0,bundle_item.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                    ) AS `total_revenue_excl_tax`,
                    (
                        (IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                        + (IFNULL(item2.base_tax_amount, item.base_tax_amount) * $currencyRate)
                        + (COALESCE(item2.base_hidden_tax_amount, item.base_hidden_tax_amount, 0) * $currencyRate)
                        + (IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount) * $currencyRate)
                        - (COALESCE(IF(item.product_type ='bundle' AND bundle_item.base_price>0,bundle_item.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                    ) AS `total_revenue`,
                    {$itemProductIdField} AS `item_product_id`,
                    item.order_id AS `item_order_id`,
                    item.parent_item_id AS `parent_item_id`,
                    item.item_id AS `item_id`
                    FROM {$itemTable} AS `item`
                    INNER JOIN {$orderTable} AS `order` ON order.entity_id = item.order_id
                    LEFT JOIN {$itemTable} AS `item2` ON order.entity_id = item2.order_id AND item2.item_id = item.parent_item_id AND item2.product_type IN ({$typeList})
                    LEFT JOIN {$itemTable} AS `child_item` ON order.entity_id = child_item.order_id AND child_item.parent_item_id = item.item_id
                    LEFT JOIN (SELECT parent_item_id, SUM(base_price) AS base_price, SUM(orderItem.base_discount_amount) AS base_discount_amount FROM {$itemTable} AS orderItem
                        INNER JOIN {$orderTable} AS `order` ON order.entity_id = orderItem.order_id
                        WHERE (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))
                        GROUP BY parent_item_id) AS `bundle_item` ON bundle_item.parent_item_id = item.item_id
                    LEFT JOIN {$costTable} AS `cost_def` ON cost_def.entity_id = item.product_id AND cost_def.attribute_id = {$costAttr->getId()} AND cost_def.store_id = 0
                    LEFT JOIN {$costTable} AS `cost_store` ON cost_store.entity_id = item.product_id AND cost_store.attribute_id = {$costAttr->getId()} AND cost_store.store_id = order.store_id
                    LEFT JOIN {$costTable} AS `child_cost_def` ON child_cost_def.entity_id = child_item.product_id AND child_cost_def.attribute_id = {$costAttr->getId()} AND child_cost_def.store_id = 0
                    LEFT JOIN {$costTable} AS `child_cost_store` ON child_cost_store.entity_id = child_item.product_id AND child_cost_store.attribute_id = {$costAttr->getId()} AND child_cost_store.store_id = order.store_id
                    LEFT JOIN {$costTable} AS `cost_parent_def` ON cost_parent_def.entity_id = item2.product_id AND cost_parent_def.attribute_id = {$costAttr->getId()} AND cost_parent_def.store_id = 0
                    LEFT JOIN {$costTable} AS `cost_parent_store` ON cost_parent_store.entity_id = item2.product_id AND cost_parent_store.attribute_id = {$costAttr->getId()} AND cost_parent_store.store_id = order.store_id
                    WHERE  {$skuTypeCondition} AND (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))
                    AND {$storeIdsCondition}
                    GROUP BY {$groupBy})"
                ),
                "item.item_id = t.item_id AND {$additionalJoinCondition}",
                array(
                    'total_cost'             => "SUM(COALESCE(t.total_cost, 0))",
                    'total_profit'           => "SUM(COALESCE(t.total_profit, 0))",
                    'total_margin'           => "SUM(COALESCE(t.total_margin, 0))",
                    'total_revenue_excl_tax' => "SUM(COALESCE(t.total_revenue_excl_tax, 0))",
                    'total_revenue'          => "SUM(COALESCE(t.total_revenue, 0))",
                )
            );

        return $this;
    }
}