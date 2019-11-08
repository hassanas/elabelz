<?php
/**
 * User: Naveed
 * Date: 10/20/17
 */
/**
 * This file run every hour at 15th and 45th minutes to place orders from mobile app that have an empty quote issue
 */
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
placeEmptyQuoteOrders();

/*
 * Function to place empty quote orders
 */
function placeEmptyQuoteOrders()
{
    date_default_timezone_set('Asia/Dubai');
    $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
    $ordersToSyncToMageWorx = array();
    $pageSize = 10;
    if ((int)Mage::getStoreConfig('api/emapi/empty_quote_limit') > 0) {
        $pageSize = (int)Mage::getStoreConfig('api/emapi/empty_quote_limit');
    }
    $failedOrders = array();
    $savedOrders = Mage::getModel('restmob/quote_index')
        ->getCollection()
        ->addFieldToFilter('status', array('eq' => 2))
        ->addFieldToFilter('cart_items', array('neq' => Null))
        ->addFieldToFilter('payment_status', array('eq' => 1))
        ->setCurPage(1)
        ->setPageSize($pageSize);
    if ($savedOrders->count() > 0) {
        foreach ($savedOrders as $savedOrder) {
            $quoteId = $savedOrder->getQid();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            try {
                /*
                 * Conditional code addred for different billing address
                 */
                if ($savedOrder->getShippingCustomerInfo()) {
                    setShipping($quoteId,$savedOrder->getShippingCustomerInfo(),$savedOrder->getIsBilling(),$savedOrder->getBillingAddress());
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
                    repairOrdersWithMissingAddresses($diffBilling);
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

            try {
                if ($quote) {
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
                        $cartItems = $savedOrder->getCartItems();
                        if(is_null($cartItems) || $cartItems == ""){
                            $mdlRestmob = Mage::getModel('restmob/quote_index');
                            $mdlRestmob->load($savedOrder->getId())->setStatus(4)->save();
                            continue;
                        }else{
                            $cartItems = json_decode($cartItems,true);
                            foreach($cartItems as $cartItem){
                                $productId = (int)$cartItem['id'];
                                $qty = $cartItem['qty'];
                                //changing the stock for empty cart products
                                updateProductStock($productId, $quoteId, $qty);
                                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                                $mainProductId = $parentIds[0];
                                $product = $product = Mage::getModel('catalog/product')->load($mainProductId);
                                $attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                                $child = Mage::getModel('catalog/product')->load($productId);
                                $custom_options = array();
                                foreach ($attributes as $attribute){
                                    $custom_options[$attribute['attribute_id']] = $child->getData($attribute['attribute_code']);
                                }
                                ksort($custom_options);
                                $products = array(array(
                                    'product_id' => $mainProductId,
                                    'qty' => $qty,
                                    'options' => null,
                                    'super_attribute' => $custom_options,
                                    'bundle_option' => null,
                                    'bundle_option_qty' => null,
                                    'links' => null
                                ));
                                $productMdl = Mage::getModel('emapi/product');
                                $productMdl->add($quoteId, $products, Mage::app()->getStore());
                            }
                        }
                    }
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
                    $service = Mage::getModel('sales/service_quote', $quote);
                    $service->submitAll();
                    $order = $service->getOrder();
                    if ($order) {
                        Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                            array('order' => $order, 'quote' => $quote));
                        $increment_id = $order->getIncrementId();
                        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));

                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Elabelz Mobile App '. $savedOrder->getVersionString());
                        if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Gateway has authorized the payment. Reference Id = '.$savedOrder->getTelrReferenceId());
                        } elseif ($payment_method == 'telrtransparent' && $payment_status == '0') {
                            $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.');
                        }
                        Mage::dispatchEvent(
                            'checkout_submit_all_after',
                            array('order' => $order, 'quote' => $quote)
                        );
                        $order->save();
                        $order->getSendConfirmation(null);
                        $order->sendNewOrderEmail();
                        $mdlRestmob = Mage::getModel('restmob/quote_index');
                        $mdlRestmob->load($savedOrder->getId())->setRealOrderId($increment_id)->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();
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
                if($mdlRestmob->getStatus() == 2) {
                    $failedOrders[] = $savedOrder;
                }
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                if (is_null($message)) {
                    $message = Mage::helper('restmob')->checkError($e->getMessage());
                } elseif (strstr($message, '_')) {
                    $message = Mage::helper('restmob')->checkError($message);
                }
                Mage::log('Error array = ' . $quoteId . ' & array = ' . json_encode($e), null, 'cron_error.log');
                Mage::log('Error in cron quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                continue;
            }
        }

        if(!empty($ordersToSyncToMageWorx)){
            Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
        }

        if (!empty($failedOrders) && Mage::getStoreConfig('api/emapi/enable_empty_quote_retries')) {
            placeFailedOrdersWithRetry($failedOrders);
        }
    }
}


/*
 * Function to place the orders that are not placed in first attempt
 */
