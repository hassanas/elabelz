<?php

class Progos_Restmob_Model_Customer_Customer_Api extends Mage_Customer_Model_Customer_Api
{
    public function create($customerData)
    {
        return parent::create($customerData);
    }

    public function update($customerId, $customerData)
    {
        return parent::update($customerId, $customerData);
    }
}
