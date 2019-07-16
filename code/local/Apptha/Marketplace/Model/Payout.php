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
class Apptha_Marketplace_Model_Payout extends Mage_Core_Model_Abstract {
    
    public function _construct() {
        parent::_construct ();
        $this->_init ( 'marketplace/payout' );
    }

    public function saveRequestedPayment($data)
    {
        try {
            $data['created_at'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
            $data['updated_at'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
            $model = Mage::getModel ( 'marketplace/payout' )->setData($data);
            $model->save();
            return $model->getId();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function ackPayout($payoutId)
    {
        if($payoutId != '') {
            $now = Mage::getModel('core/date')->date('Y-m-d H:i:s', time());
            $collection = Mage::getModel('marketplace/payout')->load($payoutId, 'id');
            $collection->setAck('Yes');
            $collection->setAckAt($now);
            $collection->save();
            return true;
        }
    }
    
}