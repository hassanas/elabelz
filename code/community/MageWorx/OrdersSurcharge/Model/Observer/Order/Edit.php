<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_Observer_Order_Edit extends MageWorx_OrdersSurcharge_Model_Observer_Abstract
{

    public function createSurcharge($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        /** @var Mage_Sales_Model_Order $origOrder */
        $origOrder = $observer->getOrigOrder();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();
        /** @var MageWorx_OrdersSurcharge_Model_Mail $mailer */
        $mailer = Mage::getModel('mageworx_orderssurcharge/mail');

        // Order without surcharge in totals
        if ($order->getBaseLinkedAmount() == 0 || $origOrder->getBaseLinkedAmount() == $quote->getBaseLinkedAmount()) {
            return $this;
        }

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $surcharge */
        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge');

        // Fill new model with data
        $data = array(
            'store_id' => $order->getStoreId(),
            'customer_id' => $order->getCustomerId(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_is_guest' => $order->getCustomerIsGuest(),
            'parent_order_id' => $order->getEntityId(),
            'order_id' => null,
            'parent_order_increment_id' => $order->getIncrementId(),
            'order_increment_id' => null,
            'created_at' => Mage::app()->getLocale()->date(),
            'updated_at' => Mage::app()->getLocale()->date(),
            'base_total' => $order->getBaseLinkedAmount() * -1 + $origOrder->getBaseLinkedAmount(),
            'base_total_due' => $order->getBaseLinkedAmount() * -1 + $origOrder->getBaseLinkedAmount()
        );
        $surcharge->addData($data);

        // Save
        try {
            $surcharge->save();
            $logger = $helper->getLogger();
            $message = 'The email with the payment link was sent to the customer. Surcharge ID #' . $surcharge->getId();
            $logger->log(
                $message,
                $order,
                1
            );
            $mailer->setOrder($order)
                ->setSurcharge($surcharge)
                ->sendEmail();
            $successMessage = $helper->__('The email with the payment link was sent to the customer');
            Mage::getSingleton('adminhtml/session')->addSuccess($successMessage);
        } catch (Exception $e) {
            Mage::logException($e);
            $errorMessage = $helper->__('Unable to create a payment link for the order #%s', $order->getIncrementId());
            Mage::getSingleton('adminhtml/session')->addError($errorMessage);
        }

        return $this;
    }

    /**
     * Add "create/remove surcharge" button to temp totals block
     *
     * @param $observer
     * @return $this
     */
    public function addSurchargeButton($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Totals $block */
        $block = $observer->getBlock();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $block->getSource();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::registry('ordersedit_order');

        if (!$order) {
            return $this;
        }

        if ($order->getStatus() == 'pending') {
            return $this;
        }

        // Do not show the button when a order linked amount eq. quote linked amount
        if ($quote->getBaseGrandTotal() <= $order->getBaseGrandTotal() && $quote->getBaseLinkedAmount() == $order->getBaseLinkedAmount()) {
            return $this;
        }
        $applyWithSurchargeButton = $block->getButtonHtml(
            $helper->__('Apply & Send Payment Link'),
            'orderEdit.saveOrderWithSurcharge();',
            'mw-totals-button mw_floater-right mw_br'
        );
        $block->addButton('apply_surcharge_and_save', $applyWithSurchargeButton);

        $baseLinkedAmountOrder = $order->getBaseLinkedAmount();
        $baseLinkedAmountNew = $quote->getBaseGrandTotal() - ($order->getBaseTotalDue() + $baseLinkedAmountOrder + $order->getBaseGrandTotal());

        if ($baseLinkedAmountNew > 0) {
            $baseAmount = $baseLinkedAmountOrder + $baseLinkedAmountNew;
            $amount = $order->getStore()->convertPrice($baseAmount);
            $surchargeAmount = $order->getStore()->formatPrice($amount);
            $message = $helper->__('Need to be paid');
            $afterTotalsHtml = '<tr class="possible-linked"><td class="label">' . $message . '</td><td>' . $surchargeAmount . '</td></tr>';
            $block->addAfterTotalsHtml($afterTotalsHtml);
        }

        return $this;
    }

    /**
     * Add or remove linked order amount to quote
     *
     * @see MageWorx_OrdersEdit_Model_Edit_Quote
     * @param $observer
     * @return $this
     */
    public function changeNewQuoteLinkedAmount($observer)
    {
        $helper = $this->getHelper();
        if ($helper->isDisabled()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::registry('ordersedit_order');
        /** @var array $data */
        $data = $observer->getNewData();

        if (!$order || !isset($data['surcharge'])) {
            return $this;
        }

        if ($data['surcharge']) {
            $this->addLinkedOrderAmount($quote, $order);
        } else {
            $this->removeLinkedOrderAmount($quote, $order);
        }

        return $this;
    }

    /**
     * Add linked amount to new quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return MageWorx_OrdersSurcharge_Model_Observer_Order_Edit
     */
    protected function addLinkedOrderAmount(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
    {
        $baseAmountOrder = $order->getBaseLinkedAmount();
        $baseAmountNew = $order->getBaseGrandTotal() - $order->getBaseTotalDue() - $quote->getBaseGrandTotal();
        if ($baseAmountNew > 0) {
            return $this;
        } else {
            $baseAmount = $baseAmountOrder + $baseAmountNew;
            $amount = $order->getStore()->convertPrice($baseAmount);
        }

        $quote->setIsSurcharged(true);
        $quote->setBaseLinkedAmount($baseAmount);
        $quote->setLinkedAmount($amount);
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        return $this;
    }

    /**
     * Remove linked amount from quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Order $order
     * @return MageWorx_OrdersSurcharge_Model_Observer_Order_Edit
     */
    protected function removeLinkedOrderAmount(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order)
    {
        $quote->setIsSurcharged(false);
        $quote->setLinkedAmount($order->getLinkedAmount());
        $quote->setBaseLinkedAmount($order->getBaseLinkedAmount());
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        return $this;
    }

}
