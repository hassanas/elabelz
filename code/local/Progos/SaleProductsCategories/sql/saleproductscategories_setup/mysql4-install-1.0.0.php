<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
CREATE TABLE `sale_products_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL COMMENT 'Original Category Id',
  `sale_category_id` int(10) unsigned NOT NULL COMMENT 'Coresponding  category id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`category_id`,`sale_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1

		
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 