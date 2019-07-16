<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('sales_quote_restmob'),'version_string', array(
        'type'      => "text",
        'nullable'  => false,
        'length'    => 255,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Version string for analytics'
    ));
$installer->endSetup();