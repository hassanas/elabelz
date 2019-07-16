<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
CREATE TABLE `newarrivals_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL COMMENT 'Category Id',
  `new_arrivals_category_id` int(10) unsigned NOT NULL COMMENT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_new_category` (`category_id`,`new_arrivals_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1

		
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 