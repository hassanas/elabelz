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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_ShippingCountry extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to render order date 
     * 
     * Return the date
     * @return date
     */
    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $_order = Mage::getModel('sales/order')->load($value);
        if($_order->getId() == ""){
            $orderDetails ="Empty";
        }
        else{
        $_shippingAddress = $_order->getShippingAddress();
        $countryCode = $_shippingAddress->getCountry_id();
        if($countryCode === "SA" || $countryCode === "AE" || $countryCode === "KW" 
            || $countryCode === "QA" || $countryCode === "BH" || $countryCode === "OM" || $countryCode === "IQ"):
          $country = Mage::getModel('directory/country')->loadByCode($countryCode);
          $orderDetails = $country->getName();
        else:
          $orderDetails ="Empty";
        endif;
        }
        return $orderDetails;

    }

}

