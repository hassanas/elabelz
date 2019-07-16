<?php

class Progos_Restmob_Model_Coupon_Api extends Mage_Checkout_Model_Cart_Coupon_Api
{
    public function add($quoteId, $couponCode, $store = null)
    {
        return parent::add($quoteId, $couponCode, $store = null);
    }
}
