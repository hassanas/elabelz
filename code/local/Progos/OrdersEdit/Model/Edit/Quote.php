<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php
/**
 * Class Progos_OrdersEdit_Model_Edit_Quote
 */
class Progos_OrdersEdit_Model_Edit_Quote extends MageWorx_OrdersEdit_Model_Edit_Quote
{
    /**
     * Apply all the changes to quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param array $data
     * @return Mage_Sales_Model_Quote
     */
    public function applyDataToQuote(Mage_Sales_Model_Quote $quote, array $data)
    {
        foreach ($data as $key => $value) {
            if ($key == 'shipping_address') {
                $this->setAddress($quote, $value, 'shipping');
            } elseif ($key == 'billing_address') {
                $this->setAddress($quote, $value, 'billing');
            /** Begin: Add quote clear for cancelled orders */
            } elseif ($key == 'status' && $value == "canceled") {
                $this->clearQuote($quote);
            /** End: Add quote clear for cancelled orders */
            } elseif ($key == 'payment') {
                $this->setPayment($quote, $value);
            } elseif ($key == 'shipping') {
                $this->setShipping($quote, $value);
            } elseif ($key == 'quote_items') {
                $this->updateItems($quote, $value);
            } elseif ($key == 'product_to_add' && !empty($value)) {
                $this->addNewItems($quote, $value);
            } elseif ($key == 'coupon_code') {
                $this->setCouponCode($quote, $value);
            }
            elseif($key == 'store_credit') {
                    $this->setStoreCredit($quote);
            }
        }

        // Clear quote from canceled items
        $this->clearQuote($quote);

        // If multifees enabled
        $this->collectMultifees();

        $quote->setTotalsCollectedFlag(false)->collectTotals();
        Mage::dispatchEvent('mwoe_apply_data_to_quote_collect_totals_after', array(
            'quote' => $quote,
            'new_data' => $data
        ));

        $this->saveTemporaryItems($quote, 1, true);

        if (isset($data['coupon_code'])) {
            $valid = $this->validateCouponCode($quote, $data['coupon_code']);
            $valid ? $quote->getShippingAddress()->setCouponCode($data['coupon_code']) : null;
        }

        return $quote;
    }

    /**
     * Apply shipping method data to quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param $data
     * @return $this
     */
    public function setShipping(Mage_Sales_Model_Quote $quote, $data)
    {
        $address = $quote->getShippingAddress();

        if (isset($data['custom_price'])) {
            Mage::getSingleton('adminhtml/session')->setShippingEdited(true);
            $shippingCustomPrice = $data['custom_price'];
            $order = Mage::registry('ordersedit_order');
            if ($order) {
                $rate = $order->getBaseToOrderRate();
                /** Begin: Fixing incorrect rounding issue for new shipping amount */
                $baseShippingCustomPrice = round($shippingCustomPrice / floatval($rate), 0);
                /** End: Fixing incorrect rounding issue for new shipping amount */
            } else {
                $baseShippingCustomPrice = $shippingCustomPrice;
            }
            Mage::getSingleton('adminhtml/session_quote')->setBaseShippingCustomPrice($baseShippingCustomPrice);
            Mage::getSingleton('adminhtml/session_quote')->setShippingCustomPrice($shippingCustomPrice);
        } else {
            Mage::getSingleton('adminhtml/session_quote')->setBaseShippingCustomPrice(null);
            Mage::getSingleton('adminhtml/session_quote')->setShippingCustomPrice(null);
        }

        if (isset($data['shipping_method'])) {
            Mage::getSingleton('adminhtml/session')->setShippingEdited(true);
            $address->setShippingMethod($data['shipping_method']);
        }

        return $this;
    }

    public function setStoreCredit($quote)
    {
        Mage::getSingleton('adminhtml/session')->setData('storecredit_'.$quote->getId().'', '1');
        return $this;
    }

}