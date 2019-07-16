<?php
$installer = $this;

/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('upslabel'), 'request',
    'TEXT DEFAULT ""'
);
$installer->getConnection()->addColumn($installer->getTable('upslabel'), 'response',
    'TEXT DEFAULT ""'
);

$installer->endSetup();

