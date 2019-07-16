<?php
$installer = $this;

$installer->startSetup();

$installer->run("
	ALTER TABLE `marketplace_commission` ADD COLUMN `commission_percentage` INT(3) NULL DEFAULT '0' AFTER `replacement`;
");

$installer->endSetup();