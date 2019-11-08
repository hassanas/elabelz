<?php
/**
 * @author Progos
 * @copyright Copyright (c) 2016 progos(https://www.amasty.com)
 * @package None
 * Purpose is to insert the missing product urls for uk, us and international stores that were missed earlier
 */
ini_set('memory_limit','-1');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('app/Mage.php'); //Path to Magento
umask(0);
Mage::app();

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
/**
 * Execute the query and store the results in $results
 */
$results = $readConnection->fetchAll("SELECT t.entity_id, t.sku, GROUP_CONCAT( DISTINCT t.store_id SEPARATOR '%') AS `stores` FROM(
SELECT 
  cpe.entity_id, cpe.sku,cs.`store_id`
FROM
  catalog_product_entity cpe,
  core_store cs 
WHERE NOT EXISTS 
  (SELECT 
    * 
  FROM
    catalog_product_entity_varchar cpev 
  WHERE cpev.`entity_id` = cpe.`entity_id` 
    AND cpev.`store_id` = cs.store_id 
    AND cpev.`attribute_id` = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'url_key' AND entity_type_id = '4')) 
  AND cpe.`type_id` = 'configurable'
  AND cs.`store_id` IN (SELECT store_id FROM core_store WHERE `code` IN ('en_us','en_gb','en_int','en_uk')) 
  AND cpe.`entity_id` = 
  (SELECT 
    cpe1.entity_id 
  FROM
    catalog_product_entity_varchar cpev1,
    catalog_product_entity cpe1 
  WHERE cpe1.`entity_id` = cpev1.`entity_id` 
    AND cpev1.`attribute_id` = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'url_key' AND entity_type_id = '4') 
    AND cpev1.`store_id` = '0' 
    AND cpev1.value IS NULL 
    AND cpe1.entity_id = cpe.`entity_id` 
    AND cpe1.`type_id` = 'configurable')
    ) t
    GROUP BY t.entity_id");

$attributeCode = "url_key";
$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode)->getId();
$uaeStoreId = reset(Mage::getModel('core/store')->getCollection()->addFieldToFilter('code', array('eq' => 'en_ae'))->getData())['store_id'];
$fullQuery = "INSERT INTO catalog_product_entity_varchar(`entity_type_id`,`attribute_id`,`store_id`,`entity_id`,`value`) VALUES ";
$queryArray = array();
foreach ($results as $record) {
    foreach (explode("%", $record['stores']) as $each) {
        $uaeProduct = Mage::getModel('catalog/product')->setStoreId($uaeStoreId)->loadByAttribute('sku', $record['sku']);
        $newUrl = $uaeProduct->getData($attributeCode);
        if(empty($newUrl)){
            continue;
        }

        $query = "";

        $query = "('4' , '" . $attribute . "' , '" . $each . "' , '" . $record['entity_id'] . "' , '" . $newUrl . "')";
        $queryArray[] = $query;

    }
}
$fullQuery .= implode(" , ", $queryArray);
$writeConnection->query($fullQuery);
echo 'Done!'.PHP_EOL;
Mage::log($fullQuery, null, 'url.log');
