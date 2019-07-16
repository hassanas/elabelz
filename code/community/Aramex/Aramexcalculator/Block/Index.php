<?php

class Aramex_Aramexcalculator_Block_Index extends Mage_Core_Block_Template
{

    protected function ifLogged()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getId()
    {
        return Mage::registry('current_product')->getId();
    }

    protected function getCountries()
    {
        return Mage::getResourceModel('directory/country_collection')->loadByStore()->toOptionArray();
    }

    public function getDestinationAddress()
    {
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
        if ($customerAddressId) {
            $address = Mage::getModel('customer/address')->load($customerAddressId);
            return $address->getData();
        }else{
            return null;
        }
        
    }
}
