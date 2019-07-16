<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Merchandising
 */
$installer = $this;

$installer->startSetup();


 $table = $installer->getConnection()
     ->newTable($installer->getTable('progos_merchandising/positions'))
     ->addColumn('position_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
         'auto_increment' => true,
         'identity'  => true,
         'unsigned'  => true,
         'nullable'  => false,
         'primary'   => true,
     ), 'Position Id')
     ->addColumn('category_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
         'unsigned'  => true,
         'nullable'  => false,
     ), 'Category Id')
     ->addColumn('positions', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
         'nullable'  => false,
     ), 'Products Position Collection')
     ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
         'nullable' => false,
         'default' => 1,
     ), 'Is Active?');
 $installer->getConnection()->createTable($table);
$installer->endSetup();