<?php
$installer = $this;

/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('upslabel'), 'track_status_code',
    'VARCHAR(20) DEFAULT \'-1\''
);

$installer->endSetup();

