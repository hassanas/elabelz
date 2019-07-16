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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_CancelOrder extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to cancel the order and sync between magento order system and marketplace 
     * 
     * Return the status
     * @return varchar
     */
    
    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $seller = $this->getColumn()->getSeller();
        $commission = Mage::getModel('marketplace/commission')->load($value);

        if ($commission->getItemOrderStatus() != "canceled" && $commission->getCancelRequestCustomer() == 1 && $commission->getCancelRequestSellerConfirmation() == 1) {
            $order_id = $commission->getIncrementId();
            $url = $this->getUrl('*/*/cancel/', array('id' => $commission->getId()));
            $on_click = "onclick=\"javascript: return confirm('Cancel this item belong to Order # $order_id');\"";
            $result = "<a $on_click href=\"" . $url . "\">Cancel</a>";
        } elseif ($commission->getItemOrderStatus() == "canceled" && $commission->getCancelRequestCustomer() == 1 && $commission->getCancelRequestSellerConfirmation() == 1) {
            $result = $this->__("Canceled");            
        }
        
        return $result;
    }

}

