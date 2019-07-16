<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_Clear
{
    public function process($limit = 250000) {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $urlRewriteTable = $resource->getTableName('core/url_rewrite');

        $helper = Mage::helper('safemage_urloptimization');
        $clearMode = $helper->getClearMode();

        if ($helper->removeOnlyWithDigit()) {
            $whereWithDigit = "AND  `request_path` REGEXP  '^.+(-)[0-9]+(.html|.htm)$'";
        } else {
            $whereWithDigit = '';
        }

        if ($clearMode == SafeMage_UrlOptimization_Model_System_Config_Source_Mode::REMOVE_ALL_BUT_LASTEST) {
            $keepRedirectQty = $helper->getKeepRedirectQty();
            $stmt = $connection->query("DELETE rewrite_tbl4
                FROM `". $urlRewriteTable ."` AS rewrite_tbl4,
                (SELECT rewrite_tbl2.`url_rewrite_id`,
                IF(url_number = @curret_url_number, @redirect_number:=@redirect_number + 1, @redirect_number:=1) AS redirect_number,
                IF(url_number = @curret_url_number, 0, @curret_url_number:=url_number) AS curret_url_changed,
                (IF(count - " . $keepRedirectQty . " >= @redirect_number, 1, 0)) AS to_delete
                FROM `". $urlRewriteTable ."` AS rewrite_tbl2
                INNER JOIN
                (SELECT target_path, store_id, COUNT(*) as count,
                (@url_number:=@url_number + 1) AS url_number
                FROM `". $urlRewriteTable ."`, (select @url_number:=0) AS url_number_tbl1
                WHERE `is_system` = 0
                    AND `options` = 'RP'
                    AND (`product_id` > 0 OR `category_id` > 0)
                    ". $whereWithDigit ."
                GROUP BY `target_path`, `store_id`
                HAVING count > " . $keepRedirectQty . "
                LIMIT " . $limit . ") AS rewrite_tbl1
                ON rewrite_tbl2.`target_path` = rewrite_tbl1.`target_path`
                    AND rewrite_tbl2.`store_id` = rewrite_tbl1.`store_id`
                    AND rewrite_tbl2.`is_system` = 0,
                (select @curret_url_number:=0, @redirect_number:=0) AS url_number_tbl2
                LIMIT " . $limit . ") AS rewrite_tbl3
                WHERE rewrite_tbl3.`url_rewrite_id` = rewrite_tbl4.`url_rewrite_id` AND rewrite_tbl3.to_delete = 1;");
            $result = $stmt->rowCount();
        } elseif ($clearMode == SafeMage_UrlOptimization_Model_System_Config_Source_Mode::REMOVE_ALL_BUT_VISITED) {
            $result = 0;
            $stores = Mage::app()->getStores();
            foreach($stores as $store) {
                $storeId = (int)$store->getId();
                $stmt = $connection->query("DELETE FROM `". $urlRewriteTable ."`
                WHERE
                    NOT EXISTS (SELECT `url_id` FROM `". $resource->getTableName('log/url_info_table') ."`
                        WHERE `request_path` = REPLACE(`url`, '". Mage::app()->getStore($storeId)->getBaseUrl() ."', '')
                            OR `request_path` = REPLACE(`referer`, '". Mage::app()->getStore($storeId)->getBaseUrl() ."', '')
                    )
                    AND `is_system` = 0
                    AND `options` = 'RP'
                    AND `store_id` = ". $storeId ."
                    AND (`product_id` > 0 OR `category_id` > 0)
                    ". $whereWithDigit ."
                LIMIT " . $limit . ";");
                $result += $stmt->rowCount();
                if ($result >= $limit) {
                    return $result;
                }
            }
        } else {
            $stmt = $connection->query("DELETE FROM `". $urlRewriteTable ."`
                WHERE `is_system` = 0
                    AND `options` = 'RP'
                    AND (`product_id` > 0 OR `category_id` > 0)
                    ". $whereWithDigit ."
                LIMIT " . $limit . ";");
            $result = $stmt->rowCount();
        }
        return $result;
    }
}
