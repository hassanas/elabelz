<?php

class Progos_Restmob_Model_Shipping_Api extends Mage_Checkout_Model_Cart_Shipping_Api
{
    public function getShippingMethodsList($quoteId, $store = null)
    {
        return parent::getShippingMethodsList($quoteId, $store = null);
    }
}
