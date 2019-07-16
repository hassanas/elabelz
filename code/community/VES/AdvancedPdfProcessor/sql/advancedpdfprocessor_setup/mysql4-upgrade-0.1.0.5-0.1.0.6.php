<?php

$installer = $this;

$installer->startSetup();
$installer->run("

ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `config_data` TEXT NOT NULL AFTER `css_path` ;

ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `config_data` TEXT NOT NULL AFTER `custom3_template` ;

");
$installer->endSetup();