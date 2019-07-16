<?php

/**
 * Highstreet_Elabelz_module
 *
 * @package     Highstreet_Elabelz
 * @author      Radovan Dodic (radovan.dodic@atessoft.rs) ~ AtesSoft
 * @copyright   Copyright (c) 2016 Highstreet
 */
class Highstreet_Elabelz_Helper_Config_Cart extends Highstreet_Hsapi_Helper_Config_Cart {


   public function getItemPrice($priceType, $_item) {
        //Elabelz specific: always use the effective price since the original price cannot be calculated
        //eLabelz uses the configurable price instead of the simple price. But in some cases the confurable price is also different than the price in the cart; so it is safest to use the effective price
        if ($priceType === 'original') {
            return parent::getItemPrice('effective',$_item);
        } else {
            return parent::getItemPrice($priceType,$_item);
        }
    }


    /**
     * Get totals from quote
     *
     * @return array
     */
    public function getTotals() {
        $totals = $this->_getQuote()->getTotals();

        // get tax calculation based on shipping address
        $shipAddress = $this->_getQuote()->getShippingAddress();
        $shippingFromTotals = (isset($totals['shipping']) && $totals['shipping']->getValue()) ? $totals['shipping']->getValue() : 0;
        $shipping = ($shipAddress) ? $this->getShippingPrice($shipAddress) : $shippingFromTotals;
        return array(
            'discount' => (isset($totals['discount']) && $totals['discount']->getValue()) ? $totals['discount']->getValue() : 0,
            'sub_total' => (isset($totals['subtotal']) && $totals['subtotal']->getValue()) ? $totals['subtotal']->getValue() : 0,
            'tax' => (isset($totals['tax']) && $totals['tax']->getValue()) ? $totals['tax']->getValue() : 0,
            'shipping' => ($shipping == 0 && !$shipAddress->getShippingMethod()) ? null : $shipping,
            'grand_total' => (isset($totals['grand_total']) && $totals['grand_total']->getValue()) ? $totals['grand_total']->getValue() /* + $this->_getQuote()->getMspCashondeliveryInclTax() */ : 0,
        );
    }

}
