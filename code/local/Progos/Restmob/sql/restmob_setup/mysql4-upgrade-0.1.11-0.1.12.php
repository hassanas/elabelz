<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales_quote_restmob'),
        'shipping_customer_info',
        array(
            'type' => "text",
            'nullable' => true,
            'default' => null,
            'comment' => 'Shipping Address and customer info for soapless call'
        )
    );
$installer->endSetup();