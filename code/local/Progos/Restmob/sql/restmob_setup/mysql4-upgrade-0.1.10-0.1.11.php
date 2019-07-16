<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales_quote_restmob'),
        'bin_discount',
        array(
            'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'nullable'  => false,
            'length'    => 2,
            'after'     => null, // column name to insert new column after
            'comment'   => 'BIN auto Discount'
        )
    );
$installer->endSetup();