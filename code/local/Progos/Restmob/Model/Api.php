<?php

class Progos_Restmob_Model_Api extends Mage_Checkout_Model_Cart_Api_V2
{
    public function info($quoteId, $store = null)
    {
        if(Mage::getStoreConfig('api/newgroupv2/getProductInfoUsingSession')) {
            return $this->getInfo();
        } else {
            return parent::info($quoteId, $store = null);
        }
    }
    
     /**
     * Retrieve full information about quote
     * @return array
     */
    public function getInfo()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        if ($quote->getGiftMessageId() > 0) {
            $quote->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($quote->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($quote, 'quote');
        $result['shipping_address'] = $this->_getAttributes($quote->getShippingAddress(), 'quote_address');
        $result['billing_address'] = $this->_getAttributes($quote->getBillingAddress(), 'quote_address');
        $result['items'] = array();

        foreach ($quote->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
                );
            }

            $result['items'][] = $this->_getAttributes($item, 'quote_item');
        }

        $result['payment'] = $this->_getAttributes($quote->getPayment(), 'quote_payment');

        return $result;
    }
}
