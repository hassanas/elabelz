<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('sales_quote_restmob'),'is_billing', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => false,
        'length'    => 2,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Different billing address?'
    ));


$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales_quote_restmob'),
        'billing_address',
        array(
            'type' => "text",
            'nullable' => true,
            'default' => null,
            'comment' => 'JSON array in case of different billing address'
        )
    );
$installer->endSetup();