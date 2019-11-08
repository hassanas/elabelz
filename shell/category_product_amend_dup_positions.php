<?php
ini_set('memory_limit', '-1');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('app/Mage.php'); //Path to Magento
umask(0);
Mage::app();

$allCategories = Mage::getModel('catalog/category');
$categoryTree = $allCategories->getTreeModel();
$categoryTree->load();
$categoryIds = $categoryTree->getCollection()->getAllIds();


/**
 * Get the resource model
 */
$resource = Mage::getSingleton('core/resource');

/**
 * Retrieve the read connection
 */
$readConnection = $resource->getConnection('core_read');

/**
 * Retrieve the write connection
 */
$writeConnection = $resource->getConnection('core_write');
$startTime = microtime(true);
foreach ($categoryIds as $catId) {
    $maxPosCols = $readConnection->fetchAll("SELECT MAX(position) AS max_pos FROM catalog_category_product where category_id = " . $catId);
    if (strlen($maxPosCols[0]['max_pos']) != 0) {
        Mage::log('Max position for category ' . $catId . ' is ' . $maxPosCols[0]['max_pos'], null, 'dup_pos.log', true);
        $dupProCols = $readConnection->fetchAll("SELECT * FROM catalog_category_product
            WHERE POSITION IN(SELECT t1.position FROM
            (SELECT
              `e`.*,
              `ccp`.`position`
            FROM
              `catalog_product_entity` AS `e`
              INNER JOIN `catalog_category_product` AS `ccp`
                ON ccp.product_id = e.entity_id
            WHERE (ccp.category_id = " . $catId . ")
              AND (e.type_id = 'configurable')) t1
              GROUP BY t1.position HAVING COUNT(*)  > 1)
            AND category_id  = " . $catId);
        $index = 1;
        foreach ($dupProCols as $each) {
            $pos = $index + $maxPosCols[0]['max_pos'];
            Mage::log('product ' . $each['product_id'] . ' is repositioned to ' . $pos . ' from position ' . $each['position'] . ' in category ' . $catId, null, 'dup_pos.log', true);
            $index++;
            $writeConnection->update($resource->getTableName('catalog/category_product'), array('position' => $pos), 'product_id=' . $each['product_id'] . ' AND category_id=' . $catId);
            $writeConnection->update($resource->getTableName('catalog/category_product_index'), array('position' => $pos), 'product_id=' . $each['product_id'] . ' AND category_id=' . $catId);

        }

    } else {
        Mage::log('Max position for category' . $catId . ' is null', null, 'no_max_pos.log', true);
    }

}


$endTime = (microtime(true) - $startTime) / 60;

echo PHP_EOL . "Time: {$endTime}  minutes\n";