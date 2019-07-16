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
 * Get Order Details
 */
class Apptha_Marketplace_Block_Adminhtml_Payout extends Mage_Adminhtml_Block_Widget_Grid_Container {

    /**
     * Construct the inital display of grid information
     * Setting the Block files group for this grid
     * Setting the Header text to display
     * Setting the Controller file for this grid
     *  testing 
     * Return order details as array
     * @return array
     */
    public function __construct() {
        //echo  '<br>payout admin block'; 
        $seller_id = $this->getRequest()->getParam('id');
        $orderCollection = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
        $payoutCollection = Mage::getModel ( 'marketplace/payout' )->load($seller_id, 'seller_id');
        $customer = Mage::getModel ( 'customer/customer' )->load ( $seller_id );
        $getSellerTotalSaleAmount =  Mage::helper('marketplace/marketplace')->getSellerAccountsDetail($seller_id);               //working on this section
            /*---------------------------------------------------*/ 
        //$getSellerTotalSaleAmount =  Mage::helper('marketplace/marketplace')->getSellerAccountsDetail($seller_id);
        
        $getPendingAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($seller_id,'Pending');
        $getApproveAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($seller_id,'Approve');
        $getPaidAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($seller_id,'Paid');
        $getOrderRefundAmount =  Mage::helper('marketplace/marketplace')->getOrderRefundAmount($seller_id); 
        $getSellerTotalPayoutRequestAmount = $getPendingAmount + $getApproveAmount + $getPaidAmount;
        $finalRemainingAmount = $getSellerTotalSaleAmount['seller_amount'] - $getSellerTotalPayoutRequestAmount;
        $finalRemainingAmount = $finalRemainingAmount - $getOrderRefundAmount;
        $orderCollection = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
        
        $sellerTitle = $orderCollection['store_title'];
        $this->_controller = 'adminhtml_payout';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')
        ->__('Summary of ' . $sellerTitle.' ('.$customer['firstname'].' '.$customer['lastname'].')'.'
                <ul>
                <li>Total Sale Price: '.$getSellerTotalSaleAmount['currency_product_amount'].'</li>
                <li> Total Commission: '.$getSellerTotalSaleAmount['currency_commission_fee'].'</li>
                <li>Total Remaining: '.$getSellerTotalSaleAmount['currency_seller_amount'].'</li>
                <li>Total Refund : '.Mage::helper ( 'core' )->currency ($getOrderRefundAmount, true, false ).'</li>
                <li>Total Payout Request Amount : '.Mage::helper ( 'core' )->currency ( $getSellerTotalPayoutRequestAmount, true, false ).'</li>
                <li>Final Remaining: '.Mage::helper ( 'core' )->currency ( $finalRemainingAmount, true, false ).'</li>
                </ul>');
        $this->_addButton('button1', array(
            'label' => Mage::helper('marketplace')->__('Back'),
            'onclick' => 'setLocation(\'' . $this->getUrl('marketplaceadmin/adminhtml_manageseller/index') . '\')',
            'class' => 'back',
        ));
        //echo  '<br>payout admin block'; 
        parent::__construct();
        $this->_removeButton('add');
    }
}