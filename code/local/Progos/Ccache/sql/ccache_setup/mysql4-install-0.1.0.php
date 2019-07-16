<?php
$installer = $this;

$installer->startSetup();


$installer->run("
DROP TABLE IF EXISTS {$this->getTable('ccache')};
CREATE TABLE {$this->getTable('ccache')} (
  `id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,  
  `type_id` int(11) unsigned NOT NULL,
  `type` varchar(255),
  `count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$installer->endSetup();