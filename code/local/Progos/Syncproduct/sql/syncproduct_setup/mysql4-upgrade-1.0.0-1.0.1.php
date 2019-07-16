<?php

$installer = $this;

$installer->startSetup();
if (!$installer->tableExists('progos_syncproduct/syncproduct')) {
    $installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('syncproduct')};
CREATE TABLE {$this->getTable('syncproduct')} (
  `syncproduct_id` int(11) unsigned NOT NULL auto_increment,
  `sku` text NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`syncproduct_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");


}
$installer->endSetup();