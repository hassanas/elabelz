<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Model_Linked_Order_Total_Quote extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    public function __construct()
    {
        $this->setCode('linked');
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('mageworx_orderssurcharge')->__('Linked Order Total');
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

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $address->getQuote();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::registry('ordersedit_order');

        if (!$order) {
            return $this;
        }

        if (($address->getAddressType() == 'billing' && !$quote->isVirtual())) {
            return $this;
        }

        $baseAmount = floatval($quote->getBaseLinkedAmount());
        $amount = $order->getStore()->convertPrice($baseAmount);

        if ($baseAmount) {
            $this->_addAmount($amount);
            $this->_addBaseAmount($baseAmount);

            $quote = $address->getQuote();
            $quote->setBaseSubtotal($quote->getBaseSubtotal() - $baseAmount);
            $quote->setSubtotal($quote->getSubtotal() - $amount);
            $quote->setLinkedAmount($amount);
            $quote->setBaseLinkedAmount($baseAmount);
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
        if (($address->getAddressType() == 'shipping' || $address->getQuote()->isVirtual())) {
            $baseAmount = $address->getBaseLinkedAmount();
            $amount = $address->getLinkedAmount();
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