function placeFailedOrdersWithRetry($failedOrders)
{
    $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
    $ordersToSyncToMageWorx = array();
    $retries = 6;
    if ((int)Mage::getStoreConfig('api/emapi/empty_quote_retries') > 0) {
        $retries = (int)Mage::getStoreConfig('api/emapi/empty_quote_retries');
    }
    foreach ($failedOrders as $failedOrder) {
        for ($i = 0; $i < $retries; $i++) {
            try {
                $notPlaced = false;
                $quoteId = $failedOrder->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                if ($quote) {
                    if ($quote->getItemsCount() == 0) {
                        $cartItems = $failedOrder->getCartItems();
                        if(is_null($cartItems) || $cartItems == ""){
                            $mdlRestmob = Mage::getModel('restmob/quote_index');
                            $mdlRestmob->load($failedOrder->getId())->setStatus(4)->save();
                            continue;
                        }else{
                            $cartItems = json_decode($cartItems,true);
                            foreach($cartItems as $cartItem){
                                $productId = (int)$cartItem['id'];
                                $qty = $cartItem['qty'];
                                //changing the stock for empty cart products
                                updateProductStock($productId, $quoteId, $qty);
                                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                                $mainProductId = $parentIds[0];
                                $product = $product = Mage::getModel('catalog/product')->load($mainProductId);
                                $attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                                $child = Mage::getModel('catalog/product')->load($productId);
                                $options=array();
                                $custom_options = array();
                                foreach ($attributes as $attribute){
                                    $custom_options[$attribute['attribute_id']] = $child->getData($attribute['attribute_code']);
                                }
                                ksort($custom_options);
                                $products = array(array(
                                    'product_id' => $mainProductId,
                                    'qty' => $qty,
                                    'options' => null,
                                    'super_attribute' => $custom_options,
                                    'bundle_option' => null,
                                    'bundle_option_qty' => null,
                                    'links' => null
                                ));
                                $productMdl = Mage::getModel('emapi/product');
                                $productMdl->add($quoteId, $products, Mage::app()->getStore());
                            }
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
                        $order->getSendConfirmation(null);
                        $order->sendNewOrderEmail();
                        $mdlRestmob = Mage::getModel('restmob/quote_index');
                        $mdlRestmob->load($failedOrder->getId())->setRealOrderId($increment_id)->setStatus(1)->setUpdatedAt(Varien_Date::now())->save();

                        //for storecredit history
                        $increment_id = $order->getIncrementId();
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
                    } else {
                        if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                            sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                        }
                        continue;
                    }
                }
            } catch (Exception $e) {
                $notPlaced = true;
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                if (is_null($message)) {
                    $message = Mage::helper('restmob')->checkError($e->getMessage());
                } elseif (strstr($message, '_')) {
                    $message = Mage::helper('restmob')->checkError($message);
                }
                Mage::log('Error in cron retry quoteId= ' . $quoteId . ' & message = ' . $message . ' \n', null, 'cron_error.log');
                if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                    sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                }
                continue;
            }
        }
    if($notPlaced){
        $senderName = Mage::getStoreConfig('trans_email/ident_general/name');
        $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');
        $mail = Mage::getModel('core/email')
            ->setToName('Naveed Abbas')
            ->setToEmail('naveed.abbas@progos.org')
            ->setBody('Order failed to place, id = '.$failedOrder->getId())
            ->setSubject('Order failed')
            ->setFromEmail($senderEmail)
            ->setFromName($senderName)
            ->setType('html');
        try{
            $mail->send();
        }
        catch(Exception $error)
        {
            Mage::log('Order failed to place, id = '.$failedOrder->getId() . ' \n', null, 'failed_orders.log');
        }
        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $mdlRestmob->load($failedOrder->getId())->setStatus(5)->save();
    }
    }

    if(!empty($ordersToSyncToMageWorx)){
        Mage::helper('progos_ordersgrid')->syncMobileCronOrders($ordersToSyncToMageWorx);
    }

}

function updateProductStock($productId, $quoteId, $qty){
    $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
    if ($stock->getQty() == 0 || $stock->getQty() < $qty) {
        $stock->setQty($qty);
    }
    if ($stock->getIsInStock() == 0) {
        $stock->setIsInStock(1);
    }
    $stock->save();
    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
    $storeId = $quote->getStoreId();
    Mage::getModel('catalog/product_status')->updateProductStatus($productId, $storeId, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
}

function repairOrdersWithMissingAddresses($missingAddresses){
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

function setShipping($quoteId,$shippingCustomerInfo,$isBilling,$diffBillingAddress){

    $shippingCustomerInfo = json_decode($shippingCustomerInfo,true);
    $customerAsGuest = $shippingCustomerInfo['customer'];
    $address = $shippingCustomerInfo['address'];
    $createAddress = $shippingCustomerInfo['create_address'];
    $addressCustomerData = $shippingCustomerInfo['address_customer_data'];

    if($createAddress){
        addCustomerAdresses($customerAsGuest['customer_id'],$addressCustomerData);
    }

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

function addCustomerAdresses( $customerId, $customerData ){
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