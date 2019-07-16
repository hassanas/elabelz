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
 * Renderer to get the order date
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_SellerName extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to render order date 
     * 
     * Return the date
     * @return date
     */
    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        // $commission = Mage::getModel('marketplace/commission')->getCollection()
        //              ->addFieldToFilter('increment_id',$value )
        //              ->addFieldToFilter('is_buyer_confirmation','Yes');
        $orderDetails = "<ul class='sellers-name'>";
        //foreach($commission as $com):
            $seller_id = $value;
            $seller = Mage::getModel('marketplace/sellerprofile')->collectprofile ( $seller_id );
            $seller_name = $seller->getStoreTitle();
            $orderDetails .= "<li>" . $seller_name ."</li>" ;
            $orderCsv = $seller_name;
        //endforeach; 

        $orderDetails .= "</ul>";
        
        if (strpos(Mage::app()->getRequest()->getRequestString(), '/exportCsv/')) {
            return $orderCsv;
        }
        elseif (strpos(Mage::app()->getRequest()->getRequestString(), '/exportExcel/')) {
            return $orderCsv;
        }
        else{
        return $orderDetails;
       } 
    }

}

