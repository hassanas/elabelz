<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales_quote_restmob'),
        'telr_reference_id',
        array(
            'type' => "text",
            'nullable' => true,
            'default' => null,
            'comment' => 'Telr transaction reference id'
        )
    );
$installer->endSetup();