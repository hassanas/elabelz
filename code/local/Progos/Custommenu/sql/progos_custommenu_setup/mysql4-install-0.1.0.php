<?php
$installer = $this;

$installer->startSetup();


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('progos_custommenu_menu')};
CREATE TABLE {$this->getTable('progos_custommenu_menu')} (
  `id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,  
  `categories_en` TEXT(40000000) NULL,
  `categories_ar` TEXT(40000000) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$installer->endSetup();