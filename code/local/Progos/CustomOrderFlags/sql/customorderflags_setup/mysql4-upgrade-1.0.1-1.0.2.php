<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Saroop Chand <saroop.chand@progos.org> 21-02-2018
 * @copyright   Copyright (c) 2018 Progos, Ltd (http://progos.org)
 */
$installer = $this;
$installer->startSetup();

$tablePrefix = Mage::getConfig()->getTablePrefix();

if ( $installer->getConnection()->isTableExists($tablePrefix . 'mageworx_ordersgrid_order_grid') ) {

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'oos_status')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `oos_status` int(11) DEFAULT NULL;");
    }

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'preffered_courier')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `preffered_courier` int(11) DEFAULT NULL;");
    }

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'customer_flag')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `customer_flag` int(11) DEFAULT NULL;");
    }

    $installer->endSetup();

}