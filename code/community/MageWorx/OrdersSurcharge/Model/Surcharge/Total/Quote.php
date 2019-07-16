<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_Surcharge_Total_Quote extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    public function __construct()
    {
        $this->setCode('surcharge');
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('mageworx_orderssurcharge')->__('Order Surcharge');
    }

    /**
     * Collect total information
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this|Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        if (($address->getAddressType() == 'shipping')) {
            return $this;
        }

        $quote = $address->getQuote();
        if (!$quote->getSurchargeId()) {
            return $this;
        }

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $surcharge */
        $surcharge = Mage::getModel('mageworx_orderssurcharge/surcharge')->load($quote->getSurchargeId());

        if (!$surcharge->validateByCustomer()) {
            return $this;
        }

        if ($surcharge->isAlreadyPaid()) {
            return $this;
        }

        $baseAmount = $surcharge->getBaseTotal();
        $amount = Mage::app()->getStore()->convertPrice($baseAmount);

        if ($baseAmount) {
            $this->_addAmount($amount);
            $this->_addBaseAmount($baseAmount);

            $quote = $address->getQuote();
            $quote->setSurchargeAmount($amount);
            $quote->setBaseSurchargeAmount($baseAmount);
        }

        return $this;
    }

    /**
     * Add total information to address
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this|array
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if (($address->getAddressType() == 'billing')) {
            $baseAmount = $address->getBaseSurchargeAmount();
            $amount = $address->getSurchargeAmount();
            if ($baseAmount != 0) {
                $address->addTotal(array(
                    'code' => $this->getCode(),
                    'title' => $this->getLabel(),
                    'value' => $amount
                ));
            }
        }

        return $this;
    }

}