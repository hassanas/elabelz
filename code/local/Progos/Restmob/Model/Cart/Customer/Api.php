<?php

class Progos_Restmob_Model_Cart_Customer_Api extends Mage_Checkout_Model_Cart_Customer_Api
{
    /**
     * Set customer for shopping cart
     *
     * @param int $quoteId
     * @param array|object $customerData
     * @param int | string $store
     * @return int
     */
    public function set($quoteId, $customerData, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);

        $customerData = $this->_prepareCustomerData($customerData);
        if (!isset($customerData['mode'])) {
            $this->_fault('customer_mode_is_unknown');
        }

        switch ($customerData['mode']) {
            case self::MODE_CUSTOMER:
                /** @var $customer Mage_Customer_Model_Customer */
                $customer = $this->_getCustomer($customerData['entity_id']);
                $customer->setMode(self::MODE_CUSTOMER);
                break;

            case self::MODE_REGISTER:
            case self::MODE_GUEST:
                /** @var $customer Mage_Customer_Model_Customer */
                $customer = Mage::getModel('customer/customer')
                    ->setData($customerData);

                if ($customer->getMode() == self::MODE_GUEST) {
                    $password = $customer->generatePassword();

                    $customer
                        ->setPassword($password)
                        ->setPasswordConfirmation($password);
                }

                $isCustomerValid = $customer->validate();
                if ($isCustomerValid !== true && is_array($isCustomerValid)) {
                    $this->_fault('customer_data_invalid', implode(PHP_EOL, $isCustomerValid));
                }
                break;
        }

        //removed try an catch because we are going to use try catch when we call this function
        $quote
            ->setCustomer($customer)
            ->setCheckoutMethod($customer->getMode())
            ->setPasswordHash($customer->encryptPassword($customer->getPassword()))
            ->save();

        return true;
    }

    /**
     * @param  int $quoteId
     * @param  array of array|object $customerAddressData
     * @param  int|string $store
     * @return int
     */
    public function setAddresses($quoteId, $customerAddressData, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);

        $customerAddressData = $this->_prepareCustomerAddressData($customerAddressData);
        if (is_null($customerAddressData)) {
            $this->_fault('customer_address_data_empty');
        }

        foreach ($customerAddressData as $addressItem) {
            /** @var $address Mage_Sales_Model_Quote_Address */
            $address = Mage::getModel("sales/quote_address");
            $addressMode = $addressItem['mode'];
            unset($addressItem['mode']);

            if (!empty($addressItem['entity_id'])) {
                $customerAddress = $this->_getCustomerAddress($addressItem['entity_id']);
                if ($customerAddress->getCustomerId() != $quote->getCustomerId()) {
                    $this->_fault('address_not_belong_customer');
                }
                $address->importCustomerAddress($customerAddress);

            } else {
                $address->setData($addressItem);
            }

            $address->implodeStreetAddress();

            if (($validateRes = $address->validate()) !== true) {
                $this->_fault('customer_address_invalid', implode(PHP_EOL, $validateRes));
            }

            switch ($addressMode) {
                case self::ADDRESS_BILLING:
                    $address->setEmail($quote->getCustomer()->getEmail());

                    if (!$quote->isVirtual()) {
                        $usingCase = isset($addressItem['use_for_shipping']) ? (int)$addressItem['use_for_shipping'] : 0;
                        switch ($usingCase) {
                            case 0:
                                $shippingAddress = $quote->getShippingAddress();
                                $shippingAddress->setSameAsBilling(0);
                                break;
                            case 1:
                                $billingAddress = clone $address;
                                $billingAddress->unsAddressId()->unsAddressType();

                                $shippingAddress = $quote->getShippingAddress();
                                $shippingMethod = $shippingAddress->getShippingMethod();
                                $shippingAddress->addData($billingAddress->getData())
                                    ->setSameAsBilling(1)
                                    ->setShippingMethod($shippingMethod)
                                    ->setCollectShippingRates(true);
                                break;
                        }
                    }
                    $quote->setBillingAddress($address);
                    break;

                case self::ADDRESS_SHIPPING:
                    $address->setCollectShippingRates(true)
                        ->setSameAsBilling(0);
                    $quote->setShippingAddress($address);
                    break;
            }

        }

        //Removed try catch because we can call this function in a try block in setshippingaddress function of cartsoap
        $quote
            ->collectTotals()
            ->save();

        return true;
    }
}
