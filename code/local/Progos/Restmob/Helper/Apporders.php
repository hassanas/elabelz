<?php

/**
 * This Helper is created to complete the orders from App
 * working on Eid
 * @category      Progos
 * @package       Progos_Restmob
 * @copyright     Progos Tech Copyright (c) 01-09-2017
 * @author        Hassan Ali Shahzad
 */
class Progos_Restmob_Helper_Apporders extends Mage_Core_Helper_Abstract
{
    /*
     * Note: This function is ued as provided by Naveed Abbas
     *
     * This function will process the app order
     * @param $mobileapporder order object
     * return $flag bool
     * */
    public function processOrder($mobileapporder)
    {
        $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
        $retries = 6;
        if(Mage::getStoreConfig('api/emapi/numberRetries') != "" && (int)Mage::getStoreConfig('api/emapi/numberRetries') > 0) {
            $retries = (int)Mage::getStoreConfig('api/emapi/numberRetries');
        }
        for ($i = 1; $i <= $retries; $i++) {
            try {
                $quoteId = $mobileapporder->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $missingAddresses = array();
                $ordersToSyncToMageWorx = array();
                /*
                 * Condition for missing address quote
                 */
                $shippingAddress = $quote->getShippingAddress();
                $billingAddress = $quote->getBillingAddress();

                if (($shippingAddress->getFirstname() == null || $shippingAddress->getLastname() == null
                        || $shippingAddress->getStreet() == null || $shippingAddress->getCity() == null ||
                        $shippingAddress->getCountryId() == null || $shippingAddress->getTelephone() == null
                        || $shippingAddress->getEmail() == null
                    ) &&
                    ($billingAddress->getFirstname() == null || $billingAddress->getLastname() == null
                        || $billingAddress->getStreet() == null || $billingAddress->getCity() == null ||
                        $billingAddress->getCountryId() == null || $billingAddress->getTelephone() == null
                        || $billingAddress->getEmail() == null
                    )
                ) {
                    $missingAddresses[] = array(
                        'address' => array(),
                        'type' => 'both',
                        'quote_id' => $quoteId,
                        'customer_id' => $quote->getCustomerId()
                    );
                } else if (($shippingAddress->getFirstname() == null || $shippingAddress->getLastname() == null
                    || $shippingAddress->getStreet() == null || $shippingAddress->getCity() == null ||
                    $shippingAddress->getCountryId() == null || $shippingAddress->getTelephone() == null
                    || $shippingAddress->getEmail() == null
                )
                ) {
                    $missingAddresses[] = array(
                        'address' => $billingAddress,
                        'type' => 'shipping',
                        'quote_id' => $quoteId,
                        'customer_id' => $quote->getCustomerId()
                    );
                } else if (($billingAddress->getFirstname() == null || $billingAddress->getLastname() == null
                    || $billingAddress->getStreet() == null || $billingAddress->getCity() == null ||
                    $billingAddress->getCountryId() == null || $billingAddress->getTelephone() == null
                    || $billingAddress->getEmail() == null
                )
                ) {
                    $missingAddresses[] = array(
                        'address' => $shippingAddress,
                        'type' => 'billing',
                        'quote_id' => $quoteId,
                        'customer_id' => $quote->getCustomerId()
                    );
                }
                if (!empty($missingAddresses)) {
                    $this->repairOrdersWithMissingAddresses($missingAddresses);
                }
                if ($quote->getItemsCount() == 0) {
                    $mdlRestmob = Mage::getModel('restmob/quote_index');
                    $mdlRestmob->load($mobileapporder->getId())->setStatus(2)->save();
                    return false;
                } elseif (is_null($quote->getCustomerEmail()) || $quote->getCustomerEmail() == "") {
                    $mdlRestmob = Mage::getModel('restmob/quote_index');
                    $mdlRestmob->load($mobileapporder->getId())->setStatus(3)->save();
                    return false;
                }
                $payment_method = $mobileapporder->getPayemntMethod();
                $shipment_method = $mobileapporder->getShippingMethod();
                $payment_status = $mobileapporder->getPaymentStatus();
                $paymentMethod = array(
                    'po_number' => null,
                    'method' => $payment_method,
                    'cc_cid' => $mobileapporder->getCcCid(),
                    'cc_owner' => $mobileapporder->getCcOwner(),
                    'cc_number' => $mobileapporder->getCcNumber(),
                    'cc_type' => $mobileapporder->getCcType(),//'VI',//
                    'cc_exp_year' => $mobileapporder->getCcExpYear(),
                    'cc_exp_month' => $mobileapporder->getCcExpMonth()
                );
                $customerShippingMdl = Mage::getSingleton('restmob/cart_shipping_api');
                $customerShippingMdl->setShippingMethod($quoteId, $shipment_method);// set shipping methods
                $customerPaymentMdl = Mage::getSingleton('restmob/cart_payment_api');
                $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
                if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                    sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                }
                $quote->collectTotals()->save();
                $service = Mage::getModel('sales/service_quote', $quote);
                $service->submitAll();
                $order = $service->getOrder();
                if ($order) {
                    Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                        array('order' => $order, 'quote' => $quote));
                    $increment_id = $order->getIncrementId();
                    Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));

