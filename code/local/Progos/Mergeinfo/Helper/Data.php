<?php
/*
 * @author     Hassan Ali Shahzad
 * @package    Progos_Mergeinfo
 * Date    26-05-2017
 */

class Progos_Mergeinfo_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function chagePaymentMethodTo($method_code = null)
    {
        $store	= null;
        if($this->getQuote())
            $store = $this->getQuote()->getStoreId();

        $methods = Mage::helper('payment')->getStoreMethods($store, $this->getQuote());

        $payments = array();
        foreach ($methods as $method)
        {
            if ($this->_PaymentMethodAllowed($method))
                $payments[] = $method;
        }

        $cp = count($payments);
        if ($cp == 0)
        {
            $this->getQuote()->removePayment();
        }
        elseif ($cp == 1)
        {
            $payment = $this->getQuote()->getPayment();
            $payment->setMethod($payments[0]->getCode());
            $method = $payment->getMethodInstance();
            $method->assignData(array('method' => $payments[0]->getCode()));
        }
        else
        {
            $exist = false;
            if (!$method_code)
            {
                if ($this->getQuote()->isVirtual())
                    $method_code = $this->getQuote()->getBillingAddress()->getPaymentMethod();
                else
                    $method_code = $this->getQuote()->getShippingAddress()->getPaymentMethod();
            }

            if($method_code)
            {
                foreach ($payments as $payment)
                {
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
            if (!$method_code || !$exist)
            {
                $method_code = Mage::getStoreConfig('onestepcheckout/general/payment_method');
                foreach ($payments as $payment)
                {
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
}
