<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales_quote_restmob'),
        'sc_info',
        array(
            'type' => "text",
            'nullable' => true,
            'default' => null,
            'comment' => 'JSON array of storecredit details'
        )
    );
$installer->endSetup();