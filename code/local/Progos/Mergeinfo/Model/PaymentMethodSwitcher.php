<?php

/**
 * Author: Hasasn Ali Shahzad
 * Date: 18/05/2017
 * Time: 19:51
 */
class Progos_Mergeinfo_Model_PaymentMethodSwitcher
{
    protected $_help_obj;
    protected $_quote_obj;
    protected $_cust_sess;
    protected $_check_sess;

    public function __construct()
    {
        $this->_help_obj = Mage::helper('checkout');
        $this->_check_sess = Mage::getSingleton('checkout/session');
        $this->_quote_obj = $this->_check_sess->getQuote();
        $this->_cust_sess = Mage::getSingleton('customer/session');
    }

    public function getQuote()
    {
        return $this->_quote_obj;
    }

    public function getCustomerSession()
    {
        return $this->_cust_sess;
    }

    public function getCheckout()
    {
        return $this->_check_sess;
    }

    public function changePaymentMethodTo($method_code = null)
    {
        $store = null;
        if ($this->getQuote())
            $store = $this->getQuote()->getStoreId();
        $methods = Mage::helper('payment')->getStoreMethods($store, $this->getQuote());
        $payments = array();
        foreach ($methods as $method) {
            if ($this->_PaymentMethodAllowed($method))
                $payments[] = $method;
        }
        $cp = count($payments);
        if ($cp == 0) {
            $this->getQuote()->removePayment();
        } elseif ($cp == 1) {
            $payment = $this->getQuote()->getPayment();
            $payment->setMethod($payments[0]->getCode());
            $method = $payment->getMethodInstance();
            $method->assignData(array('method' => $payments[0]->getCode()));
        } else {
            $exist = false;
            if (!$method_code) {
                if ($this->getQuote()->isVirtual())
                    $method_code = $this->getQuote()->getBillingAddress()->getPaymentMethod();
                else
                    $method_code = $this->getQuote()->getShippingAddress()->getPaymentMethod();
            }

            if ($method_code) {
                foreach ($payments as $payment) {
                    if ($method_code !== $payment->getCode())
                        continue;

                    $payment = $this->getQuote()->getPayment();
                    $payment->setMethod($method_code);
                    $method = $payment->getMethodInstance();
                    $method->assignData(array('method' => $method_code));
                    $exist = true;
                    break;
                }

            }

            if (!$exist)
                $this->getQuote()->removePayment();
        }

        return $this;
    }
    protected function _PaymentMethodAllowed($pmnt_method)
    {
        if ($pmnt_method->canUseForCountry($this->getQuote()->getBillingAddress()->getCountry())) {
            $grand_total = $this->getQuote()->getBaseGrandTotal();
            $min = $pmnt_method->getConfigData('min_order_total');
            $max = $pmnt_method->getConfigData('max_order_total');

            if ((!empty($max) && ($grand_total > $max)) || (!empty($min) && ($grand_total < $min)))
                return false;

            return true;
        } else
            return false;
    }

}