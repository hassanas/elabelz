<?php
$installer = $this;

$installer->startSetup();


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('sales_quote_ecoprocessor')};
CREATE TABLE {$this->getTable('sales_quote_ecoprocessor')} (
  `id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,  
  `qid` int(11) unsigned,
  `payemnt_method` VARCHAR(255),
  `telr_reference_id` VARCHAR(255),
  `real_order_id` INT(11),
  `shipping_method` VARCHAR(255),
  `payment_status` TINYINT(1),
  `status` TINYINT(1),
  `reserved_order_id` VARCHAR(255),
  `cc_cid` VARCHAR(255),
  `cc_owner` VARCHAR(255),
  `cc_number` VARCHAR(255),
  `cc_type` VARCHAR(255),
  `cc_exp_year` VARCHAR(255),
  `cc_exp_month` VARCHAR(255),
  `created_at` timestamp,
  `updated_at` timestamp,
  `cart_items` text,
  `store_credit` INT(11),
  `billing_address` text,
  `sc_info` text,
  `shipping_address` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->endSetup();