                    $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Elabelz Mobile App ' . $mobileapporder->getVersionString());
                    if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Gateway has authorized the payment.');
                    } elseif ($payment_method == 'telrtransparent' && $payment_status == '0') {
                        $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.');
                    }
                    Mage::dispatchEvent(
                        'checkout_submit_all_after',
                        array('order' => $order, 'quote' => $quote)
                    );
                    $order->save();
                    if (!is_null($quote->getCustomerEmail()) || $quote->getCustomerEmail() != "") {
                        $order->getSendConfirmation(null);
                        $order->sendNewOrderEmail();
                    }
                    $ordersToSyncToMageWorx[] = $order->getEntityId();
                }
                if (!empty($ordersToSyncToMageWorx)) {
                    Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
                }
                return true;
            } catch (Exception $e) {
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                if($i < $retries){
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }
                    continue;
                }else{
                    return false;
                }
            }
        }
    }

    /*
     *function to repair addresses in case of missing illing or shippinng address
     */
    public function repairOrdersWithMissingAddresses($missingAddresses){
        foreach($missingAddresses as $missingAddress){
            try {
                $storeId = Mage::app()->getStore()->getId();
                $customerAddress = $missingAddress['address'];
                $customerId = $missingAddress['customer_id'];
                $quoteId = $missingAddress['quote_id'];

                if (Mage::getStoreConfig('api/emapi/authuser_field', $storeId) &&
                    Mage::getStoreConfig('api/emapi/authpass_field', $storeId)
                ) {
                    $prxy = new SoapClient(Mage::getStoreConfig('api/emapi/v1_field', $storeId), array(
                        'login' => Mage::getStoreConfig('api/emapi/authuser_field', $storeId),
                        'password' => Mage::getStoreConfig('api/emapi/authpass_field', $storeId)
                    ));
                } else {
                    $prxy = new SoapClient(Mage::getStoreConfig('api/emapi/v1_field', $storeId));
                }
                $sessionId1 = $prxy->login(Mage::getStoreConfig('api/emapi/apiuser_field', $storeId), Mage::getStoreConfig('api/emapi/apikey_field', $storeId));

                if ($missingAddress['type'] == 'both') {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    $address = $customer->getPrimaryShippingAddress();
                    if (!$address) {
                        $address = $customer->getPrimaryBillingAddress();
                    }
                    $addressArr = array(
                        'firstname' => $address->getFirstname(),
                        'lastname' => $address->getLastname(),
                        'street' => $address->getStreet(),
                        'city' => $address->getCity(),
                        'country_id' => $address->getCountryId(),
                        'telephone' => $address->getTelephone(),
                        "email" => $customer->getEmail(),
                        "is_default_shipping" => 0,
                        "is_default_billing" => 0
                    );
                    $shippingAddress = $addressArr;
                    $shippingAddress['mode'] = 'shipping';
                    $billingAddress = $addressArr;
                    $billingAddress['mode'] = 'billing';
                    $arrAddresses = array(
                        $shippingAddress,
                        $billingAddress
                    );
                    $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, $arrAddresses));
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }

                } else if ($missingAddress['type'] == 'shipping') {
                    $addressArr = array(
                        'firstname' => $customerAddress->getFirstname(),
                        'lastname' => $customerAddress->getLastname(),
                        'street' => $customerAddress->getStreet(),
                        'city' => $customerAddress->getCity(),
                        'country_id' => $customerAddress->getCountryId(),
                        'telephone' => $customerAddress->getTelephone(),
                        "email" => $customerAddress->getEmail(),
                        "is_default_shipping" => 0,
                        "is_default_billing" => 0
                    );
                    $shippingAddress = $addressArr;
                    $shippingAddress['mode'] = 'shipping';
                    $billingAddress = $addressArr;
                    $billingAddress['mode'] = 'billing';
                    $arrAddresses = array(
                        $shippingAddress,
                        $billingAddress
                    );
                    $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, $arrAddresses));
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }
                } else if ($missingAddress['type'] == 'billing') {
                    $billingAddress = array(
                        'firstname' => $customerAddress->getFirstname(),
                        'lastname' => $customerAddress->getLastname(),
                        'street' => $customerAddress->getStreet(),
                        'city' => $customerAddress->getCity(),
                        'country_id' => $customerAddress->getCountryId(),
                        'telephone' => $customerAddress->getTelephone(),
                        'email' => $customerAddress->getEmail(),
                        'mode' => 'billing',
                        "is_default_shipping" => 0,
                        "is_default_billing" => 0
                    );
                    $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, array($billingAddress)));
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }
                }
            }catch (Exception $e){
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                Mage::log('error in address repair quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
            }
        }
    }
}