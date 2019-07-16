<?php
/*
 * @author     Hassan Ali Shahzad
 * @package    Progos_Storecredit
 * Date    01-07-2017
 */

class Progos_Storecredit_Model_Sales_Order_Totals_Quote extends AW_Storecredit_Model_Sales_Order_Totals_Quote
{
    /*
     * This function overrided because on order edit from admin its behaviour is not correct
     * Its changes store credit on order edit as well because Mageworx order edit recreate quote
     * and on recreation its again do the process  and get base store credit and minus it which is not correct
     * so I place check on line number 33 and on line 59 futher explaination is on those lines
     *
     * Humera Batool added on 31stoct 2017
     *@purpose : adding store credit into quote from quote table if its value already exist and assigning payment method accordingly
     * */

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $_result = parent::collect($address);

        $baseTotal = $address->getBaseGrandTotal();
        $total = $address->getGrandTotal();
        $baseTotalStorecreditAmount = 0;
        $totalStorecreditAmount = 0;

        $quote = $address->getQuote();

        Mage::log('1 discount and base tax '. $address->getData('base_discount_amount') . '==' . $address->getData('base_tax_amount') .'  quoteId= ' . $quote->getId() . '.. \n', null, 'cron_error.log');
        // Hassan: This patch applied for those shipping countries to which after conversion tax rate less then .5 and it move to 0 like kuwait stores
        // we will forcefully update tax to 1
            if($address->getAddressType()=="shipping"){
                if($address->getBaseTaxAmount() > 0 && $address->getTaxAmount()==0){
                    $currentRate = ceil(1 / Mage::app()->getStore()->getCurrentCurrencyRate());
                    $address->setTaxAmount(1);
                    $address->setBaseTaxAmount($currentRate);
                    $address->setGrandTotal($address->getGrandTotal() + 1);
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() + $currentRate);
                }
            }

        if ($baseTotal) {
            if (Mage::app()->getRequest()->getParam("order_id")) {
                $order_id = Mage::app()->getRequest()->getParam("order_id");
                $order = Mage::getModel("sales/order")->load($order_id);
                $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($order->getCustomerId());
                if (Mage::getSingleton('adminhtml/session')->getData('storecredit_' . $quote->getId() . '')) {
                    if (count($collection) == 0) {
                        Mage::helper('aw_storecredit/totals')->addStoreCreditToQuote($storeCredit, $quote);
                    }
                }
            }
            /*
             * Code added by Naveed Abbas for mobile app orders
             */
            $mdlRestmob = Mage::getModel('restmob/quote_index');
            $id = $mdlRestmob->getIdByQuoteId($quote->getId());
            if ($id) {
                $_order = $mdlRestmob->load($id);
                if ($_order->getStatus() == 0 && $_order->getStoreCredit() == 1) {
                    $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quote->getId());
                    foreach ($quoteStorecredits as $quoteStorecredit) {
                        $_baseStorecreditAmount = $quoteStorecredit->getBaseStorecreditAmount();
                        $_storecreditAmount = $quoteStorecredit->getStorecreditAmount();

                        $order_total = $address->getData('base_subtotal') + $address->getBaseShippingAmount() + $address->getData('base_discount_amount') + $address->getData('base_tax_amount');

                        /*
                         * getting total for current store
                         */
                        $order_current_total = $address->getData('subtotal') + $address->getShippingAmount() + $address->getData('discount_amount') + $address->getData('tax_amount');


                        if ($_order->getPayemntMethod() == "msp_cashondelivery") {
                            $storeId = $store = Mage::app()->getStore()->getId();
                            $currency = $quote->getQuoteCurrencyCode();
                            $address = $quote->getShippingAddress();
                            Mage::getModel('msp_cashondelivery/quote_total')->collect($address);
                            $zoneType = $address->getCountryId() == Mage::getStoreConfig('shipping/origin/country_id', $storeId) ? 'local' : 'foreign';
                            if ($zoneType == 'local')
                                $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
                            else
                                $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

                            if ($address->getCountryId() == "SA") {
                                $additionalFee = Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_sa');
                                $baseMspFee = $baseMspFee + $additionalFee;
                            }
                            if ($address->getCountryId() == "IQ") {
                                $baseMspFee =  (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');;
                            }

                            $mspFee = Mage::helper('directory')->currencyConvert($baseMspFee, "AED", $currency);
                            $order_total = $order_total + $baseMspFee;
                            $order_current_total = $order_current_total + $mspFee;
                        }

                    }
                    if ($_order->getPayemntMethod() == "free") {
                        $address
                            ->getQuote()
                            ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                            ->setAwStorecreditAmountUsed($_storecreditAmount);
                        $address
                            ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                            ->setAwStorecreditAmountUsed($_storecreditAmount)
                            ->setBaseGrandTotal(0)
                            ->setGrandTotal(0);
                    } else {
                        $address
                            ->getQuote()
                            ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                            ->setAwStorecreditAmountUsed($_storecreditAmount);
                        $address
                            ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                            ->setAwStorecreditAmountUsed($_storecreditAmount)
                            ->setBaseGrandTotal(($order_total - $_baseStorecreditAmount))
                            ->setGrandTotal(($order_current_total - $_storecreditAmount));
                    }
                }
            }else {
                $mdlEco = Mage::getModel('ecoprocessor/quote_index');
                $id = $mdlEco->getIdByQuoteId($quote->getId());
                if ($id) {
                    $_order = $mdlEco->load($id);
                    if ($_order->getStatus() == 0 && $_order->getStoreCredit() == 1) {
                        $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quote->getId());
                        foreach ($quoteStorecredits as $quoteStorecredit) {
                            $_baseStorecreditAmount = $quoteStorecredit->getBaseStorecreditAmount();
                            $_storecreditAmount = $quoteStorecredit->getStorecreditAmount();

                            $order_total = $address->getData('base_subtotal') + $address->getBaseShippingAmount() + $address->getData('base_discount_amount') + $address->getData('base_tax_amount');

                            /*
                             * getting total for current store
                             */
                            $order_current_total = $address->getData('subtotal') + $address->getShippingAmount() + $address->getData('discount_amount') + $address->getData('tax_amount');


                            if ($_order->getPayemntMethod() == "msp_cashondelivery") {
                                $storeId = $store = Mage::app()->getStore()->getId();
                                $currency = $quote->getQuoteCurrencyCode();
                                $address = $quote->getShippingAddress();
                                Mage::getModel('msp_cashondelivery/quote_total')->collect($address);
                                $zoneType = $address->getCountryId() == Mage::getStoreConfig('shipping/origin/country_id', $storeId) ? 'local' : 'foreign';
                                if ($zoneType == 'local')
                                    $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
                                else
                                    $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

                                if ($address->getCountryId() == "SA") {
                                    $additionalFee = Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_sa');
                                    $baseMspFee = $baseMspFee + $additionalFee;
                                }
                                if ($address->getCountryId() == "IQ") {
                                    $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');;
                                }

                                $mspFee = Mage::helper('directory')->currencyConvert($baseMspFee, "AED", $currency);
                                $order_total = $order_total + $baseMspFee;
                                $order_current_total = $order_current_total + $mspFee;
                            }

                        }
                        if ($_order->getPayemntMethod() == "free") {
                            $address
                                ->getQuote()
                                ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                                ->setAwStorecreditAmountUsed($_storecreditAmount);
                            $address
                                ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                                ->setAwStorecreditAmountUsed($_storecreditAmount)
                                ->setBaseGrandTotal(0)
                                ->setGrandTotal(0);
                        } else {
                            $address
                                ->getQuote()
                                ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                                ->setAwStorecreditAmountUsed($_storecreditAmount);
                            $address
                                ->setBaseAwStorecreditAmountUsed($_baseStorecreditAmount)
                                ->setAwStorecreditAmountUsed($_storecreditAmount)
                                ->setBaseGrandTotal(($order_total - $_baseStorecreditAmount))
                                ->setGrandTotal(($order_current_total - $_storecreditAmount));
                        }
                    }
                }
            }
        }
        if( $address->getData('discount_amount') !== 0 && $address->getData('subtotal') !== 0 && (($address->getData('subtotal') + $address->getData('discount_amount'))<=0)){

                if($quote->getPayment()->getMethod() == "msp_cashondelivery"){
                $quote->getPayment()->setMethod('free');

            }
        }
        return $_result;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     *
     * This function overrided to add storecredits used values in temporary block on orders edit
     *
     */
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
                'basestorecredit' => -$address->getBaseAwStorecreditAmountUsed(),
                'storecreditindollers' => -$address->getAwStorecreditAmountUsed(),
                'storecredit' => $storecredit,
            )
        );
        return $_result;
    }

}

