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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Totalcommission extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
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
        $sellerProduct = Mage::getModel('marketplace/sellerprofile')->sellerProduct($value);
        
        // $getSellerTotalSaleAmount =  Mage::helper('marketplace/marketplace')->getSellerAccountsDetail($value);
        // $getSellerTotalPayoutRequestPendingAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($value,'Pending');
        // $getSellerTotalPayoutRequestApproveAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($value,'Approve');
        // $getSellerTotalPayoutRequestAmount = $getSellerTotalPayoutRequestPendingAmount + $getSellerTotalPayoutRequestApproveAmount;
        // $refund_amount = Mage::helper('marketplace/marketplace')->getOrderRefundAmount($value);
        
        // $finalRemainingAmount = ($getSellerTotalSaleAmount['commission_fee'] - $refund_amount) -  $getSellerTotalPayoutRequestAmount;
        
        // $total_commision = $getSellerTotalSaleAmount['commission_fee'];

        // return Mage::helper('core')->currency($total_commision, true, false);
        /*---------------------Code edited by Azhar 07-13-2016------------------*/
        foreach ($sellerProduct as $_sellerProduct) {
            if (
                ($_sellerProduct->getOrderStatus() == "complete" ||
                 $_sellerProduct->getOrderStatus() == "shipped_from_elabelz" ||
                 $_sellerProduct->getOrderStatus() == "successful_delivery" ) &&

                ($_sellerProduct->getItemOrderStatus() != "canceled" || 
                 $_sellerProduct->getItemOrderStatus() != "refunded" 
                 // $_sellerProduct->getItemOrderStatus() != "rejected_seller" ||
                 // $_sellerProduct->getItemOrderStatus() != "rejected_customer"
                 ) &&

                $_sellerProduct->getIsSellerConfirmation() == "Yes" &&
                $_sellerProduct->getIsBuyerConfirmation() == "Yes" &&
                $_sellerProduct->getStatus() == 1) {

                $commission_fee[] = $_sellerProduct['commission_fee'];
            }
        }
          $commission_fee = array_sum($commission_fee);
        return Mage::helper('core')->currency($commission_fee, true, true);
    }
}

