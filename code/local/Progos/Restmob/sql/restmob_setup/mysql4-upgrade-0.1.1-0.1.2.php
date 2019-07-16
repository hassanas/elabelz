<?php
$installer = $this;

$installer->startSetup();


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('sales_quote_restmob')};
CREATE TABLE {$this->getTable('sales_quote_restmob')} (
  `id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,  
  `qid` int(11) unsigned,
  `payemnt_method` VARCHAR(255),
  `shipping_method` VARCHAR(255),
  `payment_status` TINYINT(1),
  `status` TINYINT(1),
  `reserved_order_id` VARCHAR(255),
  `cc_cid` VARCHAR(255),
  `cc_owner` VARCHAR(255),
  `cc_number` VARCHAR(255),
  `cc_type` VARCHAR(255),
  `cc_exp_year` VARCHAR(255),
  `cc_exp_month` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->endSetup();
