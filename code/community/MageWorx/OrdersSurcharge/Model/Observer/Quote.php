<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Model_Observer_Quote extends MageWorx_OrdersSurcharge_Model_Observer_Abstract
{
    /**
     * Adds fake subtotal to prevent exception when checkout complete
     *
     * @param $observer
     * @return $this
     */
    public function activatePaymentMethod($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** Unused params
        $result = $observer->getResult();
        $method = $observer->getMethodInstance();
         */
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();
        if (!$quote) {
            return $this;
        }

        $billing = $quote->getBillingAddress();
        if ($billing->getBaseSurchargeAmount() >= 0.0001 && $quote->getBaseSubtotal() == 0) {
            $quote->setBaseSubtotal(0.0001);
        }

        return $this;
    }

    /**
     * Does not allow mix regular cart items with surcharge item
     *
     * @param $observer
     * @return $this
     */
    public function checkSurchargeCart($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();
        Mage::getSingleton('mageworx_orderssurcharge/quote')->validateQuote($quote);

        return $this;
    }
}