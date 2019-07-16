<?php

class Progos_Restmob_Model_Cart_Shipping_Api extends Mage_Checkout_Model_Cart_Shipping_Api
{
    public function setShippingMethod($quoteId, $shippingMethod, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        $quoteShippingAddress = $quote->getShippingAddress();
        if (is_null($quoteShippingAddress->getId())) {
            Mage::log('error on setShippingMethod1.. ' . ' shipping_address_is_not_set\n', null, 'mobile_app.log');
            $this->_fault("shipping_address_is_not_set");
        }
        $rate = $quote->getShippingAddress()->collectShippingRates()->getShippingRateByCode($shippingMethod);
        if ($rate === false) {
            Mage::log('error on Progos_Restmob_Model_Cart_Shipping_Api setShippingMethod2.. ' . ' shipping_method_is_not_available \n' . $shippingMethod . '\n', null, 'mobile_app.log');
            $this->_fault('shipping_method_is_not_available');
        }
        $quote->getShippingAddress()->setShippingMethod($shippingMethod);
        $quote->collectTotals()->save();
        return true;
    }

    public function getShippingMethodsList($quoteId, $store = null)
    {
        return parent::getShippingMethodsList($quoteId, $store = null);
    }
}
