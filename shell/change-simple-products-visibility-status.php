<?php
/**
 * @author Progos
 * @copyright Copyright (c) 2016 progos(https://www.amasty.com)
 * @package None
 * Purpose is to make the visibility status of all simple products to not visible individually
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
 * Retrieve the write connection
 */
$writeConnection = $resource->getConnection('core_write');
$fullQuery = "UPDATE catalog_product_entity_int SET value = 1 WHERE
attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'visibility')
AND
entity_id in (SELECT entity_id FROM catalog_product_entity where type_id='simple')";
$writeConnection->query($fullQuery);
echo 'Done!'.PHP_EOL;
