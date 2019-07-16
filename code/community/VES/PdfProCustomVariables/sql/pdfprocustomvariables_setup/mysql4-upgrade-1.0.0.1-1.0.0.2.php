<?php 
$installer = $this;
$installer->startSetup();

$installer->run("
		ALTER TABLE `{$this->getTable('pdfprocustomvariables/pdfprocustomvariables')}` 
 		ADD `attribute_id_customer` int(11) unsigned not null AFTER `attribute_id`
		");

$installer->endSetup();
