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
 * This file is used to alter table for Add new attribute for buyer confirmation request
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
						
		ALTER TABLE {$this->getTable('marketplace_commission')} CHANGE `is_buyer_confirmation_date` `is_buyer_confirmation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
		ALTER TABLE {$this->getTable('marketplace_commission')} CHANGE `is_seller_confirmation_date` `is_seller_confirmation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
		ALTER TABLE {$this->getTable('marketplace_commission')} CHANGE `shipped_from_elabelz_date` `shipped_from_elabelz_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
		ALTER TABLE {$this->getTable('marketplace_commission')} CHANGE `successful_non_refundable_date` `successful_non_refundable_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

");

$installer->endSetup();