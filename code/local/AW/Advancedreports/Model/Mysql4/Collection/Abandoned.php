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


class AW_Advancedreports_Model_Mysql4_Collection_Abandoned extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function reInitSelect($from, $to, $storeIds)
    {
        $isAllStores = empty($storeIds);
        $orderStores = "1=1";
        $quoteStores = "1=1";
        if ($isAllStores) {
            $currencyRate = "quote_abandoned.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("1");
            if (is_array($storeIds)) {
                $strStores = implode(',', $storeIds);
                $orderStores = "`order`.store_id IN ({$strStores})";
                $quoteStores = "quote_abandoned.store_id IN ({$strStores})";
            }
        }

        $quoteTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_quote');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => new Zend_Db_Expr(
            "(SELECT entity_id AS total_carts, NULL AS quote_completed, entity_id as quote_abandoned, quote_abandoned.base_grand_total * {$currencyRate} AS abandoned_carts_total
                FROM {$quoteTable} AS `quote_abandoned`
                WHERE (quote_abandoned.is_active = 1) AND (quote_abandoned.items_count > 0) AND (quote_abandoned.updated_at >= '{$from}') AND (quote_abandoned.updated_at <= '{$to}')
                AND ({$quoteStores})
             UNION
             SELECT quote_id AS total_carts, quote_id as quote_completed, NULL AS quote_abandoned, 0 AS abandoned_carts_total FROM {$orderTable} AS `order`
                WHERE (`order`.created_at >= '{$from}') AND (`order`.created_at <= '{$to}') AND ({$orderStores}))"
            )),
            array(
                'total_carts'               => "COALESCE(COUNT(total_carts), 0)",
                'completed_carts'           => "COALESCE(COUNT(quote_completed), 0)",
                'abandoned_carts'           => "COALESCE(COUNT(quote_abandoned), 0)",
                'abandoned_carts_total'     => "COALESCE(SUM(abandoned_carts_total), 0)",
                'abandonment_rate'          => "COALESCE((100 / COUNT(total_carts)) * COUNT(quote_abandoned), 0)",
            )
        );

        return $this;
    }

    /**
     * Set up date filter to collection of grid
     *
     * @param Datetime $from
     * @param Datetime $to
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Abstract
     */
    public function setDateFilter($from, $to)
    {
        $this->_from = $from;
        $this->_to = $to;
        return $this;
    }
}