<?php

$installer = $this;

$installer->startSetup();

$installer->run(
    "CREATE TABLE IF NOT EXISTS {$this->getTable('aramexlabel')} (
  `label_id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `order_id` int(11) NOT NULL default 0,
  `trackingnumber` varchar(255) NOT NULL default '',
  `labelname` varchar(255) NOT NULL default '',
  `type` varchar(20) DEFAULT 'shipment',
  `shipment_id` int(11) NULL DEFAULT '0',
  `status` smallint(6) NOT NULL default '0',
  `statustext` text,
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);
$installer->endSetup();



$tablePrefix = Mage::getConfig()->getTablePrefix();

if ( $installer->getConnection()->isTableExists($tablePrefix . 'mageworx_ordersgrid_order_grid') ) {

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'aramexstatus')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `aramexstatus` int(11) DEFAULT NULL;");
    }

    if (!$installer->getConnection()->tableColumnExists($installer->getTable('mageworx_ordersgrid/order_grid'), 'aramexstatus_flag')) {
        $installer->run("ALTER TABLE `{$this->getTable('mageworx_ordersgrid/order_grid')}` ADD `aramexstatus_flag` int(11) DEFAULT 0;");
    }

    $installer->endSetup();

}

$installer->startSetup();
$installer->addAttribute('order', 'aramexstatus', array('type'=>'int', 'default' => null));
$installer->addAttribute('order', 'aramexstatus_flag', array('type'=>'int', 'default' => 0));
$installer->endSetup();

$installer->startSetup();
if (!$installer->getConnection()->tableColumnExists($installer->getTable('aramexlabel'), 'aramexstatus')) {
    $installer->run("ALTER TABLE `{$this->getTable('aramexlabel')}` ADD `aramexstatus` int(11) DEFAULT NULL;");
}
if (!$installer->getConnection()->tableColumnExists($installer->getTable('aramexlabel'), 'aramexstatus_flag')) {
    $installer->run("ALTER TABLE `{$this->getTable('aramexlabel')}` ADD `aramexstatus_flag` int(11) DEFAULT 0;");
}
$installer->endSetup();

$installer->startSetup();
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
if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_flat_order'), 'upsstatus')) {
    $installer->addAttribute('order', 'upsstatus', array('type' => 'int', 'default' => null));
}
if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_flat_order'), 'upsstatus_flag')) {
    $installer->addAttribute('order', 'upsstatus_flag', array('type' => 'int', 'default' => 0));
}
$installer->endSetup();

$installer->startSetup();
if (!$installer->getConnection()->tableColumnExists($installer->getTable('upslabel'), 'upsstatus')) {
    $installer->run("ALTER TABLE `{$this->getTable('upslabel')}` ADD `upsstatus` int(11) DEFAULT NULL;");
}
if (!$installer->getConnection()->tableColumnExists($installer->getTable('upslabel'), 'upsstatus_flag')) {
    $installer->run("ALTER TABLE `{$this->getTable('upslabel')}` ADD `upsstatus_flag` int(11) DEFAULT 0;");
}
$installer->endSetup();