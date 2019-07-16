<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Customer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{

    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->load($value);
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

        if ($order->getCustomerIsGuest()) {
            $cId = Mage::helper("adminhtml")->getUrl('adminhtml/customer/edit', array('id' => $value));
            $billing = $order->getBillingAddress()->getData();

            $street = $billing["street"]?$billing["street"]:"N/A";
            // $region = $billing["region"]?$billing["region"]:"N/A";
            $city = $billing["city"]?$billing["city"]:"N/A";
            $country = $billing["country_id"]?Mage::app()->getLocale()->getCountryTranslation($billing["country_id"]):"N/A";
            $telephone = $billing["telephone"]?$billing["telephone"]:"N/A";

            // $additional_details = "<strong>Name:&nbsp;<font color='#F6750F'>" . $order->getCustomerName() . "</font></strong><br>";
            // $additional_details .= "<strong>Email:</strong>&nbsp;" . $order->getCustomerEmail() . "<br><br>";
            // $additional_details .= "<strong>Billing:</strong><br>";
            $additional_details .= "<strong>Name:&nbsp;<font color='#F6750F'>" . $billing["firstname"] . " " . $billing["lastname"] . "</font></strong><br>";
            $additional_details .= "<strong>Contact:&nbsp;<font color='#F6750F'>$telephone</font></strong><br>";
            $additional_details .= "<strong>Address:</strong>&nbsp;$street, $city, $country <br><br>";

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

                // $additional_details = "<strong>Name:&nbsp;<font color='#F6750F'>" . $customer->getName() . "</font></strong><br>";
                // $additional_details .= "<strong>Email:</strong>&nbsp;" . $customer->getEmail() . "<br><br>";
                // $additional_details .= "<strong>Billing:</strong><br>";
                $additional_details .= "<strong>Name:&nbsp;<font color='#F6750F'>" . $billing["firstname"] . " " . $billing["lastname"] . "</font></strong><br>";
                $additional_details .= "<strong>Contact:&nbsp;<font color='#F6750F'>$telephone</font></strong><br>";
                $additional_details .= "<strong>Address:</strong>&nbsp;$street, $region, $city, $country <br><br>";
                // return "<a title='Click to Edit' target='_blank' href='" . $cId . "'>" . $customer->getEmail() . "</a><br>$additional_details";
                return "$additional_details";
            } else {
                return "N/A";
            }

        }
    }

}

