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
 * Renderer to display customer details
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemsellerdetails extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to display customer details
     * 
     * Return the customer details
     * @return string
     */
    public function render(Varien_Object $row) {
         
        $value = $row->getData($this->getColumn()->getIndex());
        $customer = Mage::getModel('customer/customer')->load($value);
        // $cId = Mage::helper("adminhtml")->getUrl('adminhtml/customer/edit', array('id' => $value));
        $cId = Mage::helper("adminhtml")->getUrl('*/adminhtml_sellerreview/edit', array('id' => $value));
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->collectprofile ( $customer->getId() );
        if ($customer->getId()) {
            $store_title = !empty($seller->getStoreTitle())?$seller->getStoreTitle():"Untitled";
            $additional_details = "<strong>Email:&nbsp;</strong>" . $customer->getEmail() . "<br>";
            $additional_details .= "<strong>Name:&nbsp;</strong>" . $customer->getName() . "<br>";
            $additional_details .= "<strong>Contact:&nbsp;</strong>" . $seller->getContact() . "<br>";
            $additional_details .= "<strong>Location:&nbsp;</strong>" . $seller->getState() . ", " . Mage::app()->getLocale()->getCountryTranslation($seller->getCountry()) . "<br><br>";
         return "<a title='Click to Edit' target='_blank' href='" . $cId . "'>" . $store_title . "</a><br>$additional_details";
        } else {
            return "N/A";
        }
    }

    public function renderExport(Varien_Object $row)
    {
       
        $value = $row->getData($this->getColumn()->getIndex());
        $customer = Mage::getModel('customer/customer')->load($value);
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->collectprofile ( $customer->getId() );
        if ($customer->getId()) {
            $store_title = !empty($seller->getStoreTitle())?$seller->getStoreTitle():"Untitled";
            $store_title = "Store Title:". $store_title;
            $additional_details = ",Email:" . $customer->getEmail();
            $additional_details .= ",Name:" . $customer->getName();
            $additional_details .= ",Contact:" . $seller->getContact();
            $additional_details .= ",State:" . $seller->getState() . ", " . Mage::app()->getLocale()->getCountryTranslation($seller->getCountry());
            return $store_title.$additional_details;
            
        } else {
            return "N/A";
        }
    }

}

