<?php

/**
 * Highstreet_Elabelz_module
 *
 * @package     Highstreet_Elabelz
 * @author      Radovan Dodic (radovan.dodic@atessoft.rs) ~ AtesSoft
 * @copyright   Copyright (c) 2016 Highstreet
 */
class Highstreet_Elabelz_Helper_Config_Checkout extends Highstreet_Hsapi_Helper_Config_Checkout {

    /**
     * returns array for JSON with ALL active payment methods filtered by country set by shipping address
     *
     * @return array
     */
    public function getAllPaymentMethods($asArray = false) {
        $jsonMethods = array();
        $methods = array();
        $model = new Mage_Checkout_Block_Onepage_Payment_Methods();
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        try {
            $selectedPaymentMethod = $quote->getPayment()->getData('method');
        } catch (Exception $e) {
            $this->logException($e, 'Get payment method');
            $this->_JSONencodeAndRespond(array("title" => "Error", "content" => $e->getMessage()));
            return;
        }
        foreach ($model->getMethods() as $method) {
            $methodTitle = $method->getTitle();
            $methodCode = $method->getCode();
            if ($methodCode == "paypal_express") { // PayPal. Has logo and strange label text, override
                $methodTitle = "PayPal";
            }

            //eLabelz specific fix
            if ($methodCode == 'msp_cashondelivery') {
                $methodTitle .= "  –  " . Mage::helper('core')->currency($quote->getMspCashondeliveryInclTax(), true, false);
            }

            $m = array(
                'type' => 'option',
                'title' => $methodTitle,
                'code' => $methodCode,
                'price' => $this->getPaymentMethodPrice($methodCode),
                // Payment fee (price) is not available for standard payment methods, for extensions this need to be individualy coded
                'image' => null,
                'options' => $this->_getSuboptionsForPaymentMethod($methodCode),
            );
            $methods[] = $m;
        }
        if ($asArray)
            return $methods;
        $jsonMethods['payment_methods'] = $methods;

        return $jsonMethods;
    }

}