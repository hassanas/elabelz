<?php
$installer = $this;

$installer->startSetup();

$installer->run("
		ALTER TABLE `{$this->getTable('marketplace_commission')}` ADD COLUMN `replacement` TINYINT NULL DEFAULT '0' AFTER `ship_status`;
");

$installer->endSetup();