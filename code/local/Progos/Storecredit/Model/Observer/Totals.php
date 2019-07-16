<?php
/*@author: Humera Batool
@ Created at : 16 october 2017
The observer was created for adding registers*/

class Progos_Storecredit_Model_Observer_Totals extends AW_Storecredit_Model_Observer_Totals
{
    public function insertStoreCreditBlock($observer)
    {
        /** @var Varien_Object $transport */
        $transport = $observer->getTransport();
        /** @var Mage_Core_Block_Abstract $block */
        $block = $observer->getBlock();
        if($block->getType() == 'adminhtml/sales_order_view_items' && $block->getNameInLayout() == 'order_items')
        {
            /** @var string $oldHtml */
            $oldHtml = $transport->getHtml();

            /** @var string $couponsBlockHtml */
            $storeCreditBlockHtml = Mage::getSingleton('core/layout')
                ->createBlock('progos_storecredit/adminhtml_sales_order_storecredits', 'storecredits')
                ->toHtml();

            /** @var string $newHtml */
            $newHtml = $oldHtml . $storeCreditBlockHtml; // append storecredit html
            $transport->setHtml($newHtml);
        }

        return;
    }

    public function salesOrderLoadAfter(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if(!Mage::registry('already_check')){
            Mage::register('already_check',true);

            $quoteStorecreditItem = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
            $refundedStorecreditItem = Mage::helper('aw_storecredit/totals')->getRefundedStoreCredit($order->getId());
            $invoiceStorecreditItem = Mage::helper('aw_storecredit/totals')->getInvoicedStorecreditByOrderId($order->getId());

            if (count($invoiceStorecreditItem) > 0) {
                if(!Mage::registry('invoice')) {
                    Mage::register('invoice', $invoiceStorecreditItem);
                }

            }
            elseif(count($quoteStorecreditItem) > 0){
                if(!Mage::registry('quote')) {
                    Mage::register('quote', $quoteStorecreditItem);
                }

            }

            if (count($refundedStorecreditItem) > 0) {
                if(!Mage::registry('creditmemo')) {
                    Mage::register('creditmemo', $refundedStorecreditItem);
                }

            }

        }
        $quote = Mage::registry('quote');
        $invoice = Mage::registry('invoice');
        if($invoice) {
            $order->setAwStorecredit($invoice);
        }
        elseif($quote){
            $order->setAwStorecredit($quote);
        }
        $creditmemo = Mage::registry('creditmemo');
        if($creditmemo) {
            $order->setAwRefundedStorecredit($creditmemo);
        }
        if (!$order->isCanceled()
            && $order->getState() != Mage_Sales_Model_Order::STATE_CLOSED
            && !$order->canCreditmemo()
            && count($quote) > 0
        ) {
            foreach ($order->getAllItems() as $item) {
                if ($item->canRefund()) {
                    $order->setForcedCanCreditmemo(true);
                }
            }
        }
        return $this;

    }
    /**
    * modify final quote for msp
    * @author RT
    * @param object $observer
    * @return void
    **/
    public function getTotalsAfter(Varien_Event_Observer $observer) 
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            return;
        }
        if (!Mage::helper('customer')->getCustomer()->getId()) {
            return;
        }
        $quote = $observer->getEvent()->getQuote();
        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $id = $mdlRestmob->getIdByQuoteId($quote->getId());
        if ($id) {
            return;
        }
        $mdlEco = Mage::getModel('ecoprocessor/quote_index');
        $id = $mdlEco->getIdByQuoteId($quote->getId());
        if ($id) {
            return;
        }
        if($quote->getIsActive() == 0){
            return;
        }
        $paymentByStore = Mage::getStoreConfig('onestepcheckout/general/payment_method', $quote->getStoreId());
        $specificCountryMsp = Mage::getStoreConfig('payment/msp_cashondelivery/specificcountry');

        $storecredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId(Mage::helper('customer')->getCustomer()->getId());
        if (!$quote->getStorecreditInstance()) {
            $quote->setStorecreditInstance($storecredit);
            // add storecredit to quote using existing method
            $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCreditData($quote->getId());
            if (count($quoteStorecredits->getData()) == 0) {
                Mage::helper('aw_storecredit/totals')->addStoreCreditToQuote($storecredit, $quote);
            }
            $quote->collectTotals()->save();
        }
        //base_aw_storecredit_amount_used
        $baseAwStorecreditAmountUsed = $quote->getBaseAwStorecreditAmountUsed();
        //aw_storecredit_amount_used
        $awStorecreditAmountUsed = $quote->getAwStorecreditAmountUsed();
        //final total
        $baseGrandTotal = $quote->getBaseGrandTotal();
        $grandTotal = $quote->getGrandTotal();
        //before tax and other amounts applied
        $subtotal = $quote->getSubtotal();
        $baseSubtotal = $quote->getBaseSubtotal(); 
        // Mage::log(print_r($quote->getShippingAddress()->getData(), true), null, 'storecredit.log');
        if ($quote->getPayment()->getMethod() == NULL) {
            $quote->getPayment()->setMethod('free');
            $quote->collectTotals()->save();
        }
        //if quote payment method is msp and basestorecredit amount is gt than or eq to base subtotal
        //remove msp and apply free payment method to quote
        if ($quote->getPayment()->getMethod() == 'msp_cashondelivery' && $baseAwStorecreditAmountUsed >= $baseSubtotal ) {
            // Mage::log('payment method is msp and basestorecredit amt is gt than subtotal', null, 'storecredit.log');
            //msp_base_cashondelivery_incl_tax
            $baseGrandTotal = $baseGrandTotal - $quote->getMspBaseCashondeliveryInclTax();
            //when base grand total is 0 after msp subtraction
            if ($baseGrandTotal == 0) {
                // Mage::log('grand total is 0', null, 'storecredit.log');
                $quote->getPayment()->setMethod('free');
                $quote->setBaseGrandTotal(0)
                ->setGrandTotal(0);
                $quote->collectTotals();
                $quote->save();
            }
        } 
    }
}



