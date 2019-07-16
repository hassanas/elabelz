<?php

$installer = $this;

$installer->startSetup();
$installer->run("

ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `custom1_template` TEXT NOT NULL AFTER `creditmemo_template` ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `custom2_template` TEXT NOT NULL AFTER `custom1_template` ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `custom3_template` TEXT NOT NULL AFTER `custom2_template` ;
");
$installer->endSetup(); 
