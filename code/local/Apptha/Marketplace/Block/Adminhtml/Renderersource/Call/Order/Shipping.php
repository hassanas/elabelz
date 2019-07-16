<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Order_Shipping extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{

    public function render(Varien_Object $row) {
        $entityId = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel("sales/order")->load($entityId);
        $shipping = $order->getShippingAddress()->getData();


        $street = $shipping["street"]?$shipping["street"]:"N/A";
        // $region = $shipping["region"]?$shipping["region"]:"N/A";
        $city = $shipping["city"]?$shipping["city"]:"N/A";
        $country = $shipping["country_id"]?Mage::app()->getLocale()->getCountryTranslation($shipping["country_id"]):"N/A";
        $telephone = $shipping["telephone"]?$shipping["telephone"]:"N/A";

        // $additional_details = "<strong>Name:&nbsp;<font color='#F6750F'>" . $order->getCustomerName() . "</font></strong><br>";
        // $additional_details .= "<strong>Email:</strong>&nbsp;" . $order->getCustomerEmail() . "<br><br>";
        // $additional_details = "<strong>Shipping:</strong><br>";
        $additional_details .= "<strong>Name:&nbsp;<font color='#F6750F'>" . $shipping["firstname"] . " " . $shipping["lastname"] . "</font></strong><br>";
        $additional_details .= "<strong>Contact:&nbsp;<font color='#F6750F'>$telephone</font></strong><br>";
        $additional_details .= "<strong>Address:</strong>&nbsp;$street, $city, $country <br><br>";

        return "$additional_details";
    }

}

