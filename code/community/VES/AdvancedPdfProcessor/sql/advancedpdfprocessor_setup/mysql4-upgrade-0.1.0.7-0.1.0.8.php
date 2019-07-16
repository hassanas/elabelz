<?php

$installer = $this;

$installer->startSetup();
$installer->run("


ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `water_type` SMALLINT NULL ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `water_image` TEXT NULL ;

ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `water_text` VARCHAR(255) NULL;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `water_alpha` VARCHAR(255) NULL;
");
$installer->endSetup();