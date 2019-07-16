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


class AW_Advancedreports_Model_Mysql4_Collection_Stockvssold extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    protected $_currentStoreId = null;

    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Stockvssold
     */
    public function reInitSelect()
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();

        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                'order_created_at'   => $filterField,
                'order_id'           => 'entity_id',
                'order_increment_id' => 'increment_id',
            )
        );

        return $this;
    }

    public function addProductInfo()
    {
        $stockTable = Mage::helper('advancedreports/sql')->getTable('cataloginventory_stock_item');
        $productEntityDecimalTable = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity_decimal');

        $costAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'cost');
        $costAttributeId = $costAttribute->getId();

        $priceAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'price');
        $priceAttributeId = $priceAttribute->getId();

        $storeId = $this->getCurrentStoreId();

        $this->getSelect()
            ->joinLeft(
                array('stock' => $stockTable),
                "stock.product_id = item.product_id",
                array('item_qty' => 'COALESCE(stock.qty, 0)')
            )
            ->joinLeft(
                array('costTableDef' => $productEntityDecimalTable),
                "costTableDef.entity_id = item.product_id AND costTableDef.attribute_id = {$costAttributeId} AND costTableDef.store_id = 0",
                array()
            )
            ->joinLeft(
                array('costTableStore' => $productEntityDecimalTable),
                "costTableStore.entity_id = item.product_id AND costTableStore.attribute_id = {$costAttributeId} AND costTableStore.store_id = {$storeId}",
                array()
            )
            ->columns(array('cost' => 'COALESCE(IFNULL(costTableStore.value, costTableDef.value), 0)'))
            ->joinLeft(
                array('priceTableDef' => $productEntityDecimalTable),
                "priceTableDef.entity_id = IFNULL(item2.product_id, item.product_id) AND priceTableDef.attribute_id = {$priceAttributeId} AND priceTableDef.store_id = 0",
                array()
            )
            ->joinLeft(
                array('priceTableStore' => $productEntityDecimalTable),
                "priceTableStore.entity_id = IFNULL(item2.product_id, item.product_id) AND priceTableStore.attribute_id = {$priceAttributeId} AND priceTableStore.store_id = {$storeId}",
                array()
            )
            ->columns(array('price' => 'COALESCE(IFNULL(priceTableStore.value, priceTableDef.value), 0)'))
        ;
        return $this;
    }

    /**
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Stockvssold
     */
    public function addOrderItems($from, $to, $isAllStores = false)
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $refundTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_creditmemo_item');
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        if ($isAllStores) {
            $currencyRate = "main_table.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("1");
        }

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                'main_table.entity_id = item.order_id',
                array(
                    'sum_qty'      => 'COALESCE(SUM(IFNULL(item2.qty_ordered, item.qty_ordered)), 0)',
                    'sum_total'    => "COALESCE(SUM(IFNULL(item2.base_row_total, item.base_row_total) * $currencyRate), 0)
                                      + COALESCE(SUM(IFNULL(item2.base_hidden_tax_amount, item.base_hidden_tax_amount) * $currencyRate), 0.0000)
                                      + COALESCE(SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount) * $currencyRate), 0)
                                      + COALESCE(SUM(IFNULL(item2.base_tax_amount,item.base_tax_amount) * $currencyRate), 0)
                                      - COALESCE(SUM(IFNULL(item2.base_discount_amount,item.base_discount_amount) * $currencyRate), 0)
                                        ",
                    'sum_invoiced' => "COALESCE(SUM(IFNULL(item2.base_row_invoiced, item.base_row_invoiced) * $currencyRate), 0)
                                        + COALESCE(SUM(IFNULL(item2.base_hidden_tax_invoiced, item.base_hidden_tax_invoiced) * $currencyRate),0)
                                        + COALESCE(SUM(IFNULL(item2.base_tax_invoiced, item.base_tax_invoiced) * $currencyRate), 0)
                                        - COALESCE(SUM(IFNULL(item2.base_discount_invoiced, item.base_discount_invoiced) * $currencyRate), 0)",
                    'item_id'      => 'item.item_id',
                    'name'         => 'item.name',
                    'sku'          => 'item.sku',
                    'product_id'   => 'item.product_id',
                    'product_type' => 'item.product_type',
                    'product_options' => 'item.product_options'
                )
            )
            ->joinLeft(
                array('item2' => $itemTable),
                "(main_table.entity_id = item2.order_id AND item.parent_item_id IS NOT NULL AND item.parent_item_id = item2.item_id AND item2.product_type = 'configurable')",
                array()
            )
            ->joinLeft(
                array('refund' => new Zend_Db_Expr(
                    "(SELECT order_item_id, sum(qty) as qty, sum(base_row_total_incl_tax) as base_row_total_incl_tax
                    FROM {$refundTable}
                    WHERE order_item_id IN
                        (SELECT `oi`.`item_id`
                        FROM {$itemTable} as `oi`
                        INNER JOIN {$orderTable} as `order` ON `oi`.`order_id` = `order`.`entity_id`
                        WHERE `order`.{$filterField} >= '{$from}' AND `order`.{$filterField} <= '{$to}' AND `order`.`status` IN ('{$orderStatusList}'))
                    GROUP BY order_item_id)"
                )),
                'refund.order_item_id = item.item_id',
                array('sum_refunded' => "COALESCE(SUM(refund.base_row_total_incl_tax * $currencyRate), 0)",)
            )
            ->where("(item.product_type <> 'bundle' OR IFNULL(priceTableStore.value, priceTableDef.value) > 0)")
            ->where("(item.product_type <> 'configurable')")
            ->group('item.product_id')
        ;

        return $this;
    }

    public function addEstimationThreshold($days)
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();

        //current date with timezone
        $date = new Zend_Date(null, null, Mage::app()->getLocale()->getLocaleCode());
        $date->setHour(23)->setMinute(59)->setSecond(59);

        $timeZone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        $date->setTimezone($timeZone);
        $dateTo = $date->toString('yyyy-MM-dd HH:mm:ss');

        $date->sub($days, Zend_Date::DAY);
        $dateFrom = $date->toString('yyyy-MM-dd HH:mm:ss');

        $gmtDiff = $date->get(Zend_Date::GMT_DIFF_SEP);

        $this->getSelect()
            ->joinLeft(
                new Zend_Db_Expr(
                    "(SELECT SUM(IFNULL(t_item2.qty_ordered, t_item.qty_ordered)) AS `sum_qty`,
                    t_item.product_id AS `item_product_id`
                    FROM {$orderTable} as `order`
                    INNER JOIN {$itemTable} AS `t_item` ON order.entity_id = t_item.order_id
                    LEFT JOIN {$itemTable} AS `t_item2` ON order.entity_id = t_item2.order_id AND t_item.parent_item_id IS NOT NULL AND t_item.parent_item_id = t_item2.item_id AND t_item2.product_type = 'configurable'
                    WHERE (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}')
                    GROUP BY t_item.product_id)"
                ),
                "item.product_id = t.item_product_id ",
                array(
                    'esitmation_data' => "IF ((COALESCE(t.sum_qty, 0)/{$days} > 0),
                    DATE_FORMAT(
                        ADDDATE(CONVERT_TZ(NOW(), @@global.time_zone, '{$gmtDiff}'), (COALESCE(stock.qty, 0)/(COALESCE(t.sum_qty, 0)/{$days}))),
                        '%Y-%m-%d'
                        ),
                    DATE_FORMAT(CONVERT_TZ(NOW(), @@global.time_zone, '{$gmtDiff}'),'%Y-%m-%d'))",
                )
            );
    }

    public function getCurrentStoreId()
    {
        if (is_null($this->_currentStoreId)) {
            if (Mage::app()->getRequest()->getParam('store')) {
                $store = Mage::app()->getRequest()->getParam('store');
                $this->_currentStoreId = Mage::app()->getStore($store)->getId();
            } else if (Mage::app()->getRequest()->getParam('website')){
                $website = Mage::app()->getRequest()->getParam('website');
                $storeIds = Mage::app()->getWebsite($website)->getStoreIds();
                $this->_currentStoreId = reset($storeIds);
            } else if (Mage::app()->getRequest()->getParam('group')){
                $group = Mage::app()->getRequest()->getParam('group');
                $storeIds =  Mage::app()->getGroup($group)->getWebsite()->getStoreIds();
                $this->_currentStoreId = reset($storeIds);
            } else {
                $this->_currentStoreId = Mage::app()->getStore()->getId();
            }
        }
        return $this->_currentStoreId;
    }
}