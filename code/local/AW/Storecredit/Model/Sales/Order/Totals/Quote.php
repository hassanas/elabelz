<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Storecredit
 * @version    1.0.5
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

class AW_Storecredit_Model_Sales_Order_Totals_Quote extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    private $counter = 0;
    public function __construct()
    {
        $this->setCode('aw_storecredit');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $_result = parent::collect($address);
        if (!Mage::app()->getStore()->isAdmin()) {
            return $_result;
        }
        $baseTotal = $address->getBaseGrandTotal();
        $total = $address->getGrandTotal();

        $quote = $address->getQuote();
        $storeCreditUsedInOrderQuoteBase = 0;
        $storeCreditUsedInOrderQuote = 0;
        $baseStoreCreditReturnToCustomer = 0;
        $storeCreditOperator = 0;
        if ($baseTotal) {
            $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCreditData($quote->getId());
            //if(count($quoteStorecredits) !== 0) {
                $order_id = Mage::app()->getRequest()->getParam('order_id');
                $order = Mage::getModel("sales/order")->load($order_id);
                if (Mage::app()->getRequest()->getParam('order_id') && $order->getAwStorecredit()) {
                    if ($order->getAwStorecredit() && $order->getCustomerId()) {
                        $storeCreditModel = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($order->getCustomerId());

                        $order_total = $address->getData('base_subtotal') + $address->getBaseShippingAmount() + $address->getData('base_discount_amount') + $address->getData('base_tax_amount');
                        /*
                         * getting total for current store
                         */
                        $order_current_total = $address->getData('subtotal') + $address->getShippingAmount() + $address->getData('discount_amount') + $address->getData('tax_amount');

                        foreach ($order->getAwStorecredit() as $storecredit) {
                            $orderStoreCredit = $storecredit['base_storecredit_amount'];
                            $orderStoreCreditCurrent = $storecredit['storecredit_amount'];
                            if ($orderStoreCredit == NULL) {
                                $orderStoreCredit = 0;
                            }
                            if ($orderStoreCreditCurrent == NULL) {
                                $orderStoreCreditCurrent = 0;
                            }
                        }

                        if ($orderStoreCredit == $order_total) {

                            $storeCreditUsedInOrderQuoteBase = $orderStoreCredit;
                            $storeCreditUsedInOrderQuote = $orderStoreCreditCurrent;

                        } elseif ($order_total < $orderStoreCredit) {
                            /*
                             * When shipping amount chnage
                             * when any order item removed from order
                             * when coupon code added
                             * $order_total = base sub total means with out shipping amount and discount (sc + coupon)
                             * */

                            $baseStoreCreditReturnToCustomer = $orderStoreCredit - $order_total;//10 aed e.g amount reverted back in customer balance
                            $storeCreditReturnToCustomer = $orderStoreCreditCurrent - $order_current_total;//10 aed e.g amount reverted back in customer balance

                            $storeCreditOperator = 1;

                            $storeCreditUsedInOrderQuoteBase = $orderStoreCredit - $baseStoreCreditReturnToCustomer;//base store credit amount added in quote
                            $storeCreditUsedInOrderQuote = $orderStoreCreditCurrent - $storeCreditReturnToCustomer;


                        } elseif ($order_total > $orderStoreCredit) {
                            /*
                             * extera amount of order should be manage from customer store credit if any amount available
                             *
                             * * if  store credit balance is greater then extra amount of order
                             * * if  store credit balance is equal to extra amount of order
                             * * if  store credit balance is less then extra amount of order
                             * * if  store credit balance is zero then we will for for next 3 options
                             * extera amount pay from COD
                             * extera amount pay from CC
                             * extera amount Pay from Coupon code
                             * */

                            $baseStoreCreditReturnToCustomer = $order_total - $orderStoreCredit;
                            $storeCreditReturnToCustomer = $order_current_total - $orderStoreCreditCurrent;

                            if ($storeCreditModel->getBalance() != 0) {
                                if ($storeCreditModel->getBalance() >= $baseStoreCreditReturnToCustomer) {
                                    $storeCreditOperator = 2;

                                    $storeCreditUsedInOrderQuoteBase = $orderStoreCredit + $baseStoreCreditReturnToCustomer;
                                    $storeCreditUsedInOrderQuote = $orderStoreCreditCurrent + $storeCreditReturnToCustomer;

                                } elseif ($storeCreditModel->getBalance() < $baseStoreCreditReturnToCustomer) {
                                    $storeCreditOperator = 2;

                                    $current_balance = $quote->getStore()->roundPrice(
                                        $quote->getStore()->convertPrice($storeCreditModel->getBalance())
                                    );
                                    $storeCreditUsedInOrderQuoteBase = $orderStoreCredit + $storeCreditModel->getBalance();
                                    $storeCreditUsedInOrderQuote = $orderStoreCreditCurrent + $current_balance;

                                    $baseStoreCreditReturnToCustomer = $orderStoreCredit + $storeCreditModel->getBalance();

                                }
                            } elseif ($storeCreditModel->getBalance() == 0) {
                                $storeCreditUsedInOrderQuoteBase = $orderStoreCredit;
                                $storeCreditUsedInOrderQuote = $orderStoreCreditCurrent;
                            }

                        }
                    }

                } else {
                    foreach ($quoteStorecredits as $quoteStorecredit) {
                        $storecreditCollection = Mage::getModel("aw_storecredit/storecredit")->getCollection()
                            ->addFieldToSelect(array('balance'))
                            ->addFieldToFilter('entity_id',$quoteStorecredit->getStorecreditId());
                        foreach($storecreditCollection as $storecredit) {
                            $_baseStorecreditAmount = $storecredit->getBalance();
                            $_storecreditAmount = $quote->getStore()->roundPrice(
                                $quote->getStore()->convertPrice($storecredit->getBalance())
                            );
                        }

                        $order_total = $address->getData('base_subtotal') + $address->getBaseShippingAmount() + $address->getData('base_discount_amount') + $address->getData('base_tax_amount');
                        /*
                         * getting total for current store
                         */
                        $order_current_total = $address->getData('subtotal') + $address->getShippingAmount() + $address->getData('discount_amount') + $address->getData('tax_amount');


                        $storeCreditUsedInOrderQuoteBase = $_baseStorecreditAmount;

                        if ($_baseStorecreditAmount >= $order_total) {
                            $storeCreditUsedInOrderQuoteBase = $order_total;
                        }

                        $storeCreditUsedInOrderQuote = $_storecreditAmount;

                        if ($_storecreditAmount >= $order_current_total) {
                            $storeCreditUsedInOrderQuote = $order_current_total;
                        }

                        $baseTotalStorecreditAmount = 0;
                        $totalStorecreditAmount = 0;
                        $_baseStorecreditAmount = round($storeCreditUsedInOrderQuoteBase, 4);
                        $_storecreditAmount = round($storeCreditUsedInOrderQuote, 4);

                        if (Mage::app()->getRequest()->getParam('order_id')) {
                            $baseStoreCreditReturnToCustomer = $_baseStorecreditAmount;
                            $storeCreditOperator = 2;
                        } else {
                            Mage::helper('aw_storecredit/totals')->saveQuoteStorecreditTotals($quoteStorecredit->getLinkId(), $_baseStorecreditAmount, $_storecreditAmount);
                        }
                    }


                }


                $baseTotalStorecreditAmount = 0;
                $totalStorecreditAmount = 0;
                $_baseStorecreditAmount = round($storeCreditUsedInOrderQuoteBase, 4);
                $_storecreditAmount = round($storeCreditUsedInOrderQuote, 4);
                $baseTotalStorecreditAmount += $_baseStorecreditAmount;
                $totalStorecreditAmount += $_storecreditAmount;

                //cash on delivery charges deduction
                $total = $address->getData('base_subtotal') + $address->getBaseShippingAmount() + $address->getData('base_discount_amount') + $address->getData('base_tax_amount');
                $baseStoreCredit = 0;

                $baseStoreCredit = $storeCreditUsedInOrderQuoteBase;
                // if storecredit amount is less then order total then adding cash on delivery charges
                if ($order_id != NULL && count($quoteStorecredits) !== 0) {

                    if ($baseStoreCredit < $total) {
                        $quote->getPayment()->setMethod('msp_cashondelivery');
                    } elseif ($baseStoreCredit >= $total && ($quote->getPayment()->getMethod()!== 'ccsave' || $quote->getPayment()->getMethod() !== 'telrpayments_cc' || $quote->getPayment()->getMethod() !== 'telrtransparent') ) {
                        $quote->getPayment()->setMethod('free');
                    }
                }


                $address
                    ->getQuote()
                    ->setBaseAwStorecreditAmountUsed($baseTotalStorecreditAmount)
                    ->setAwStorecreditAmountUsed($totalStorecreditAmount)
                    ->setStorecreditAmountBalance($baseStoreCreditReturnToCustomer)
                    ->setStoreCreditOperator($storeCreditOperator);
                $address
                    ->setBaseAwStorecreditAmountUsed($baseTotalStorecreditAmount)
                    ->setAwStorecreditAmountUsed($totalStorecreditAmount)
                    ->setBaseGrandTotal($address->getBaseGrandTotal() - $baseTotalStorecreditAmount)
                    ->setGrandTotal($address->getGrandTotal() - $totalStorecreditAmount);
            //}
            }

        return $_result;

    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $_result = parent::fetch($address);
        if ( ! ($address->getAwStorecreditAmountUsed() > 0)) {
            return $_result;
        }
        $storecredit = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($address->getQuote()->getId());
        $address->addTotal(
            array(
                'code'       => $this->getCode(),
                'title'      => Mage::helper('aw_storecredit')->__('Store Credit'),
                'value'      => -$address->getAwStorecreditAmountUsed(),
                'storecredit' => $storecredit,
            )
        );
        return $_result;
    }
}