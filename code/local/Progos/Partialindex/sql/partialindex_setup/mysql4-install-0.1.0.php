<?php
$installer = $this;

$installer->startSetup();


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('catalog_product_partialindex')};
CREATE TABLE {$this->getTable('catalog_product_partialindex')} (
  `id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,  
  `product_id` int(11) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->endSetup();