<?php

$installer = $this;

$installer->startSetup();
 
 
$installer->run("

ALTER TABLE {$this->getTable('cms_page')}
ADD COLUMN `meta_title` varchar(255) NULL

");
 

$installer->endSetup(); 