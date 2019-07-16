<?php
$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('manageimage')};
CREATE TABLE {$this->getTable('manageimage')} (
  `manageimage_id` int(11) unsigned NOT NULL auto_increment,
     `productname` varchar(255) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `sku` varchar(255) NOT NULL default '',
  PRIMARY KEY (`manageimage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 