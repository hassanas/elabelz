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


class AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    /**
     * Not simple product types
     *
     * @var array
     */
    protected $_notSimple = array('configurable');
    protected $_storeIds = array();

    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function reInitSelect()
    {
        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('e' => $this->getEntity()->getEntityTable()),
            array()
        );
        return $this;
    }

    public function setDateFilter($from, $to)
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $this->getSelect()
            ->where("orders.{$filterField} >= ?", $from)
            ->where("orders.{$filterField} <= ?", $to);
        return $this;
    }

    protected function _getProcessStates()
    {
        $states = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $isFirst = true;
        $filter = "";
        foreach ($states as $state) {
            if (!$isFirst) {
                $filter .= " OR ";
            }
            $filter .= "orders.status = '" . $state . "'";
            $isFirst = false;
        }
        return "(" . $filter . ")";
    }

    public function setState()
    {
        $this->getSelect()
            ->where($this->_getProcessStates());

        return $this;
    }

    public function setStoreFilter($storeIds = array())
    {
        $this->_storeIds = $storeIds;
        $this->getSelect()->where("orders.store_id in ('" . implode("','", $storeIds) . "')");
        return $this;
    }

    public function getStoreIds()
    {
        return $this->_storeIds;
    }

    public function addOrderItems(
        $limit = 10, $dateFrom, $dateTo,
        $skuType = AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_SIMPLE,
        $isAllStores = false
    )
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $notSimple = "'" . implode("','", $this->_notSimple) . "'";

        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        if ($isAllStores) {
            $currencyRate = "orders.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("1");
        }
        if ($skuType == AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_SIMPLE) {
            $priceAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'price');
            $priceTable = $priceAttr->getBackendTable();

            $this->getSelect()
                ->join(
                    array('item' => $itemTable),
                    "(item.product_id = e.entity_id AND ((item.parent_item_id IS NULL
                    AND item.product_type NOT IN ({$notSimple})) OR (item.parent_item_id IS NOT NULL
                    AND item.product_type NOT IN ({$notSimple}))))",
                    array(
                        'product_id' => 'product_id',
                        'sum_qty' => 'COALESCE(SUM(item.qty_ordered), 0)',
                        'sum_total' => "(COALESCE(SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate),0)
                            + COALESCE(SUM(IFNULL(item2.base_hidden_tax_amount, item.base_hidden_tax_amount) * $currencyRate), 0.0000)
                            + COALESCE(SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount) * $currencyRate), 0)
                            + COALESCE(SUM(IFNULL(item2.base_tax_amount,item.base_tax_amount) * $currencyRate), 0)
                            - COALESCE(SUM(IFNULL(item2.base_discount_amount,item.base_discount_amount) * $currencyRate), 0))",
                        'name' => 'name',
                        'sku' => 'sku',
                    )
                )
                ->joinLeft(
                    array('item2' => $itemTable),
                    "(item.parent_item_id = item2.item_id AND item2.product_type IN ('configurable'))",
                    array()
                )
                ->join(
                    array('orders' => $orderTable), "orders.entity_id = item.order_id", array()
                )
                ->joinLeft(
                    array('item_price_def' => $priceTable),
                    "item_price_def.entity_id = item.product_id AND item_price_def.attribute_id = {$priceAttr->getId()} AND item_price_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('item_price_store' => $priceTable),
                    "item_price_store.entity_id = item.product_id AND item_price_store.attribute_id = {$priceAttr->getId()} AND item_price_store.store_id = orders.store_id",
                    array()
                )
                ->where("(item.product_type <> 'bundle' OR IFNULL(item_price_store.value, item_price_def.value) > 0)")
                ->where("(item.product_type <> 'configurable')")
            ;
        } else {
            $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
            $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
            $orderStatusList = implode("','", $orderStatusList);

            $storeIdsCondition = '1=1';
            if ($storeIds = $this->getStoreIds()) {
                $storeIdsCondition = "(t_order.store_id in ('" . implode("','", $storeIds) . "'))";
            }

            $productTable = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity');
            $this->getSelect()
                ->join(
                    array('item' => $itemTable),
                    "(item.product_id = e.entity_id AND item.parent_item_id IS NULL)",
                    array(
                        'product_id' => 'product_id',
                        'sum_qty' => 'COALESCE(SUM(item.qty_ordered), 0)',
                        'sum_total' => "(
                            COALESCE(SUM(item.base_row_total * $currencyRate),0)
                            + COALESCE(SUM(item.base_hidden_tax_amount * $currencyRate), 0.0000)
                            + COALESCE(SUM(item.base_weee_tax_applied_amount * $currencyRate), 0)
                            + COALESCE(SUM(item.base_tax_amount * $currencyRate), 0)
                            - SUM(COALESCE(t_discount.item_discount, item.base_discount_amount, 0) * $currencyRate)
                        )",
                        'name' => 'name',
                        'sku' => 'realP.sku',
                    )
                )
                ->joinLeft(
                    array('t_discount' => new Zend_Db_Expr(
                        "(SELECT IF(t_item.base_discount_amount = 0, SUM(t_item2.base_discount_amount), t_item.base_discount_amount) AS `item_discount`,
                        t_item.item_id AS `discount_item_id`
                        FROM {$orderTable} AS `t_order`
                        INNER JOIN {$itemTable} AS `t_item` ON (t_item.order_id = t_order.entity_id AND t_item.parent_item_id IS NULL)
                        INNER JOIN {$itemTable} AS `t_item2` ON (t_item2.order_id = t_order.entity_id AND t_item2.parent_item_id IS NOT NULL AND t_item2.parent_item_id = t_item.item_id AND t_item.product_type IN ('configurable', 'bundle'))
                        WHERE (t_order.{$filterField} >= '{$dateFrom}' AND t_order.{$filterField} <= '{$dateTo}') AND (t_order.status IN ('{$orderStatusList}'))
                        AND {$storeIdsCondition}
                        GROUP BY t_item.item_id)"
                    )),
                    'item.item_id = t_discount.discount_item_id',
                    array()
                )
                ->joinLeft(
                    array('realP' => $productTable),
                    "item.product_id = realP.entity_id",
                    array()
                )
                ->join(
                    array('orders' => $orderTable), "orders.entity_id = item.order_id", array()
                );
        }

        $this->getSelect()
            ->group('e.entity_id')
            ->limit($limit);
        return $this;
    }

    /**
     * Set up order by total
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function orderByTotal()
    {
        $this->getSelect()
            ->order('sum_total DESC');
        return $this;
    }

    /**
     * Set up order by quantitty
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function orderByQty()
    {
        $this->getSelect()
            ->order('sum_qty DESC');
        return $this;
    }

    /**
     * Set up profit columns for collection
     * ATTENTION: use this method only for collections with joined 'item' => 'sales_flat_order_item' table
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
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

        $skuTypeCondition = '1=1';
        $itemProductIdField = "IFNULL(item.product_id, item2.product_id)";
        $typeList = "'configurable'";
        $skuType = Mage::helper('advancedreports/setup')->getCustomConfig('advancedreports_bestsellers_options_skutype');
        if ($skuType == AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_GROUPED) {
            $itemProductIdField = "IFNULL(item2.product_id, item.product_id)";
            $skuTypeCondition =  "((item.product_type <> 'configurable' AND item2.product_type <> 'bundle') OR (item.product_type <> 'configurable' AND item2.product_type IS NULL))";
            $typeList = "'configurable', 'bundle'";
            $bundleChildCost = 'NULL';
        } else {
            // fills by zero bundle child cost for bundle products with fixed price (SKU of simple product mode)
            $bundleChildCost = "IF(parent_item.product_type='bundle' AND IFNULL(item2.base_row_total, item.base_row_total)=0, 0, NULL)";
        }

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        $storeIdsCondition = '1=1';
        if ($storeIds = $this->getStoreIds()) {
            $storeIdsCondition = "(order.store_id in ('" . implode("','", $storeIds) . "'))";
        }

        $this->getSelect()
            ->joinLeft(
                array('profit' => new Zend_Db_Expr(
                    "(SELECT (SUM(IFNULL(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_cost,NULL), {$bundleChildCost}, cost_store.value, cost_def.value, cost_parent_store.value, cost_parent_def.value) * $currencyRate, 0) * IFNULL(item.qty_ordered,item2.qty_ordered))) AS `total_cost`,
                    (
                        SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                        - SUM(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                        - (SUM(IFNULL(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_cost,NULL), {$bundleChildCost}, cost_store.value, cost_def.value, cost_parent_store.value, cost_parent_def.value) * $currencyRate, 0) * IFNULL(item.qty_ordered,item2.qty_ordered)))
                    ) AS `total_profit`,
                    (
                        100
                        * (
                            SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                            - SUM(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                            - (SUM(IFNULL(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_cost,NULL), {$bundleChildCost}, cost_store.value, cost_def.value, cost_parent_store.value, cost_parent_def.value) * $currencyRate, 0) * IFNULL(item.qty_ordered,item2.qty_ordered)))
                        )
                        / (
                            SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                            - SUM(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_discount_amount,NULL), {$bundleChildCost}, item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                        )
                    ) AS `total_margin`,
                    (
                        SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                        - SUM(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                    ) AS `total_revenue_excl_tax`,
                    (
                        SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate)
                        + SUM(IFNULL(item2.base_tax_amount, item.base_tax_amount) * $currencyRate)
                        + SUM(COALESCE(item2.base_hidden_tax_amount, item.base_hidden_tax_amount, 0) * $currencyRate)
                        + SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount) * $currencyRate)
                        - SUM(COALESCE(IF(bundle_item_cost.base_price>0,bundle_item_cost.base_discount_amount,NULL), item2.base_discount_amount, item.base_discount_amount) * $currencyRate)
                    ) AS `total_revenue`,
                    {$itemProductIdField} AS `item_product_id`
                    FROM {$itemTable} AS `item`
                    INNER JOIN {$orderTable} AS `order` ON order.entity_id = item.order_id
                    LEFT JOIN {$itemTable} AS `item2` ON order.entity_id = item2.order_id AND item2.item_id = item.parent_item_id AND item2.product_type IN ({$typeList})
                    LEFT JOIN (SELECT parent_item_id, SUM(COALESCE(cost_store.value, cost_def.value, 0)) AS base_cost, SUM(base_price) AS base_price, SUM(bundle_item.base_discount_amount) AS base_discount_amount
                        FROM {$itemTable} AS bundle_item
                        INNER JOIN {$orderTable} AS `order` ON order.entity_id = bundle_item.order_id
                        LEFT JOIN {$costTable} AS `cost_def` ON cost_def.entity_id = bundle_item.product_id AND cost_def.attribute_id = {$costAttr->getId()} AND cost_def.store_id = 0
                        LEFT JOIN {$costTable} AS `cost_store` ON cost_store.entity_id = bundle_item.product_id AND cost_store.attribute_id = {$costAttr->getId()} AND cost_store.store_id = order.store_id
                        WHERE (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))
                        GROUP BY parent_item_id) AS `bundle_item_cost` ON item.item_id = bundle_item_cost.parent_item_id
                    LEFT JOIN (SELECT item_id, product_type FROM {$itemTable}
                        INNER JOIN {$orderTable} AS `order` ON order.entity_id = {$itemTable}.order_id
                        WHERE parent_item_id IS NULL AND (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))) AS `parent_item`
                        ON item.parent_item_id = parent_item.item_id
                    LEFT JOIN {$costTable} AS `cost_def` ON cost_def.entity_id = item.product_id AND cost_def.attribute_id = {$costAttr->getId()} AND cost_def.store_id = 0
                    LEFT JOIN {$costTable} AS `cost_store` ON cost_store.entity_id = item.product_id AND cost_store.attribute_id = {$costAttr->getId()} AND cost_store.store_id = order.store_id
                    LEFT JOIN {$costTable} AS `cost_parent_def` ON cost_parent_def.entity_id = item2.product_id AND cost_parent_def.attribute_id = {$costAttr->getId()} AND cost_parent_def.store_id = 0
                    LEFT JOIN {$costTable} AS `cost_parent_store` ON cost_parent_store.entity_id = item2.product_id AND cost_parent_store.attribute_id = {$costAttr->getId()} AND cost_parent_store.store_id = order.store_id
                    WHERE {$skuTypeCondition} AND (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))
                    AND {$storeIdsCondition}
                    GROUP BY {$itemProductIdField} )"
                )),
                'item.product_id = profit.item_product_id',
                array(
                    'total_cost'             => "COALESCE(profit.total_cost, 0)",
                    'total_profit'           => "COALESCE(profit.total_profit, 0)",
                    'total_margin'           => "COALESCE(profit.total_margin, 0)",
                    'total_revenue_excl_tax' => "COALESCE(profit.total_revenue_excl_tax, 0)",
                    'total_revenue'          => "COALESCE(profit.total_revenue, 0)",
                )
            );

        return $this;
    }

    public function setSize($size)
    {
        $this->_totalRecords = $size;
        return $this;
    }
}