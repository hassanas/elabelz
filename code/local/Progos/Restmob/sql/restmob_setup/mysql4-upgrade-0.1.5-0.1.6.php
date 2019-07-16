<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('sales_quote_restmob'),'cart_items', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Cart items in case of empty cart'
    ));
$installer->endSetup();