<?php
/**
 * @autohor : Humer Batool
 * @date : 21st March 2018
 * @purpose : this code for msp_cashondelivery is rewrite coz with storecredit amount fully applied in sales order create it
 * was giving msp charges
 * frm line no 40 to 57
 */

class Progos_Msp_Model_Quote_Total extends MSP_CashOnDelivery_Model_Quote_Total
{
    protected $_code = 'msp_cashondelivery';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if (!$address->getQuoteId()) {
            return $this;
        }

        Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);

        $_model = Mage::getModel('msp_cashondelivery/cashondelivery');
        $quote = $address->getQuote();

        /*
         * if coupon code fully applied
         */

        if ($quote->getCouponCode()) {
            $totals = $quote->getTotals();
            if ($totals["discount"]) {
                $totalDiscount = $totals["discount"]->getValue();
            }
            if ($totalDiscount != 0 && $address->getSubtotal() != 0) {
                if (($address->getSubtotal() + $totalDiscount) < 0.001) {
                    $quote->getPayment()->setMethod("free");
                }
            }
        }

        $url = Mage::helper('core/url')->getCurrentUrl();
        if (strpos($url, 'sales_order_create') !== false) {
            $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCreditData($quote->getId());
            if (count($quoteStorecredits) !== 0) {

                $total = $address->getBaseSubtotal() + $address->getBaseShippingAmount() + $address->getBaseDiscountAmount() + $address->getBaseTaxAmount();

                foreach ($quoteStorecredits as $quoteStorecredit) {
                    $storecreditCollection = Mage::getModel("aw_storecredit/storecredit")->getCollection()
                        ->addFieldToSelect(array('balance'))
                        ->addFieldToFilter('entity_id', $quoteStorecredit->getStorecreditId());
                    foreach ($storecreditCollection as $storecredit) {
                        $_baseStorecreditAmount = $storecredit->getBalance();
                        $_storecreditAmount = $quote->getStore()->roundPrice(
                            $quote->getStore()->convertPrice($storecredit->getBalance())
                        );
                    }
                }

                if ($_baseStorecreditAmount !== 0 && $total !== 0 && ($_baseStorecreditAmount >= $total)) {
                        $quote->getPayment()->setMethod('free');
                }

            }
        }




        $baseAmount = $_model->getBaseExtraFee($quote);
        $amount = $_model->getExtraFee($quote);

        $baseAmountInclTax = $_model->getBaseExtraFeeInclTax($quote);
        $amountInclTax = $_model->getExtraFeeInclTax($quote);

        if (
            ($quote->getPayment()->getMethod() == $_model->getCode()) &&
            ($address->getAddressType() == Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
        ) {
            $address->setGrandTotal($address->getGrandTotal() + $amount);
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $baseAmount);

            $address->setMspCashondelivery($amount);
            $address->setMspBaseCashondelivery($baseAmount);

            $address->setMspCashondeliveryInclTax($amountInclTax);
            $address->setMspBaseCashondeliveryInclTax($baseAmountInclTax);

            $quote->setMspCashondelivery($amount);
            $quote->setMspBaseCashondelivery($baseAmount);

            $quote->setMspCashondeliveryInclTax($amountInclTax);
            $quote->setMspBaseCashondeliveryInclTax($baseAmountInclTax);
        } elseif ($quote->getPayment()->getMethod() != $_model->getCode()) {
            $address->setGrandTotal(0);
            $address->setBaseGrandTotal(0);

            $address->setMspCashondelivery(0);
            $address->setMspBaseCashondelivery(0);

            $address->setMspCashondeliveryInclTax(0);
            $address->setMspBaseCashondeliveryInclTax(0);

            $quote->setMspCashondelivery(0);
            $quote->setMspBaseCashondelivery(0);

            $quote->setMspCashondeliveryInclTax(0);
            $quote->setMspBaseCashondeliveryInclTax(0);
        }

        return $this;
    }
}