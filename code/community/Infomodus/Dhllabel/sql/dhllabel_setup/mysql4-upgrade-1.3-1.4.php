<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('dhllabel'), 'type_print',
    'varchar(50) DEFAULT "pdf"'
);
$installer->endSetup();