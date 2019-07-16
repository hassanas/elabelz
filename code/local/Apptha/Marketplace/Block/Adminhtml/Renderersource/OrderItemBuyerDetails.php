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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerDetails extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to display customer details
     * 
     * Return the customer details
     * @return string
     */
    public function render(Varien_Object $row) {
      
        $value = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('entity_id',$value)->getFirstItem();

        if ($order->getCustomerIsGuest()) {
            $cId = Mage::helper("adminhtml")->getUrl('adminhtml/customer/edit', array('id' => $value));

            $billing = $order->getBillingAddress()->getData();

            $street = $billing["street"]?$billing["street"]:"N/A";
            $region = $billing["region"]?$billing["region"]:"N/A";
            $city = $billing["city"]?$billing["city"]:"N/A";
            $country = $billing["country_id"]?Mage::app()->getLocale()->getCountryTranslation($billing["country_id"]):"N/A";
            $telephone = $billing["telephone"]?$billing["telephone"]:"N/A";

            $additional_details = "<strong>Email:</strong>&nbsp;" . $order->getCustomerEmail() . "<br>";
            $additional_details .= "<strong>Name:</strong>&nbsp;" . $order->getCustomerFirstname() . " " . $order->getCustomerLastname() . "<br><br>";
            $additional_details .= "<strong>Billing:</strong><br>";
            $additional_details .= "<strong>Name:</strong>&nbsp;" . $billing["firstname"] . " " . $billing["lastname"] . "<br>";
            $additional_details .= "<strong>Contact:</strong>&nbsp;$telephone <br>";            
            $additional_details .= "<strong>Address:</strong>&nbsp;$street, $region, $city, $country <br><br>";

            return "$additional_details";
        } else {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            $cId = Mage::helper("adminhtml")->getUrl('adminhtml/customer/edit', array('id' => $value));

            if ($customer->getId()) {
                $billing = $order->getBillingAddress()->getData();
                $street = $billing["street"]?$billing["street"]:"N/A";
                $region = $billing["region"]?$billing["region"]:"N/A";
                $city = $billing["city"]?$billing["city"]:"N/A";
                $country = $billing["country_id"]?Mage::app()->getLocale()->getCountryTranslation($billing["country_id"]):"N/A";
                $telephone = $billing["telephone"]?$billing["telephone"]:"N/A";

                $additional_details = "<strong>Email:</strong>&nbsp;" . $customer->getEmail() . "<br>";
                $additional_details .= "<strong>Name:</strong>&nbsp;" . $customer->getName() . "<br><br>";
                $additional_details .= "<strong>Billing:</strong><br>";
                $additional_details .= "<strong>Name:</strong>" . $billing["firstname"] . " " . $billing["lastname"] . "<br>";
                $additional_details .= "<strong>Contact:</strong>&nbsp;$telephone <br>";            
                $additional_details .= "<strong>Address:</strong>&nbsp;$street, $region, $city, $country <br><br>";
                return "<a title='Click to Edit' target='_blank' href='" . $cId . "'>" . $customer->getEmail() . "</a><br>$additional_details";
            } else {
                return "N/A";
            }

        }
    }

    public function renderExport(Varien_Object $row)
    {
      $value = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('entity_id',$value)->getFirstItem();
        if ($order->getCustomerIsGuest()) {
            $cId = Mage::helper("adminhtml")->getUrl('adminhtml/customer/edit', array('id' => $value));
            $billing = $order->getBillingAddress()->getData();
            $street = $billing["street"]?$billing["street"]:"N/A";
            $region = $billing["region"]?$billing["region"]:"N/A";
            $city = $billing["city"]?$billing["city"]:"N/A";
            $country = $billing["country_id"]?Mage::app()->getLocale()->getCountryTranslation($billing["country_id"]):"N/A";
            $telephone = $billing["telephone"]?$billing["telephone"]:"N/A";
            $additional_details = "Email:" . $order->getCustomerEmail();
            $additional_details .= ",Name:" . $order->getCustomerFirstname() . " " . $order->getCustomerLastname();
            $additional_details .= ",Billing:";
            $additional_details .= "Name:" . $billing["firstname"] . " " . $billing["lastname"];
            $additional_details .= ",Contact:".$telephone;            
            $additional_details .= ",Address:".$street.",".$region.",".$city.",".$country;
            return $additional_details;
        } else {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            $cId = Mage::helper("adminhtml")->getUrl('adminhtml/customer/edit', array('id' => $value));
            if ($customer->getId()) {
                $billing = $order->getBillingAddress()->getData();
                $street = $billing["street"]?$billing["street"]:"N/A";
                $region = $billing["region"]?$billing["region"]:"N/A";
                $city = $billing["city"]?$billing["city"]:"N/A";
                $country = $billing["country_id"]?Mage::app()->getLocale()->getCountryTranslation($billing["country_id"]):"N/A";
                $telephone = $billing["telephone"]?$billing["telephone"]:"N/A";
                $additional_details = "Email:" . $customer->getEmail();
                $additional_details .= ",Name:" . $customer->getName();
                $additional_details .= "Billing:";
                $additional_details .= "Name:" . $billing["firstname"] . " " . $billing["lastname"];
                $additional_details .= ",Contact:".$telephone;            
                $additional_details .= ",Address:".$street.",".$region.",".$city.",".$country;              
                return $additional_details;
            } else {
                return "N/A";
            }
        }
    }
}

