<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Saroop Chand <saroop.chand@progos.org> 14-06-2018
 * @copyright   Copyright (c) 2018 Progos, Ltd (http://progos.org)
 */
$installer = $this;
$installer->startSetup();

$tablePrefix = Mage::getConfig()->getTablePrefix();

if ( $installer->getConnection()->isTableExists($tablePrefix . 'mageworx_ordersgrid_order_grid') ) {

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'dhlstatus')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `dhlstatus` int(11) DEFAULT NULL;");
    }

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'dhlstatus_flag')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `dhlstatus_flag` int(11) DEFAULT 0;");
    }

    $installer->endSetup();

}

$installer->startSetup();
$installer->addAttribute('order', 'dhlstatus', array('type'=>'int', 'default' => null));
$installer->addAttribute('order', 'dhlstatus_flag', array('type'=>'int', 'default' => 0));
$installer->endSetup();

$installer->startSetup();
if (!$installer->getConnection()->tableColumnExists($installer->getTable('dhllabel'), 'dhlstatus')) {
    $installer->run("ALTER TABLE `{$this->getTable('dhllabel')}` ADD `dhlstatus` int(11) DEFAULT NULL;");
}
if (!$installer->getConnection()->tableColumnExists($installer->getTable('dhllabel'), 'dhlstatus_flag')) {
    $installer->run("ALTER TABLE `{$this->getTable('dhllabel')}` ADD `dhlstatus_flag` int(11) DEFAULT 0;");
}
$installer->endSetup();