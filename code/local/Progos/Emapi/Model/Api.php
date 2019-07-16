<?php

class Progos_Emapi_Model_Api extends Mage_Checkout_Model_Cart_Api_V2
{
    public function info($quoteId, $store = null)
    {
        return parent::info($quoteId, $store = null);
    }
}
