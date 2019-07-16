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
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
class Apptha_Marketplace_Model_Status_Status extends Mage_Core_Model_Abstract {
 /**
  * Define repeated string variables
  */
 const STATUS_PENDING = 0;
 const STATUS_APPROVED = 1;
 const STATUS_DISAPPROVED = 2;
 
 /**
  * Retrieve option array
  *
  * @return array
  */
 static public function getOptionArray() {
  return array (
    static::STATUS_PENDING => Mage::helper ( 'marketplace' )->__ ( 'Pending' ),
    static::STATUS_APPROVED => Mage::helper ( 'marketplace' )->__ ( 'Approved' ),
    static::STATUS_DISAPPROVED => Mage::helper ( 'marketplace' )->__ ( 'Disapproved' ) 
  );
 }
 /**
  * Define repeated string variables
  */
 const PENDING = 'Pending';
 const APPROVE = 'Approve';
 const DISAPPROVE = 'Disapprove';
 const PAID = 'Paid';
 
 /**
  * Retrieve option array
  *
  * @return array
  */
  static public function getOptionPayoutRequestArray() {
  return array (
    static::PENDING => Mage::helper ( 'marketplace' )->__ ( 'Pending' ),
    static::APPROVE => Mage::helper ( 'marketplace' )->__ ( 'Approve' ),
    static::DISAPPROVE => Mage::helper ( 'marketplace' )->__ ( 'Disapprove' ),
    static::PAID => Mage::helper ( 'marketplace' )->__ ( 'Paid' ) 
  );
 }
 /**
  * Define repeated string variables
  */
 const YES = 'Yes';
 const NO = 'No';
 
 /**
  * Retrieve option array
  *
  * @return array
  */
  static public function getOptionYesNoArray() {
  return array (
    static::YES => Mage::helper ( 'marketplace' )->__ ( 'Yes' ),
    static::NO => Mage::helper ( 'marketplace' )->__ ( 'No' ),
  );
 }
} 