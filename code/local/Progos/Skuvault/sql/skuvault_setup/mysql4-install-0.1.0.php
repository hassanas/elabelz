<?php

$this->startSetup();
$this->getConnection()->addColumn($this->getTable('catalog/product'), 'skuvault_code_updated',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'default' => 0,
        'comment' => 'item updated on skuvault or not'
    ));
$this->endSetup();