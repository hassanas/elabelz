<?php
$installer = $this;

$installer->startSetup();

$installer->run(
    "
    ALTER TABLE {$this->getTable('brand')} ADD `is_upcoming` TINYINT(1) NOT NULL DEFAULT 0;
    "
);
   
$installer->endSetup();