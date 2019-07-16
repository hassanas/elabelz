<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_Quote extends Mage_Core_Model_Abstract
{

    /**
     * Creates a new quote for the surcharge and replace current cart quote with the newly created surcharged quote.
     * Return the current quote if there is already surcharged for the requested surcharge (was checked by surcharge id)
     *
     * @param int $surchargeId
     * @param Mage_Customer_Model_Customer|null $customer
     * @return Mage_Sales_Model_Quote
     * @throws Exception
     */
    public function createNewQuote($surchargeId, Mage_Customer_Model_Customer $customer = null)
    {
        /** @var int $storeId */
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = $this->getHelper();

        if (!$surchargeId) {
            throw new Exception($helper->__('Can not create quote for empty surcharge id'));
        }

        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');

        if (!$customer) {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $checkoutSession->getCustomer();
        }

        // Check and/or Update (archive) existing quote
        /** @var Mage_Sales_Model_Quote $currentQuote */
        $currentQuote = Mage::getSingleton('checkout/session')->getQuote();

        if ($this->isSurcharged($surchargeId, $currentQuote)) {
            return $currentQuote;
        }

        if ($currentQuote->getIsActive()) {
            $currentQuote->setIsActive(0);
            $storeId = $currentQuote->getStoreId();
            $currentQuote->save();
        }

        // Create and update new quote with surcharge
        /** @var Mage_Sales_Model_Quote $newQuote */
        $newQuote = Mage::getModel('sales/quote');
        $storeId = !empty($storeId) ? $storeId : Mage::app()->getStore()->getId();

        $product = $this->loadSurchargeProduct();

        $newQuote->setCustomer($customer);
        $newQuote->setStoreId($storeId);
        $newQuote->setData('surcharge_id', $surchargeId);
        $newQuote->save();

        // Update checkout
        $checkoutSession->clear();
        $checkoutSession->setQuoteId($newQuote->getId());

        // Update cart
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getSingleton('checkout/cart');
        $cart->setQuote($newQuote);
        $cart->init();
        $cart->addProduct($product->getId());
        $cart->save();

        return $newQuote;
    }

    /**
     * Validate quote
     * Valid quotes:
     * a) with surcharge_id and surcharge item only
     * b) without surcharge_id and surcharge item
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return MageWorx_OrdersSurcharge_Model_Quote
     */
    public function validateQuote(Mage_Sales_Model_Quote $quote)
    {
        $helper = $this->getHelper();
        $notice = $helper->__('It is impossible to make surcharge payment with the other products simultaneously');
        $removeSurchargeNotice = $helper->__('Surcharge was successfully removed from your cart');

        // Do not check not active quotes
        if (!$quote->getIsActive()) {
            return $this;
        }

        $surchargedBySurchargeId = $quote->getSurchargeId();
        $surchargedByProduct = false;

        $items = $quote->getAllItems();
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($items as $item) {
            if ($item->getProduct()->getSku() != MageWorx_OrdersSurcharge_Model_Product::MW_SURCHARGE_PRODUCT_SKU) {
                continue;
            }
            $surchargedByProduct = true;
            break;
        }

        // Valid quote without surcharge
        if (!$surchargedByProduct && !$surchargedBySurchargeId) {
            return $this;
        }

        // Valid quote with surcharge
        if (count($items) == 1 && $surchargedByProduct && $surchargedBySurchargeId) {
            Mage::getSingleton('checkout/session')->setDetailsMultifees(null);
            return $this;
        }

        // Surcharge was removed (empty cart)
        if ($surchargedBySurchargeId && empty($items)) {
            $this->removeSurchargeFromQuote($quote, true);
            Mage::getSingleton('checkout/session')->addNotice($removeSurchargeNotice);
            return $this;
        }

        // Not valid quote with surcharge_id and without surcharge item.
        if ($surchargedBySurchargeId && !$surchargedByProduct) {
            $this->removeSurchargeFromQuote($quote, true);
            Mage::getSingleton('checkout/session')->addNotice($notice);
            return $this;
        }

        // Not valid quote wit surcharge item and without surcharge_id or with other items
        if ($surchargedByProduct && (count($items) != 1 || !$surchargedBySurchargeId)) {
            $this->removeSurchargeFromQuote($quote, true);
            Mage::getSingleton('checkout/session')->addNotice($notice);
            return $this;
        }

        return $this;
    }

    /**
     * Remove surcharge_id and surcharge item from quote
     * Possible: recollect quote totals
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param bool|false $recollect
     * @return MageWorx_OrdersSurcharge_Model_Quote
     */
    public function removeSurchargeFromQuote(Mage_Sales_Model_Quote $quote, $recollect = false)
    {
        $quote->setData('surcharge_id', null);

        $items = $quote->getAllItems();
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($items as $item) {
            if ($item->getProduct()->getSku() == MageWorx_OrdersSurcharge_Model_Product::MW_SURCHARGE_PRODUCT_SKU) {
                $quote->removeItem($item->getId());
            }
        }

        if ($recollect) {
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        $quote->save();

        return $this;
    }

    /**
     * Check is current quote already surcharged and valid for the current surcharge id
     * Returns false by default
     *
     * @param int $surchargeId
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    public function isSurcharged($surchargeId, Mage_Sales_Model_Quote $quote)
    {
        $quoteSurchargeId = $quote->getData('surcharge_id');

        if (!$quoteSurchargeId) {
            return false;
        }

        if (!$surchargeId) {
            return false;
        }

        if ($surchargeId == $quoteSurchargeId && $quote->getItemsCount() == 1) {
            return true;
        }

        return false;
    }

    /**
     * Create & load or just load existing virtual surcharge product with sku == 'mw_virtual_surcharge'
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    protected function loadSurchargeProduct()
    {
        try {
            /** @var MageWorx_OrdersSurcharge_Model_Product $osProduct */
            $osProduct = Mage::getModel('mageworx_orderssurcharge/product');
            /** @var Mage_Catalog_Model_Product $product */
            $product = $osProduct->getProduct();
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }

        return $product;
    }

    /**
     * Get current customer quote from checkout session
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getCustomerQuote()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        return $quote;
    }

    /**
     * Get module helper
     *
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}
