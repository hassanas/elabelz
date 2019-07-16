<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('dhllabel'), 'type_2',
    'varchar(50) DEFAULT "shipment"'
);
$installer->endSetup();