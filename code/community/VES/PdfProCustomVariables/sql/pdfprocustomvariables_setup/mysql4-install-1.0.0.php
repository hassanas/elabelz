<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('pdfprocustomvariables/pdfprocustomvariables')};
CREATE TABLE {$this->getTable('pdfprocustomvariables/pdfprocustomvariables')} (
  `custom_variable_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `variable_type` varchar(255) NOT NULL default '',
  `attribute_id` int(11) unsigned not null,
  `static_value` text not null,
  PRIMARY KEY (`custom_variable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 