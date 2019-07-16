<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
/**
 * This file is used to create table for Add new attribute for refund request
 */
$installer = $this;
/**
 *  @var $installer Mage_Core_Model_Resource_Setup */

/**
 * Load Initial setup
 */
$installer->startSetup();
/**
 * Alter table airhotels_calendar,airhotels_customer_inbox
*/
$installer->run("
		ALTER TABLE  {$this->getTable('marketplace_commission')} ADD  `ship_status` smallint(6) NOT NULL default '0';
");

$installer->run("
  DROP TABLE IF EXISTS {$this->getTable('marketplace_notes')};

  CREATE TABLE IF NOT EXISTS {$this->getTable('marketplace_notes')} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `increment_id` int(20) NOT NULL,
  `item_id` int(20) NOT NULL,
  `note` text CHARACTER SET utf8 NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ");

$installer->endSetup();