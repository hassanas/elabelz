<?php 
$installer = $this;
$installer->startSetup();

$installer->run("
		ALTER TABLE `{$this->getTable('pdfprocustomvariables/pdfprocustomvariables')}` 
 		MODIFY `name` varchar(30) NOT NULL default ''
		");

$installer->endSetup();
