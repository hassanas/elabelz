<?php

class Progos_Restmob_Model_Product extends Mage_Checkout_Model_Cart_Product_Api_V2
{
    public function add($quoteId, $productsData, $store = null)
    {
        return parent::add($quoteId, $productsData, $store);
    }

    public function remove($quoteId, $productsData, $store = null)
    {
        return parent::remove($quoteId, $productsData, $store = null);
    }

    public function update($quoteId, $productsData, $store = null)
    {
        return parent::update($quoteId, $productsData, $store = null);
    }
}
