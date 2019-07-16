<?php
class Progos_Ecoprocessor_Model_Ecoprocessor extends Mage_Core_Model_Abstract
{

    public $counter = 0;
    public function getOrders( $weborderIds=null ){
        if($weborderIds == null){
            $pageSize = 10;
            if ((int)Mage::getStoreConfig('api/ecoprocessor/numberOfOrders') > 0) {
                $pageSize = (int)Mage::getStoreConfig('api/ecoprocessor/numberOfOrders');
            }
            $savedOrders = Mage::getModel('ecoprocessor/quote_index')
                ->getCollection()
                ->addFieldToFilter('status', array('eq' => 0))
                ->addFieldToFilter('payment_status', array('eq' => 1))
                ->setCurPage(1)
                ->setPageSize($pageSize);
        }else {
            $savedOrders = Mage::getModel('ecoprocessor/quote_index')
                ->getCollection()
                ->addFieldToFilter('status', array('eq' => 0))
                ->addFieldToFilter("id", array("in" => $weborderIds))
                ->addFieldToFilter('payment_status', array('eq' => 1));
        }
        return $savedOrders;
    }

    public function placeOrders( $weborderIds=null )
    {
        date_default_timezone_set('Asia/Dubai');

        $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
        $ordersToSyncToMageWorx = array();

        $failedOrders = array();
        if($weborderIds == null) {
            $savedOrders = $this->getOrders();
        }else{
            $savedOrders = $this->getOrders($weborderIds);
        }
        /*If Orders exists.*/
        if ($savedOrders->count() > 0) {
            foreach ( $savedOrders as $savedOrder ) {
                $quoteId = $savedOrder->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $quote->setCheckoutMethod('customer')->save();
                try {
                    /* Create Customer */
                    $this->saveCustomer($savedOrder, $quote);

                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    if ($quote->getItemsCount() == 0) {
                        $mdlEcoprocessor = Mage::getModel('ecoprocessor/quote_index');
                        $mdlEcoprocessor->load($savedOrder->getId())->setStatus(2)->save();
                        continue;
                    } elseif (is_null($quote->getCustomerEmail()) || $quote->getCustomerEmail() == "") {
                        $mdlEcoprocessor = Mage::getModel('ecoprocessor/quote_index');
                        $mdlEcoprocessor->load($savedOrder->getId())->setStatus(3)->save();
                        continue;
                    }

                    $shipment_method = $savedOrder->getShippingMethod();
                    $payment_method = $savedOrder->getPayemntMethod();
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

                    $service = Mage::getModel('sales/service_quote', $quote);
                    $service->submitAll();
                    $order = $service->getOrder();
                    if ($order) {

                        $this->counter++;
                        Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                            array('order' => $order, 'quote' => $quote));
                        $increment_id = $order->getIncrementId();
                        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Web Order');
                        if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Gateway has authorized the payment. Reference Id = ' . $savedOrder->getTelrReferenceId() .' Response Code: '. $savedOrder->getTelrRespCode());
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
                        $mdlEcoprocessor = Mage::getModel('ecoprocessor/quote_index');
                        $mdlEcoprocessor->load($savedOrder->getId())->setRealOrderId($increment_id)->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
                        //for storecredit history
                        if ($savedOrder->getStoreCredit() == "1") {
                            $scInfo = $savedOrder->getScInfo();
                            if (trim($scInfo) != "" && trim($scInfo) != null) {
                                $scInfoArr = json_decode($scInfo, true);
                                if (!empty($scInfoArr)) {
                                    $storecreditId = $scInfoArr['id'];
                                    $storecreditTotal = $scInfoArr['total'];
                                    $storecreditSpent = $scInfoArr['spent'];
                                    Mage::helper('ecoprocessor')->addStoreCreditComments($storecreditId, $increment_id, $storecreditTotal, $storecreditSpent);
                                }
                            }
                        }
                        $ordersToSyncToMageWorx[] = $order->getEntityId();
                    }

                }catch( Exception $e ){
                    $mdlEcoprocessor = Mage::getModel('ecoprocessor/quote_index')->load($savedOrder->getId());
                    if($mdlEcoprocessor->getStatus() == 0) {
                        $failedOrders[] = $savedOrder;
                    }
                    if (method_exists($e, 'getCustomMessage')) {
                        $message = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $message = $e->getMessage();
                    }
                    Mage::log('Error array = ' . $quoteId . ' & trace = ' . $e->getTraceAsString() , null, 'cron_error_weborder.log');
                    Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error_weborder.log');
                    continue;
                }
            }

            if(!empty($ordersToSyncToMageWorx)){
                Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
            }

            if (!empty($failedOrders) && Mage::getStoreConfig('api/ecoprocessor/enableRetries')) {
                $this->placeFailedOrdersWithRetry($failedOrders);
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
        if(Mage::getStoreConfig('api/ecoprocessor/numberRetries') != "" && (int)Mage::getStoreConfig('api/ecoprocessor/numberRetries') > 0) {
            $retries = (int)Mage::getStoreConfig('api/ecoprocessor/numberRetries');
        }
        foreach ($failedOrders as $failedOrder) {
            for ($i = 0; $i < $retries; $i++) {
                try {
                    $quoteId = $failedOrder->getQid();
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    if ($quote) {
                        $this->saveCustomer( $failedOrder , $quote );
                        $shipment_method = $failedOrder->getShippingMethod();
                        $payment_method = $failedOrder->getPayemntMethod();
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
                        $service = Mage::getModel('sales/service_quote', $quote);
                        $service->submitAll();
                        $order = $service->getOrder();
                        if ($order) {
                            $this->counter ++;
                            Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                                array('order' => $order, 'quote' => $quote));
                            $increment_id = $order->getIncrementId();
                            Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));

                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Web Order');
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
                            $mdlEcoprocessor = Mage::getModel('ecoprocessor/quote_index');
                            $mdlEcoprocessor->load($failedOrder->getId())->setRealOrderId($increment_id)->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
                            //for storecredit history
                            if ($failedOrder->getStoreCredit() == "1") {
                                $scInfo = $failedOrder->getScInfo();
                                if(trim($scInfo) != "" && trim($scInfo) != null) {
                                    $scInfoArr = json_decode($scInfo, true);
                                    if(!empty($scInfoArr)) {
                                        $storecreditId = $scInfoArr['id'];
                                        $storecreditTotal = $scInfoArr['total'];
                                        $storecreditSpent = $scInfoArr['spent'];
                                        Mage::helper('ecoprocessor')->addStoreCreditComments($storecreditId, $increment_id, $storecreditTotal, $storecreditSpent);
                                    }
                                }
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
                    Mage::log('Error (in Re-Try) cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error_weborder.log');
                    continue;
                }
            }
        }

        if(!empty($ordersToSyncToMageWorx)){
            Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
        }

    }

    /*
     *
     * */
    public function saveCustomer( $savedOrder , $quote ){
        $billing = json_decode($savedOrder['billing_address']);
        $email = $billing->email;
        $storeId = $quote->getStoreId();

        $address_customer_data = array(
            'firstname' => $billing->firstname,
            'lastname' => $billing->lastname,
            'street' => $billing->street[0],
            'city' => $billing->city,
            'country_id' => $billing->country_id,
            'telephone' => $billing->telephone,
            'postcode' => $billing->postcode,
            'region' => $billing->region,
            'region_id' => $billing->region_id
        );

        $webId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        /* Load Customer by Email. If customer exist then return customer data.*/
        $customer = Mage::getModel('customer/customer')->setWebsiteId($webId)->loadByEmail($email);
        if (!$customer->getId()) {
            $customer->setEmail($email);
            $customer->setFirstname($address_customer_data['firstname']);
            $customer->setLastname($address_customer_data['lastname']);
            $customer->setGender($billing->gender);
            $customer->setCustomerCountry($quote->getShippingAddress()->getCountry());
            if($billing->register_account == 1 && isset($billing->customer_password) && trim($billing->customer_password) != ""){
                $cpass = $billing->customer_password;
            }else{
                $cpass = $customer->generatePassword(10);
            }
            $customer->setPassword($cpass);
            $customer->setCredentials($cpass);
            $customer->setWebsiteId( $webId );
            $customer->save();
            if (!Mage::getStoreConfig('system/smtp/disable')) {
                $customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
            }
            $customerAsGuest = array(
                'customer_id' => $customer->getId(),
                'mode' => 'customer'
            );

        } else {
            $customerAsGuest = array(
                'customer_id' => $customer->getId(),
                'mode' => 'customer'
            );
        }
        /* If Customer is gest , Then save address for billing and shipping for default */
        if ($billing->save_in_address_book == 1) {
            $this->addCustomerAdresses($customer->getId(), $address_customer_data);
        }

        /*Assign Quote To Customer*/
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
        $result = $prxy->call($sessionId1, 'cart_customer.set', array($quote->getId(), $customerAsGuest));
        return $customer;
    }

    public function addCustomerAdresses( $customerId , $customerData ){
        $customerAddressApiMdl = Mage::getModel('emapi/customer_address_api');
        $result = $customerAddressApiMdl->create($customerId, array(
            'city' => $customerData['city'],
            'country_id' => $customerData['country_id'],
            'postcode' => $customerData['postcode'],
            'street' => array($customerData['street'], $customerData['street2']),
            'telephone' => $customerData['telephone'],
            'region' => $customerData['region'],
            'region_id' => $customerData['region_id'],
            'lastname' => $customerData['lastname'],
            'firstname' => $customerData['firstname'],
            'is_default_billing' => '1',
            'is_default_shipping' => '1'
        ));
        return $result;
    }
}