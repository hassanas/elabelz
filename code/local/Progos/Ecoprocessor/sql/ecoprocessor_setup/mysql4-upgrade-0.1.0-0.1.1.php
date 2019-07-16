<?php
$installer = $this;
$installer->startSetup();

//if ($installer->getConnection()->tableColumnExists($installer->getTable('ecoprocessor/quote_index'), 'telr_resp_code') === false) {
    $installer->getConnection()
        ->addColumn($installer->getTable('ecoprocessor/quote_index'), 'telr_resp_code', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => false,
            'length' => 255,
            'after' => null, // column name to insert new column after
            'comment' => 'Response Code'
        ));
//}
$installer->endSetup();