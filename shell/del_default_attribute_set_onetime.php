<?php
require 'app/Mage.php';
Mage::app('admin');

$setup = Mage::getModel('eav/entity_setup', 'core_setup');
$setup->startSetup();
$entityTypeId     = $setup->getEntityTypeId('catalog_category');
$attributeId = $setup->getAttributeId($entityTypeId, 'default_attribute_set');
$setup->run("
DELETE FROM `{$setup->getTable('catalog_category_entity_int')}`
WHERE `attribute_id` = '{$attributeId}';
");
$setup->removeAttribute('catalog_category', 'default_attribute_set');
$setup->endSetup();

echo "deleted";
exit;