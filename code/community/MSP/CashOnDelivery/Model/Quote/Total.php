<?php
/**
 * IDEALIAGroup srl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@idealiagroup.com so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_CashOnDelivery
 * @copyright  Copyright (c) 2014 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MSP_CashOnDelivery_Model_Quote_Total extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'msp_cashondelivery';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
	   if (!$address->getQuoteId()) {
            return $this;
        }

        parent::collect($address);

        $_model = Mage::getModel('msp_cashondelivery/cashondelivery');
        $quote = $address->getQuote();

        /*
         * if coupon code fully applied
         */

        if($quote->getCouponCode()) {
            $totals = $quote->getTotals();
            if($totals["discount"]) {
                $totalDiscount = $totals["discount"]->getValue();
            }
            if($totalDiscount != 0 && $address->getSubtotal()!= 0) {
                if (($address->getSubtotal() + $totalDiscount) < 0.001) {
                    $quote->getPayment()->setMethod("free");
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

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if (!$address->getQuoteId()) {
            return $this;
        }

        parent::fetch($address);

        if ($address->getAddressType() != Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) {
            return $this;
        }

        $_model = Mage::getModel('msp_cashondelivery/cashondelivery');
        $quote = $address->getQuote();

        $amount = $_model->getExtraFee($quote);
        $baseAmount = $_model->getBaseExtraFee($quote);

        if ($amount > 0 && $quote->getPayment()->getMethod() == $_model->getCode()) {
            $address->addTotal(array(
                'code' => $_model->getCode(),
                'title' => $this->getLabel(),
                'value' => $amount,
                'base_value' => $baseAmount,
            ));
        }

        return $this;
    }

    /**
     * Get Subtotal label
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('msp_cashondelivery')->__('Cash On Delivery');
    }
}