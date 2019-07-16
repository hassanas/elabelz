<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Model_Observer extends MageWorx_OrdersSurcharge_Model_Observer_Abstract
{
    /**
     * Remove the multifees block from page for the surcharge quote
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function toHtmlBlockFrontAfter(Varien_Event_Observer $observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();

        if ($block instanceof MageWorx_MultiFees_Block_Fee) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if ($quote->getSurchargeId()) {
                $transport->setHtml('');
                Mage::getSingleton('checkout/session')->setData('multifees_validation_failed', false);
            }
        }

        return $this;
    }

    /**
     * Disable blocking by multifees
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function disableFeeCheck(Varien_Event_Observer $observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();
        if (!$quote instanceof Mage_Sales_Model_Quote) {
            return $this;
        }

        if ($quote->getSurchargeId()) {
            $quote->setHasError(false);
        }

        return $this;
    }

    public function hideCartBlocks(Varien_Event_Observer $observer)
    {
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Checkout_CartController|Mage_Core_Controller_Front_Action $action */
        $action = $observer->getEvent()->getAction();
        if (!$action instanceof Mage_Checkout_CartController) {
            return $this;
        }

        /** @var string $fullActionName */
        $fullActionName = $action->getFullActionName();
        if ($fullActionName !== 'checkout_cart_index') {
            return $this;
        }

        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        if (!$checkoutSession instanceof Mage_Checkout_Model_Session) {
            return $this;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $checkoutSession->getQuote();
        if (!$quote instanceof Mage_Sales_Model_Quote) {
            return $this;
        }

        /** @var string|int $surchargeId */
        $surchargeId = $quote->getSurchargeId();
        if (!$surchargeId) {
            return $this;
        }

        Mage::app()->getLayout()->getUpdate()->addHandle('mageworx_surcharge_cart_' . $fullActionName);
    }
}