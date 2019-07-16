<?php
$installer = $this;

$installer->startSetup();

$installer->run(
    "
    ALTER TABLE {$this->getTable('brand')} ADD `is_show_information_tab` TINYINT(1) NOT NULL DEFAULT 0;
	ALTER TABLE {$this->getTable('brand')} ADD `information_title` varchar(200) NULL default '';
	ALTER TABLE {$this->getTable('brand')} ADD `information_description` text NOT NULL default '';
    "
);
   
$installer->endSetup();