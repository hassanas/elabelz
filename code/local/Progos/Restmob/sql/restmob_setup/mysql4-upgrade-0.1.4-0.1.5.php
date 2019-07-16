<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('sales_quote_restmob'),'nexmo_status', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => false,
        'length'    => 2,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Nexmo Status'
    ));


//Order id returned when cron executes so we can update the nexmo status based on original order id returned from cron
$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales_quote_restmob'),
        'real_order_id',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'nullable' => true,
            'default' => null,
            'comment' => 'Real order id after order placed'
        )
    );
$installer->endSetup();