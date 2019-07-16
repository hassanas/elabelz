<?php

$installer = $this;

$installer->startSetup();
$installer->run("

ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `header_config` TEXT NOT NULL ;
ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `footer_config` TEXT NOT NULL ;

ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `term` TEXT  ;
ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `bar_code` TEXT  ;
ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `qr_code` TEXT  ;
ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `rtl` TEXT  ;
ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `font` TEXT  ;
ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}` ADD `language` TEXT  ;


ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `header_config` TEXT NOT NULL ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `footer_config` TEXT NOT NULL ;

ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `term` TEXT  ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `bar_code` TEXT  ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `qr_code` TEXT  ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `rtl` TEXT  ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `font` TEXT  ;
ALTER TABLE `{$this->getTable('pdfpro/key')}` ADD `language` TEXT  ;
");
$installer->endSetup();