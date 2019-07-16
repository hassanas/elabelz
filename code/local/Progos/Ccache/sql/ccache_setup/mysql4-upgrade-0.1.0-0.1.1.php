<?php
$installer = $this;

$installer->startSetup();


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('warmup_products')};
CREATE TABLE {$this->getTable('warmup_products')} (
  `id` int(11) NOT NULL auto_increment PRIMARY KEY,  
  `product_id` int(11)  NOT NULL,
  UNIQUE INDEX `product_id_UNIQUE` (`product_id` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('warmup_categories')};
CREATE TABLE {$this->getTable('warmup_categories')} (
  `id` int(11)  NOT NULL auto_increment PRIMARY KEY,  
  `category_id` int(11)  NOT NULL,
  UNIQUE INDEX `category_id_UNIQUE` (`category_id` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('warmup_manufacturers')};
CREATE TABLE {$this->getTable('warmup_manufacturers')} (
  `id` int(11)  NOT NULL auto_increment PRIMARY KEY,  
  `manufacturer_id` int(11)  NOT NULL,
  UNIQUE INDEX `manufacturer_id_UNIQUE` (`manufacturer_id` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->endSetup();