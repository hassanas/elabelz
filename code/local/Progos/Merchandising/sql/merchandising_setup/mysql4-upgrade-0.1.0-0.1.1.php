<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('progos_merchandising/positions'),
        'merchandised_at',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => true,
            'default' => null,
            'comment' => 'Merchandised At'
        )
    );

$installer->endSetup();