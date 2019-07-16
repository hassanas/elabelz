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
 * Credit status change
 * Renderer to change the credit status from 'credit' to 'credited'
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordercredit extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to change the crdit status from 'credit' to 'credited'
     * 
     * Return the status
     * @return varchar
     */
    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $seller = $this->getColumn()->getSeller();
        
        $commissionDetails = Mage::getModel('marketplace/commission')->load($value);
        /*-----------start Edit By Azhar at 03-02-2016--------------*/
        $commissionDetails->getOrderId();
        $orderDetails = Mage::getModel('sales/order')->load($commissionDetails->getOrderId());
        $getCreatedAtDate = $orderDetails->getCreatedAtDate();
        $getCreatedAtDate = date("Y-m-d H:i:s", strtotime($getCreatedAtDate));
        $todayStartOfDayDate  = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $diffDays = ceil(abs(strtotime($todayStartOfDayDate) - strtotime($getCreatedAtDate)) / 86400);
         /*-----------start Edit By Azhar at 03-02-2016--------------*/
        $getItemOrderStatus = $commissionDetails->getItemOrderStatus();
        $getCredited = $commissionDetails->getCredited();
        $creditLimitDays = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/credit_limit');

    if ($commissionDetails->getItemOrderStatus() != "complete") {
        $oc_status = ucfirst($commissionDetails->getItemOrderStatus());
        $onclick = "onclick=\"javascript: alert('$oc_status item cannot be credited'); return false;\"";
    }

     if (empty($getCredited)) {
        if(empty($creditLimitDays) || $creditLimitDays == 0){//show credit option
                 $result = "<a $onclick href='" . $this->getUrl('*/*/credit', array('id' => $value, 'seller_id' => $seller)) . "' title='" . Mage::helper('marketplace')->__('click to Credit') . "'>" . Mage::helper('marketplace')->__('Credit') . "</a>";
        }else if(is_numeric($creditLimitDays) && ($diffDays >= $creditLimitDays) ){// show enable options
                $result = "<a $onclick href='" . $this->getUrl('*/*/credit', array('id' => $value, 'seller_id' => $seller)) . "' title='" . Mage::helper('marketplace')->__('click to Credit') . "'>" . Mage::helper('marketplace')->__('Credit') . "</a>";                
        }else{
             $result = "<a href='#' title='" . Mage::helper('marketplace')->__('Credit Option will Enable after when order status completed and order date 7 older then current date.') . "'>" . Mage::helper('marketplace')->__('Not Enable') . "</a>";            
        }
    } else {
            $result = "<a href='" . $this->getUrl('*/*/rollback', array('id' => $value, 'seller_id' => $seller)) . "' title='" . Mage::helper('marketplace')->__('Click to Rollback') . "'>" . Mage::helper('marketplace')->__('Rollback') . "</a>";
    }
        return $result;
    }

}

