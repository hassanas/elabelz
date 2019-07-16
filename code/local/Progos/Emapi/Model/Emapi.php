<?php
class Progos_Emapi_Model_Emapi extends Mage_Core_Model_Abstract
{

    public $counter = 0;
    public function getOrders( $orderIds=null ){
        if($orderIds == null){
            $pageSize = 10;
            if ((int)Mage::getStoreConfig('api/emapi/numberOfOrders') > 0) {
                $pageSize = (int)Mage::getStoreConfig('api/emapi/numberOfOrders');
            }
            $savedOrders = Mage::getModel('restmob/quote_index')
                ->getCollection()
                ->addFieldToFilter('status', array('eq' => 0))
                ->addFieldToFilter('payment_status', array('eq' => 1))
                ->setCurPage(1)
                ->setPageSize($pageSize);
        }else {
            $savedOrders = Mage::getModel('restmob/quote_index')
                ->getCollection()
                ->addFieldToFilter('status', array('eq' => 0))
                ->addFieldToFilter("id", array("in" => $orderIds))
                ->addFieldToFilter('payment_status', array('eq' => 1));
        }
        return $savedOrders;
    }

    public function placeOrders( $orderIds=null )
    {
        date_default_timezone_set('Asia/Dubai');

        $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
        $ordersToSyncToMageWorx = array();

        $failedOrders = array();
        $missingAddresses = array();
        if($orderIds == null) {
            $savedOrders = $this->getOrders();
        }else{
            $savedOrders = $this->getOrders($orderIds);
        }
        //print_r($savedOrders->getData());
        /*If Order is not exist.*/
        if ($savedOrders->count() > 0) {
            foreach ($savedOrders as $savedOrder) {
                $quoteId = $savedOrder->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                try {
                    /*
                     * Conditional code addred for different billing address
                     */
                    if ($savedOrder->getShippingCustomerInfo()) {
                        $this->setShipping($quoteId,$savedOrder->getShippingCustomerInfo(),$savedOrder->getIsBilling(),$savedOrder->getBillingAddress());
                        if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                        }
                    }else if ($savedOrder->getIsBilling()) {
                        $diffBilling[] = array(
                            'address' => json_decode($savedOrder->getBillingAddress(), true),
                            'type' => 'diffbilling',
                            'quote_id' => $quoteId,
                            'customer_id' => $quote->getCustomerId()
                        );
                        $this->repairOrdersWithMissingAddresses($diffBilling);
                        if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                        }
                    }
                }catch (Exception $e) {
                    $failedOrders[] = $savedOrder;
                    if (method_exists($e, 'getCustomMessage')) {
                        $message = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $message = $e->getMessage();
                    }
                    Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                    continue;
                }

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
                    $customer = Mage::getModel('customer/customer')->load($quote->getCustomerId());
                    $customerShippingAddress = $customer->getPrimaryShippingAddress();
                    $customerBillingAddress = $customer->getPrimaryBillingAddress();
                    //check if billing or shipping address exist
                    if($customerShippingAddress || $customerBillingAddress) {
                        $missingAddresses[] = array(
                            'address' => array(),
                            'type' => 'both',
                            'quote_id' => $quoteId,
                            'customer_id' => $quote->getCustomerId()
                        );
                    }else{
                        $mdlRestmob = Mage::getModel('restmob/quote_index');
                        $mdlRestmob->load($savedOrder->getId())->setStatus(4)->save();
                    }
                    continue;
                }else if (($shippingAddress->getFirstname() == null || $shippingAddress->getLastname() == null
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
                    continue;
                }else if (($billingAddress->getFirstname() == null || $billingAddress->getLastname() == null
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
                    continue;
                }

                if ($quote->getItemsCount() == 0) {
                    $mdlRestmob = Mage::getModel('restmob/quote_index');
                    $mdlRestmob->load($savedOrder->getId())->setStatus(2)->save();
                    continue;
                } elseif (is_null($quote->getCustomerEmail()) || $quote->getCustomerEmail() == "") {
                    $mdlRestmob = Mage::getModel('restmob/quote_index');
                    $mdlRestmob->load($savedOrder->getId())->setStatus(3)->save();
                    continue;
                }

                try {
                    if ($quote) {
                        $payment_method = $savedOrder->getPayemntMethod();
                        $shipment_method = $savedOrder->getShippingMethod();
                        $payment_status = $savedOrder->getPaymentStatus();
                        $paymentMethod = array(
                            'po_number' => null,
                            'method' => $payment_method,
                            'cc_cid' => $savedOrder->getCcCid(),
                            'cc_owner' => $savedOrder->getCcOwner(),
                            'cc_number' => $savedOrder->getCcNumber(),
                            'cc_type' => $savedOrder->getCcType(),//'VI',//
                            'cc_exp_year' => $savedOrder->getCcExpYear(),
                            'cc_exp_month' => $savedOrder->getCcExpMonth()

                        );
                        $customerShippingMdl = Mage::getSingleton('restmob/cart_shipping_api');
                        $customerShippingMdl->setShippingMethod($quoteId, $shipment_method);// set shipping methods
                        $customerPaymentMdl = Mage::getSingleton('restmob/cart_payment_api');
                        $quote = $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
                        if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                        }
                        //$quote->collectTotals()->save();
                        $service = Mage::getModel('sales/service_quote', $quote);
                        $service->submitAll();
                        $order = $service->getOrder();
                        if ($order) {
                            $this->counter++;
                            Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                                array('order' => $order, 'quote' => $quote));
                            $increment_id = $order->getIncrementId();
                            Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Elabelz Mobile App '. $savedOrder->getVersionString());
                            if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                                $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Gateway has authorized the payment. Reference Id = '.$savedOrder->getTelrReferenceId() .' Response Code: '. $savedOrder->getTelrRespCode());
                            } elseif ($payment_method == 'telrtransparent' && $payment_status == '0') {
                                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment. Reference Id = ' . $savedOrder->getTelrReferenceId().' Response Code: '. $savedOrder->getTelrRespCode());
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
                            $mdlRestmob = Mage::getModel('restmob/quote_index');
                            $mdlRestmob->load($savedOrder->getId())->setRealOrderId($increment_id)->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
                            if ($savedOrder->getNexmoStatus() == "1") {
                                $this->verifyNexmo($increment_id);
                            }
                            //for storecredit history
                            if ($savedOrder->getStoreCredit() == "1") {
                                $scInfo = $savedOrder->getScInfo();
                                if(trim($scInfo) != "" && trim($scInfo) != null) {
                                    $scInfoArr = json_decode($scInfo, true);
                                    if(!empty($scInfoArr)) {
                                        $storecreditId = $scInfoArr['id'];
                                        $storecreditTotal = $scInfoArr['total'];
                                        $storecreditSpent = $scInfoArr['spent'];
                                        Mage::helper('restmob')->addStoreCreditComments($storecreditId, $increment_id, $storecreditTotal, $storecreditSpent);
                                    }
                                }
                            }

                            if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                                sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                            }
                            $ordersToSyncToMageWorx[] = $order->getEntityId();
                        }
                    }
                } catch (Exception $e) {
                    $mdlRestmob = Mage::getModel('restmob/quote_index')->load($savedOrder->getId());
                    if($mdlRestmob->getStatus() == 0) {
                        $failedOrders[] = $savedOrder;
                    }
                    if (method_exists($e, 'getCustomMessage')) {
                        $message = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $message = $e->getMessage();
                    }
                    Mage::log('Error array = ' . $quoteId . ' & array = ' . json_encode($e), null, 'cron_error.log');
                    Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                    continue;
                }
            }

            if(!empty($ordersToSyncToMageWorx)){
                Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
            }

            if (!empty($failedOrders) && Mage::getStoreConfig('api/emapi/enableRetries')) {
                $this->placeFailedOrdersWithRetry($failedOrders);
            }
            if(!empty($missingAddresses)){
                $this->repairOrdersWithMissingAddresses($missingAddresses);
            }
        }

        return $this->counter;
    }

    /*
 * Function to place the orders that are not placed in first attempt
 */
    public function placeFailedOrdersWithRetry($failedOrders)
    {
        $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
        $ordersToSyncToMageWorx = array();
        $retries = 6;
        if(Mage::getStoreConfig('api/emapi/numberRetries') != "" && (int)Mage::getStoreConfig('api/emapi/numberRetries') > 0) {
            $retries = (int)Mage::getStoreConfig('api/emapi/numberRetries');
        }
        foreach ($failedOrders as $failedOrder) {
            for ($i = 0; $i < $retries; $i++) {
                try {
                    $quoteId = $failedOrder->getQid();
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    if ($quote) {
                        /*
                     * Conditional code addred for different billing address
                     */
                        if ($failedOrder->getIsBilling()) {
                            $diffBilling[] = array(
                                'address' => json_decode($failedOrder->getBillingAddress(), true),
                                'type' => 'diffbilling',
                                'quote_id' => $quoteId,
                                'customer_id' => $quote->getCustomerId()
                            );
                            $this->repairOrdersWithMissingAddresses($diffBilling);
                            if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                                sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                            }
                        }
                        $payment_method = $failedOrder->getPayemntMethod();
                        $shipment_method = $failedOrder->getShippingMethod();
                        $payment_status = $failedOrder->getPaymentStatus();
                        $paymentMethod = array(
                            'po_number' => null,
                            'method' => $payment_method,
                            'cc_cid' => $failedOrder->getCcCid(),
                            'cc_owner' => $failedOrder->getCcOwner(),
                            'cc_number' => $failedOrder->getCcNumber(),
                            'cc_type' => $failedOrder->getCcType(),//'VI',//
                            'cc_exp_year' => $failedOrder->getCcExpYear(),
                            'cc_exp_month' => $failedOrder->getCcExpMonth()

                        );
                        $customerShippingMdl = Mage::getSingleton('restmob/cart_shipping_api');
                        $customerShippingMdl->setShippingMethod($quoteId, $shipment_method);// set shipping methods
                        $customerPaymentMdl = Mage::getSingleton('restmob/cart_payment_api');
                        $quote = $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
                        if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                        }
                        //$quote->collectTotals()->save();
                        $service = Mage::getModel('sales/service_quote', $quote);
                        $service->submitAll();
                        $order = $service->getOrder();
                        if ($order) {
                            $this->counter ++;
                            Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                                array('order' => $order, 'quote' => $quote));
                            $increment_id = $order->getIncrementId();
                            Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));

                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Elabelz Mobile App '. $failedOrder->getVersionString());
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
                            $mdlRestmob = Mage::getModel('restmob/quote_index');
                            $mdlRestmob->load($failedOrder->getId())->setRealOrderId($increment_id)->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
                            if ($failedOrder->getNexmoStatus() == "1") {
                                $this->verifyNexmo($increment_id);
                            }
                            //for storecredit history
                            if ($failedOrder->getStoreCredit() == "1") {
                                $scInfo = $failedOrder->getScInfo();
                                if(trim($scInfo) != "" && trim($scInfo) != null) {
                                    $scInfoArr = json_decode($scInfo, true);
                                    if(!empty($scInfoArr)) {
                                        $storecreditId = $scInfoArr['id'];
                                        $storecreditTotal = $scInfoArr['total'];
                                        $storecreditSpent = $scInfoArr['spent'];
                                        Mage::helper('restmob')->addStoreCreditComments($storecreditId, $increment_id, $storecreditTotal, $storecreditSpent);
                                    }
                                }
                            }

                            if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                                sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                            }
                            $ordersToSyncToMageWorx[] = $order->getEntityId();
                            break;
                        }
                    }
                } catch (Exception $e) {
                    if (method_exists($e, 'getCustomMessage')) {
                        $message = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $message = $e->getMessage();
                    }
                    Mage::log('Error (in Re-Try) cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }
                    continue;
                }
            }
        }

        if(!empty($ordersToSyncToMageWorx)){
            Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
        }
    }

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
                } else if ($missingAddress['type'] == 'diffbilling') {
                    $billingAddress = array(
                        'firstname' => $customerAddress['firstname'],
                        'lastname' => $customerAddress['lastname'],
                        'street' => $customerAddress['street'],
                        'city' => $customerAddress['city'],
                        'country_id' => $customerAddress['country_id'],
                        'telephone' => $customerAddress['telephone'],
                        'email' => $customerAddress['email'],
                        'mode' => 'billing',
                        "is_default_shipping" => 0,
                        "is_default_billing" => 0
                    );
                    $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, array($billingAddress)));
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

    public function setShipping($quoteId,$shippingCustomerInfo,$isBilling,$diffBillingAddress){

        $shippingCustomerInfo = json_decode($shippingCustomerInfo,true);
        $customerAsGuest = $shippingCustomerInfo['customer'];
        $address = $shippingCustomerInfo['address'];

        $shippingAddress = $address;
        $shippingAddress['mode'] = 'shipping';
        $billingAddress = $address;
        $billingAddress['mode'] = 'billing';
        if($isBilling){
            $billingAddress = json_decode($diffBillingAddress,true);
        }
        $arrAddresses = array(
            $shippingAddress,
            $billingAddress
        );
        $storeId = Mage::app()->getStore()->getId();
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
        $prxy->call($sessionId1, 'cart_customer.set', array($quoteId, $customerAsGuest));
        $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, $arrAddresses));
    }

    public function verifyNexmo($increment_id)
    {
        $collection = Mage::helper("marketplace/marketplace")->getRequestId($increment_id);
        foreach ($collection as $col) {
            $id = $col['id'];
            $model = Mage::getModel("marketplace/commission")->load($id);
            $model->setSmsVerifyStatus("yes");
            $model->save();
        }
    }
}