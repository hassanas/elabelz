<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Install script for all the entities of comments and classification
 */
$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('progos_agentcomments/classification'))
    ->addColumn('class_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'auto_increment' => true,
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Classification Id')
    ->addColumn('class_title', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
    ), 'Classification Title');
$installer->getConnection()->createTable($table);

$table = $installer->getConnection()
    ->newTable($installer->getTable('progos_agentcomments/comments'))
    ->addColumn('comment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'auto_increment' => true,
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Comment Id')
    ->addColumn('comment', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
    ), 'Comment')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Customer Id')
    ->addColumn('admin_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Admin Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        "default" => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
    ), 'Date of creation');
$installer->getConnection()->createTable($table);

$setup = new Mage_Eav_Model_Entity_Setup ( 'core_setup' );
$setup->addAttribute ( 'customer', 'classification', array (
    'label' => 'Customer Rating',
    'visible' => true,
    'required' => false,
    'type' => 'varchar',
    'input' => 'select',
    'source' => 'progos_agentcomments/source_custom'
) );

$eavConfig = Mage::getSingleton ( 'eav/config' );
$attribute = $eavConfig->getAttribute ( 'customer', 'classification' );
$attribute->setData ( 'used_in_forms', array (
    'adminhtml_customer',
) );
$attribute->save ();
$installer->endSetup();