<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Saroop Chand <saroop.chand@progos.org> 07-06-2018
 * @copyright   Copyright (c) 2018 Progos, Ltd (http://progos.org)
 */
$installer = $this;
$installer->startSetup();

$tablePrefix = Mage::getConfig()->getTablePrefix();

if ( $installer->getConnection()->isTableExists($tablePrefix . 'mageworx_ordersgrid_order_grid') ) {

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'upsstatus')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `upsstatus` int(11) DEFAULT NULL;");
    }

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'upsstatus_flag')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `upsstatus_flag` int(11) DEFAULT 0;");
    }

    $installer->endSetup();

}

$installer->startSetup();
$installer->addAttribute('order', 'upsstatus', array('type'=>'int', 'default' => null));
$installer->addAttribute('order', 'upsstatus_flag', array('type'=>'int', 'default' => 0));
$installer->endSetup();

$installer->startSetup();
if (!$installer->getConnection()->tableColumnExists($installer->getTable('upslabel'), 'upsstatus')) {
    $installer->run("ALTER TABLE `{$this->getTable('upslabel')}` ADD `upsstatus` int(11) DEFAULT NULL;");
}
if (!$installer->getConnection()->tableColumnExists($installer->getTable('upslabel'), 'upsstatus_flag')) {
    $installer->run("ALTER TABLE `{$this->getTable('upslabel')}` ADD `upsstatus_flag` int(11) DEFAULT 0;");
}
$installer->endSetup();