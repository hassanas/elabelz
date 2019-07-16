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
 * This file is used get all seller information
 */
class Apptha_Marketplace_Block_Transactiondetail extends Mage_Core_Block_Template {
    /**
     * Function to get save profile url
     *
     * Return the save profile action url
     * @return string
     */
    function payoutSaveUrl()
    {
        return Mage::getUrl('marketplace/seller/payoutsave');
    }

    function approvedPayoutAmount($sellerId)
    {
        return Mage::helper('marketplace/marketplace')->getTotalAmount($sellerId, "Approve");
    }

    function availablePayoutAmount($sellerId)
    {
        return Mage::helper('marketplace/marketplace')->getSellerRemainingAmount($sellerId);
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        $collection = $this->getTransactions();
        $this->setCollection($collection);
        $pager = $this->getLayout()
            ->createBlock('page/html_pager', 'my.pager')
            ->setCollection($collection);
        $pager->setAvailableLimit(array(10 => 10, 20 => 20, 50 => 50));
        $this->setChild('pager', $pager);
        return $this;
    }

    /**
     * Function to get the Pagination
     *
     * Return the collection for pagination
     * @return array
     */
    public function getPagerHtml() {
        /**
         * Return pager
         */
        return $this->getChildHtml('pager');
    }

    
    public function getPayoutDetail($sellerId){
     
        return Mage::helper('marketplace/marketplace')->getSellerAccountsDetail($sellerId); 
    }
    
    public function getTransactions(){
         /**
         * Get customer
         */
        $customer = Mage::getSingleton("customer/session")->getCustomer();
        /**
         * Get customer id
         */
        $customerId = $customer->getId();
      
        return Mage::getModel('marketplace/commission')
               ->getCollection()
               ->addFieldToFilter('seller_id', $customerId)
               //->addFieldToFilter('credited',array('eq' => 1) )
               //->addFieldToFilter( 'item_order_status',  array('like' => '%complete%') )
               //->addFieldToFilter ( 'credited', array ('eq' => 1) )
               ->addFieldToFilter ( 'status', array ('eq' => 1) )
               ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'Yes') )
               ->addFieldToFilter ( 'is_seller_confirmation', array ('eq' => 'Yes') )
               ->setOrder('order_id', 'DESC'); 
    }
}