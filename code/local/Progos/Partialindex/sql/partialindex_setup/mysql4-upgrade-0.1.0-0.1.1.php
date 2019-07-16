<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->dropColumn('catalog_product_partialindex', 'sort_order');
$installer->getConnection()->dropIndex('catalog_product_partialindex', 'product_id_UNIQUE');
$installer->run("
	ALTER TABLE `catalog_product_partialindex` 
	ADD COLUMN `sort_order` int(11) NOT NULL default '0'
	");
$installer->run("
	ALTER TABLE `catalog_product_partialindex` 
	ADD UNIQUE INDEX `product_id_UNIQUE` (`product_id` ASC)
	");
$installer->endSetup();