<?php
$installer = $this;

/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('upslabel'), 'international_invoice',
    'INT(3) DEFAULT 0'
);

$installer->endSetup();

