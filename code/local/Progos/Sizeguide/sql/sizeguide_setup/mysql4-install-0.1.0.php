<?php
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();
 
$installer->run("-- DROP TABLE IF EXISTS {$this->getTable('sizeguide')};
CREATE TABLE IF NOT EXISTS {$this->getTable('sizeguide')} (
`sizeguide_id` INT UNSIGNED NOT NULL auto_increment,
`title` varchar(255) NOT NULL default '',
`name` varchar(255) NOT NULL default '',
`class` varchar(255)  NULL default '',
`description` text NULL default '',
`category_ids` varchar(255) NOT NULL default '' ,
`brand_ids` varchar(255) NOT NULL default '' ,
`template` varchar(255) NOT NULL default '' ,
`sizeguide_file` text NULL,
`sizestandard_file` text NULL,
`details` text NULL,
`store_id` varchar(255) NOT NULL default '',
`size_code` varchar(255) NOT NULL default '',
`created_date` datetime NOT NULL,
`status` smallint(6) NOT NULL default '0',
PRIMARY KEY (`sizeguide_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
 
$installer->endSetup();

