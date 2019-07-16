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
 */

/**
 * Display Remaining amount
 * Render the Amount remaining from admin to seller
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Amountremaining extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    /**
     * Function to get data of remaining amount from admin
     *
     * Return the remaining amount
     *
     * @return float
     */
    public function render(Varien_Object $row) {
        // $return = '';
        $value = $row->getData ( $this->getColumn ()->getIndex () );
        // $amountCollection = Mage::getModel ( 'marketplace/transaction' )->getCollection ()->addFieldToSelect ( 'seller_commission' )->addFieldToFilter ( 'seller_id', $value )->addFieldToFilter ( 'paid', 0 );
        // $amountCollection->getSelect ()->columns ( 'SUM(seller_commission) AS seller_commission' )->group ( 'seller_id' );
        // foreach ( $amountCollection as $amount ) {
        //     $return = $amount->getSellerCommission ();
        // }
        // return Mage::helper ( 'core' )->currency ( $return, true, false );



        $sellerProduct = Mage::getModel('marketplace/sellerprofile')->sellerProduct($value);
        // $lifetimeSales = array();
        // foreach ($sellerProduct as $_sellerProduct) {
        //      if ($_sellerProduct->getOrderStatus() == "complete" && $_sellerProduct->getItemOrderStatus() == "complete" && $_sellerProduct->getStatus() == 1) {
        //          $sale[] = $_sellerProduct['seller_amount'];
        //      }
        // }
        foreach ($sellerProduct as $_sellerProduct) {
            if ( ($_sellerProduct->getOrderStatus() == "complete" ||
                 $_sellerProduct->getOrderStatus() == "shipped_from_elabelz" ||
                 $_sellerProduct->getOrderStatus() == "successful_delivery" ) &&
                ($_sellerProduct->getItemOrderStatus() != "canceled" || 
                 $_sellerProduct->getItemOrderStatus() != "refunded" 
                 ) &&
                $_sellerProduct->getIsSellerConfirmation() == "Yes" &&
                $_sellerProduct->getIsBuyerConfirmation() == "Yes" &&
                $_sellerProduct->getStatus() == 1) {
                 
                 $sale[] = $_sellerProduct['seller_amount'];
                 $commission_fee[] = $_sellerProduct['commission_fee'];
            }
        }
         $sale = array_sum($sale);
         $commission_fee = array_sum($commission_fee);

        $sellerPayout = Mage::getModel ( 'marketplace/payout' )->getCollection ()
        ->addFieldToFilter ( 'seller_id', $value );
        $lifetimePaid = array();
        foreach ($sellerPayout as $_sellerPayout) {
            if ($_sellerPayout->getStatus() == "Paid" ) {
                $lifetimePaid[] = $_sellerPayout['request_amount'];
            }
        }
        $paid = array_sum($lifetimePaid);
        $remaining = $sale - ($commission_fee + $paid);

        return Mage::helper('core')->currency($remaining, true, false);
        // foreach ($sellerProduct as $_sellerProduct) {
        //     if ($_sellerProduct->getOrderStatus() == "complete" && $_sellerProduct->getItemOrderStatus() == "complete" && $_sellerProduct->getCredited() == 1) {
        //         $credited[] = $_sellerProduct['seller_amount'];
        //     }
        // }

        // foreach ($sellerProduct as $_sellerProduct) {
        //     // if ($_sellerProduct->getOrderStatus() == "complete" && $_sellerProduct->getItemOrderStatus() == "refunded" && $_sellerProduct->getCredited() == 1 && $_sellerProduct->getStatus() == 0) {
        //     if ($_sellerProduct->getItemOrderStatus() == "refunded" && $_sellerProduct->getCredited() == 1 && $_sellerProduct->getStatus() == 0) {
        //         $refunded[] = $_sellerProduct['seller_amount'];
        //     }
        // }

        // $getSellerTotalSaleAmount =  Mage::helper('marketplace/marketplace')->getSellerAccountsDetail($value);
        // $getSellerTotalPayoutRequestPendingAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($value,'Pending');
        // $getSellerTotalPayoutRequestApproveAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($value,'Approve');
        // $getSellerTotalPayoutRequestAmount = $getSellerTotalPayoutRequestPendingAmount + $getSellerTotalPayoutRequestApproveAmount;
        // $refund_amount = Mage::helper('marketplace/marketplace')->getOrderRefundAmount($value);
        
        // $finalRemainingAmount = ($getSellerTotalSaleAmount['seller_amount'] - $refund_amount) -  $getSellerTotalPayoutRequestAmount;


        // $sale = array_sum($sale);
        // $credited = array_sum($credited);
        // $refunded = array_sum($credited);
        // $remaining = ($sale - $credited) - $refunded;

        // return Mage::helper('core')->currency($finalRemainingAmount, true, false);

    }

}

