<?php

class Progos_Emapi_Model_Payment_Api extends Mage_Checkout_Model_Cart_Payment_Api
{
    public function getPaymentMethodsList($quoteId, $store = null)
    {
        return parent::getPaymentMethodsList($quoteId, $store = null);
    }
}
