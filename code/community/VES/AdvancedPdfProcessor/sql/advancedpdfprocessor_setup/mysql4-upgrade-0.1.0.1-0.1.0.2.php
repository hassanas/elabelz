<?php

$installer = $this;

$installer->startSetup();
$installer->run("

ALTER TABLE `{$this->getTable('advancedpdfprocessor/template')}`
ADD `order_template` TEXT NOT NULL AFTER `css_path`,
ADD `invoice_template` TEXT NOT NULL AFTER `order_template` ,
ADD `shipment_template` TEXT NOT NULL AFTER `invoice_template`,
ADD `creditmemo_template` TEXT NOT NULL AFTER `shipment_template`
");

$installer->endSetup();