<?php
require_once dirname(__FILE__) . '/SoapController.php';

class Progos_Restmob_CheckoutSoapController extends Progos_Restmob_SoapController
{
    public function indexAction()
    {
        return;
    }

    public function processPaymentSoapless($quoteId, $payment_data)
    {
        $payment_method = $payment_data['payment_method'];
        $shipment_method = $payment_data['shipment_method'];

        $paymentMethod = array(
            'po_number' => null,
            'method' => $payment_method,
            'cc_cid' => $payment_data['cc_cid'],
            'cc_owner' => $payment_data['cc_owner'],
            'cc_number' => $payment_data['cc_number'],
            'cc_type' => $payment_data['cc_type'],//'VI',//
            'cc_exp_year' => $payment_data['cc_exp_year'],
            'cc_exp_month' => $payment_data['cc_exp_month']

        );
        $customerShippingMdl = Mage::getSingleton('restmob/cart_shipping_api');
        $result = $customerShippingMdl->setShippingMethod($quoteId, $shipment_method);// set shipping methods
        if ($result) {
            // shipping address set sucessfully
            $customerPaymentMdl = Mage::getSingleton('restmob/cart_payment_api');
            $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
            if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
            }
            Mage::log('Success in 1st attempt on Progos_Restmob_CheckoutSoapController processPaymentSoapless1.. \n', null, 'mobile_app.log');
            return true;
        }
    }

    /*Not in Used*/
    protected function processPayment($sessionId, $quoteId, $payment_data)
    {
        parent::setProxy();
        $proxy = $this->proxy;
        $payment_method = $payment_data['payment_method'];
        $shipment_method = $payment_data['shipment_method'];

        $result = $proxy->shoppingCartShippingMethod((object)array('sessionId' => $sessionId, 'quoteId' => $quoteId, 'shippingMethod' => $shipment_method));
        $paymentMethod = array(
            'po_number' => null,
            'method' => $payment_method,
            'cc_cid' => $payment_data['cc_cid'],
            'cc_owner' => $payment_data['cc_owner'],
            'cc_number' => $payment_data['cc_number'],
            'cc_type' => $payment_data['cc_type'],//'VI',//
            'cc_exp_year' => $payment_data['cc_exp_year'],
            'cc_exp_month' => $payment_data['cc_exp_month']
        );
        $result = $proxy->shoppingCartPaymentMethod((object)array('sessionId' => $sessionId, 'quoteId' => $quoteId, 'paymentData' => $paymentMethod));
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        $quote->collectTotals()->save();
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        $orderId = $order->getIncrementId();
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Elabelz Mobile App');
        $order->save();
        if ($payment_data['payment_method'] != 'telrtransparent') {
            $order->getSendConfirmation(null);
            $order->sendNewOrderEmail();
        }
        return $orderId;
    }

    public function subunsubnewsletter($email)
    {
        $emailExist = Mage::getModel('newsletter/subscriber')->load($email, 'subscriber_email');
        if ($emailExist->getSubscriberStatus() != 1) {
            Mage::getModel('newsletter/subscriber')->load($email, 'subscriber_email')->subscribe($email);
        }
    }

    public function processPaymentAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        date_default_timezone_set('Asia/Dubai');
        $sessionId = $this->getRequest()->getPost('sid');
        $quoteId = $this->getRequest()->getPost('qid');
        $newsletter = $this->getRequest()->getPost('newsletter');
        $customer_id = $this->getRequest()->getPost('customer_id');
        $email = $this->getRequest()->getPost('email');
        Mage::log('Checkout initiated quoteId = '.$quoteId.' at '.date('d/m H:i:s:u').'.. '. '\n', null, 'checkout_time.log');
        if ($newsletter && $customer_id) {
            $customer = Mage::getModel('customer/customer')->load($customer_id);
            $email = $customer->getEmail();
            $this->subunsubnewsletter($email);
        } elseif ($newsletter && $email) {
            $this->subunsubnewsletter($email);
        }
        $payment_data = array(
            'payment_method' => $this->getRequest()->getPost('payment_method'),
            'shipment_method' => $this->getRequest()->getPost('shipment_method'),
            'cc_cid' => $this->getRequest()->getPost('cc_cid'),
            'cc_owner' => $this->getRequest()->getPost('cc_owner'),
            'cc_number' => $this->getRequest()->getPost('cc_number'),
            'cc_type' => $this->getRequest()->getPost('cc_type'),
            'cc_exp_year' => $this->getRequest()->getPost('cc_exp_year'),
            'cc_exp_month' => $this->getRequest()->getPost('cc_exp_month'),
        );
        $response = array('success' => 0, 'message' => '', 'res' => false, 'retry' => 0, 'sid' => $sessionId);
        if (Mage::getStoreConfig('api/emapi/checkout')) {
            $res = false;
            $attempts = 1;
            while ($attempts <= 2) {
                try {
                    $res = $this->processPaymentSoapless($quoteId, $payment_data);
                    $response['retry'] = 0;
                    Mage::log('Success in ' . $attempts . ' attempt in try catch quoteId = ' . $quoteId . ' on Progos_Restmob_CheckoutSoapController processPaymentSoapless processPaymentAction.. \n', null, 'mobile_app.log');
                } catch (Exception $e) {
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }
                    $response['error_code'] = $e->getCode();
                    if (method_exists($e, 'getCustomMessage')) {
                        $response['error_message'] = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $response['error_message'] = $e->getMessage();
                    }
                    if (is_null($response['error_message'])) {
                        $response['error_message'] = Mage::helper('restmob')->checkError($e->getMessage());
                    } elseif (strstr($response['error_message'], '_')) {
                        $response['error_message'] = Mage::helper('restmob')->checkError($response['error_message']);
                    }
                    $errorMessage = strtolower($response['error_message']);
                    if (strstr($errorMessage, "the requested quantity") || strstr($errorMessage, "number mismatch") || strstr($errorMessage, "invalid credit") || strstr($errorMessage, "card type is not") || strstr($errorMessage, "card expiration date")) {
                        $response['retry'] = 0;
                        $response['message'] = $errorMessage;// here mobile app will change screen to shipping and billing address
                    } else {
                        $response['retry'] = 1;
                        $response['message'] = 'Your order is not processed, please try again';// here mobile app will change screen to shipping and billing address
                    }
                    Mage::log('Checkout error quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . ' Message = ' . $response['error_message'] . '.. ' . '\n', null, 'checkout_time.log');
                    Mage::log('Failed in ' . $attempts . ' try  quoteId = ' . $quoteId . ' Progos_Restmob_CheckoutSoapController processPaymentAction action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
                    $attempts++;
                    continue;
                }
                break;
            }
            if ($res) {
                try {
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $quote->collectTotals()->save();
                    $service = Mage::getModel('sales/service_quote', $quote);
                    $service->submitAll();
                    $order = $service->getOrder();
                    if ($order) {
                        Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                            array('order' => $order, 'quote' => $quote));
                        $orderId = $order->getIncrementId();
                        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Elabelz Mobile App');
                        $order->save();
                        if ($payment_data['payment_method'] != 'telrtransparent') {
                            $order->getSendConfirmation(null);
                            $order->sendNewOrderEmail();
                        }
                        Mage::dispatchEvent(
                            'checkout_submit_all_after',
                            array('order' => $order, 'quote' => $quote)
                        );
                        $res = $orderId;// Increment ID
                        if ($payment_data['payment_method'] == 'telrtransparent') {
                            $response['webViewUrl'] = Mage::getUrl('restmob/CheckoutSoap/redirectOldCc', array('_secure' => true, '_query' => 'cvv=' . $payment_data['cc_cid'] . '&oid=' . $res));
                        } else {
                            $quote = Mage::getModel('sales/quote')->load($quoteId);
                            $quote->setIsActive(0)->save();
                        }
                        $response['success'] = 1;
                        $response['message'] = 'Order processed successfully';
                        $response['res'] = $res;
                        Mage::log('Checkout completed successfully quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . '.. ' . '\n', null, 'checkout_time.log');
                    } else {
                        $response['retry'] = 1;
                        $response['error_code'] = 0;
                        $response['message'] = 'Your order is not processed, please try again';// here mobile app will change screen to shipping and billing address
                        $response['error_message'] = "Call to a member function getIncrementId()";
                        Mage::log('Checkout error quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . ' Message = ' . $response['error_message'] . '.. ' . '\n', null, 'checkout_time.log');
                    }
                } catch (Exception $e) {
                    $response['error_code'] = $e->getCode();
                    if (method_exists($e, 'getCustomMessage')) {
                        $response['error_message'] = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $response['error_message'] = $e->getMessage();
                    }
                    if (is_null($response['error_message'])) {
                        $response['error_message'] = Mage::helper('restmob')->checkError($e->getMessage());
                    } elseif (strstr($response['error_message'], '_')) {
                        $response['error_message'] = Mage::helper('restmob')->checkError($response['error_message']);
                    }
                    $errorMessage = strtolower($response['error_message']);
                    if (strstr($errorMessage, "the requested quantity") || strstr($errorMessage, "number mismatch") || strstr($errorMessage, "invalid credit") || strstr($errorMessage, "card type is not") || strstr($errorMessage, "card expiration date")) {
                        $response['retry'] = 0;
                        $response['message'] = $errorMessage;// here mobile app will change screen to shipping and billing address
                    } else {
                        $response['retry'] = 1;
                        $response['message'] = 'Your order is not processed, please try again';// here mobile app will change screen to shipping and billing address
                    }
                    Mage::log('Checkout error quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . ' Message = ' . $response['error_message'] . '.. ' . '\n', null, 'checkout_time.log');
                    Mage::log('soap less error quoteId = ' . $quoteId . ' on Progos_Restmob_CheckoutSoapController processPaymentAction action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
                }
            }
        } elseif (Mage::getStoreConfig('api/emapi/enableNewCheckout')) {
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            if ($this->getRequest()->getPost('payment_method') != 'free' && $quote->getItemsCount() > 0) {
                try {
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    if($quote->getItemsCount() == 0){
                    }
                    $res = $quote->getReservedOrderId();
                    if (!$res) {
                        $quote->reserveOrderId()->save();
                        $res = $quote->getReservedOrderId();
                    }
                    $paymentStatus = 0;
                    if ($this->getRequest()->getPost('payment_method') != 'telrtransparent') {
                        $paymentStatus = 1;
                    }
                    if ($this->getRequest()->getPost('payment_method') == 'telrtransparent') {
                        $dataArr = array(
                            'cc_type' => $this->getRequest()->getPost('cc_type'),
                            'cc_number' => $this->getRequest()->getPost('cc_number'),
                            'cc_exy' => $this->getRequest()->getPost('cc_exp_year'),
                            'cc_exm' => $this->getRequest()->getPost('cc_exp_month'),
                            'cc_cvv' => $this->getRequest()->getPost('cc_cid')

                        );
                        $mdlPayment = Mage::getModel('telrtransparent/standard');
                        $mdlPayment->validateOnly($dataArr);
                    }
                    $mdlRestmob = Mage::getModel('restmob/quote_index');
                    $id = $mdlRestmob->getIdByReserveId($res);
                    if ($id) {
                        $mdlRestmob->load($id);
                        $mdlRestmob->setQid($quoteId);
                        $mdlRestmob->setPayemntMethod($this->getRequest()->getPost('payment_method'));
                        $mdlRestmob->setShippingMethod($this->getRequest()->getPost('shipment_method'));
                        $mdlRestmob->setPaymentStatus($paymentStatus);
                        $mdlRestmob->setStatus(0);
                        $mdlRestmob->setReservedOrderId($res);
                        $mdlRestmob->setCcCid($this->getRequest()->getPost('cc_cid'));
                        $mdlRestmob->setCcOwner($this->getRequest()->getPost('cc_owner'));
                        $mdlRestmob->setCcNumber($this->getRequest()->getPost('cc_number'));
                        $mdlRestmob->setCcType($this->getRequest()->getPost('cc_type'));
                        $mdlRestmob->setCcExpYear($this->getRequest()->getPost('cc_exp_year'));
                        $mdlRestmob->setCcExpMonth($this->getRequest()->getPost('cc_exp_month'));
                        $mdlRestmob->setCreatedAt(Varien_Date::now());
                        $mdlRestmob->save();
                    } else {
                        $mdlRestmob->setQid($quoteId);
                        $mdlRestmob->setPayemntMethod($this->getRequest()->getPost('payment_method'));
                        $mdlRestmob->setShippingMethod($this->getRequest()->getPost('shipment_method'));
                        $mdlRestmob->setPaymentStatus($paymentStatus);
                        $mdlRestmob->setStatus(0);
                        $mdlRestmob->setReservedOrderId($res);
                        $mdlRestmob->setCcCid($this->getRequest()->getPost('cc_cid'));
                        $mdlRestmob->setCcOwner($this->getRequest()->getPost('cc_owner'));
                        $mdlRestmob->setCcNumber($this->getRequest()->getPost('cc_number'));
                        $mdlRestmob->setCcType($this->getRequest()->getPost('cc_type'));
                        $mdlRestmob->setCcExpYear($this->getRequest()->getPost('cc_exp_year'));
                        $mdlRestmob->setCcExpMonth($this->getRequest()->getPost('cc_exp_month'));
                        $mdlRestmob->setCreatedAt(Varien_Date::now());
                        $mdlRestmob->save();
                    }
                    if ($this->getRequest()->getParam('payment_method') == 'telrtransparent') {
                        $response['webViewUrl'] = Mage::getUrl('restmob/CheckoutSoap/redirect', array('_secure' => true, '_query' => 'cvv=' . $this->getRequest()->getPost('cc_cid') . '&oid=' . $res . '&qid=' . $quoteId));
                    }
                    $response['success'] = 1;
                    $response['message'] = 'Order processed successfully';
                    $response['res'] = $res;
                    Mage::log('Checkout completed successfully quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . '.. ' . '\n', null, 'checkout_time.log');
                } catch (Exception $e) {
                    $response['error_code'] = $e->getCode();
                    if (method_exists($e, 'getCustomMessage')) {
                        $response['error_message'] = $e->getCustomMessage();
                    } elseif (method_exists($e, 'getMessage')) {
                        $response['error_message'] = $e->getMessage();
                    }
                    if (is_null($response['error_message'])) {
                        $response['error_message'] = Mage::helper('restmob')->checkError($e->getMessage());
                    } elseif (strstr($response['error_message'], '_')) {
                        $response['error_message'] = Mage::helper('restmob')->checkError($response['error_message']);
                    }
                    $errorMessage = strtolower($response['error_message']);
                    if (strstr($errorMessage, "the requested quantity") || strstr($errorMessage, "number mismatch") || strstr($errorMessage, "invalid credit") || strstr($errorMessage, "card type is not") || strstr($errorMessage, "card expiration date")) {
                        $response['retry'] = 0;
                        $response['message'] = $errorMessage;// here mobile app will change screen to shipping and billing address
                    } else {
                        $response['retry'] = 1;
                        $response['message'] = 'Your order is not processed, please try again';// here mobile app will change screen to shipping and billing address
                    }
                    Mage::log('Checkout error quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . ' Message = ' . $response['error_message'] . '.. ' . '\n', null, 'checkout_time.log');
                    Mage::log('soap less error quoteId = ' . $quoteId . ' on Progos_Restmob_CheckoutSoapController processPaymentAction action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
                }
            }else{
                $response['error_code'] = 0;
                $response['message'] = "Nothing in your bag";
            }
        } else {// Not in used this condition will optimize later
            $sessionId = parent::loginembedded();
            try {
                $res = $this->processPayment($sessionId, $quoteId, $payment_data);
                if ($payment_data['payment_method'] == 'telrtransparent') {
                    $response['webViewUrl'] = Mage::getUrl('restmob/CheckoutSoap/redirectOldCc', array('_secure' => true, '_query' => 'cvv=' . $payment_data['cc_cid'] . '&oid=' . $res));
                } else {
                    $quote = Mage::getModel('sales/quote')->load($quoteId);
                    $quote->setIsActive(0)->save();
                }
                $response['success'] = 1;
                $response['message'] = 'Order processed successfully';
                $response['res'] = $res;
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                $response['message'] = $e->getMessage();
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    // The redirect action is to send and confirm transparent payment
    public function redirectAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'restmob', array('template' => 'restmob/redirect.phtml'));
        $this->getLayout()->getBlock('head')->append($block);
        $this->renderLayout();
    }
    public function redirectOldCcAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'restmob', array('template' => 'restmob/redirect_oldcc.phtml'));
        $this->getLayout()->getBlock('head')->append($block);
        $this->renderLayout();
    }

    // The response action is triggered when your gateway sends back a response after processing the customer's payment
    public function beforeSuccessOldCcAction()
    {
        $orderId = $this->getRequest()->getParam('oid');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Gateway has authorized the payment.');
        $order->setEmailSent(true);
        $order->save();
        $order->getSendConfirmation(null);
        $order->sendNewOrderEmail();
        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->setIsActive(0)->save();
        Mage_Core_Controller_Varien_Action::_redirect('restmob/CheckoutSoap/success', array('_secure' => true));
    }

    // The cancel action is triggered when an order is to be cancelled
    public function beforeCancelOldCcAction()
    {
        $orderId = $this->getRequest()->getParam('oid');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($order->getId()) {
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.')->save();
            }
        }
        Mage_Core_Controller_Varien_Action::_redirect('restmob/CheckoutSoap/cancel', array('_secure' => true));
    }

    // Placeholder url for app
    public function successAction()
    {
    }

    // Placeholder URL for app
    public function cancelAction()
    {
    }


    // Placeholder url for app
    public function successcallbackAction()
    {
        //$reservedOrderId = $this->getRequest()->getParam('oid');
        $reservedOrderId = $_POST['cart_id'];
        $telrReferenceId = $_POST['auth_tranref'];
        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $id = $mdlRestmob->getIdByReserveId($reservedOrderId);
        $mdlRestmob->load($id)->setPaymentStatus(1)->setTelrReferenceId($telrReferenceId)->save();
    }

    // Placeholder URL for app
    public function cancelcallbackAction()
    {
    }

    /*Not in used*/
    public function allowedCountriesAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $_countries = Mage::getResourceModel('directory/country_collection')
            ->loadByStore()
            ->toOptionArray(false);
        $response = array("status" => "0", "countries" => array());
        $countries = array();
        if (count($_countries) > 0) {
            foreach ($_countries as $_country) {
                $countries[$_country['value']] = $_country['label'];
            }
            $response = array("status" => "1", "countries" => $countries);
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Place order from the table sales_quote_restmob
     *
     */
    public function placeOrdersAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $STATUS_PENDING_CUSTOMER       = 'pending_customer_confirmation';
        $limit = $this->getRequest()->getPost('limit');
        if(!$limit){
            $limit = 3;
        }
        $savedOrders = Mage::getModel('restmob/quote_index')
            ->getCollection()
            ->addFieldToFilter('status', array('eq' => 0))
            ->addFieldToFilter('payment_status', array('eq' => 1))
            ->addFieldToFilter('payemnt_method', array('neq' => 'free'))
            ->setCurPage(1)
            ->setPageSize($limit);
        foreach ($savedOrders as $savedOrder) {
            try {
                $quoteId = $savedOrder->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                if($quote) {
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
                    $customerPaymentMdl->setPaymentMethod($quoteId, $paymentMethod);// set payment methods
                    if ((int)Mage::getStoreConfig('api/emapi/checkoutDelay') > 0) {
                        sleep((int)Mage::getStoreConfig('api/emapi/checkoutDelay'));
                    }
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $quote->collectTotals()->save();
                    $service = Mage::getModel('sales/service_quote', $quote);
                    $service->submitAll();
                    $order = $service->getOrder();
                    if ($order) {
                        $order->getIncrementId();
                        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Elabelz Mobile App');
                        if ($payment_method == 'telrtransparent' && $payment_status == '1') {
                            $order->setState(Mage_Sales_Model_Order::STATE_NEW, $STATUS_PENDING_CUSTOMER, 'Gateway has authorized the payment.');
                        } elseif ($payment_method == 'telrtransparent' && $payment_status == '0') {
                            $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.');
                        }
                        $order->save();
                        $order->getSendConfirmation(null);
                        $order->sendNewOrderEmail();
                        $quote->setIsActive(0)->save();
                        $mdlRestmob = Mage::getModel('restmob/quote_index');
                        $mdlRestmob->load($savedOrder->getId())->setStatus(1)->save();
                        echo 'Order Placed  quoteId= '.$quoteId.'..<br>';

                    } else {
                        echo 'Error in cron getIncremetId() Error  quoteId= '.$quoteId.'.. <br>';
                        continue;
                    }
                }
            } catch (Exception $e) {
                if (method_exists($e, 'getCustomMessage')) {
                    $message = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $message = $e->getMessage();
                }
                echo 'Error in cron quoteId= '.$quoteId.' & message = '.$message.' <br>';
            }
        }
    }

    public function getShipmentCharges($shippingCountry, $orderSubtotal)
    {
        switch ($shippingCountry) {
            case "AE":
                $price = 0;
                break;
            default:
            {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='" . $shippingCountry . "'";
                $rows = $connection->fetchAll($sql);
                if(!$rows){
                    $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='0'";
                    $rows = $connection->fetchAll($sql);
                }
                $i = 0;
                if(sizeof($rows) == 1){
                    $price = $rows[0]['price'];
                }else {
                    foreach ($rows as $row) {
                        if ($i == 0) {
                            $minArr[] = $row['condition_value'];
                            $minArr[] = $row['price'];
                        } else {
                            $maxArr[] = $row['condition_value'];
                            $maxArr[] = $row['price'];
                        }
                        $i++;
                    }
                    if ($orderSubtotal > $minArr[0] && $orderSubtotal < $maxArr[0]) {
                        $price = $minArr[1];
                    } else {
                        $price = $maxArr[1];
                    }
                }
            }
        }
        return $price;
    }

    /**
     *  This function will be called after checkoutdotcom form submitted
     */
    public function checkoutdotcomAction()
    {
        $orderId    = $_POST['orderId'];
        $quoteId    = $_POST['quoteId'];
        $store_credit = $_POST['store_credit'];
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        $currency = $quote->getQuoteCurrencyCode();
        //select shipping charges
        $shippingCountry = $quote->getShippingAddress()->getCountryId();
        $baseShippingFee = $this->getShipmentCharges($shippingCountry, $quote->getBaseSubtotal());
        $shippingFee = Mage::helper('directory')->currencyConvert($baseShippingFee, "AED", $currency);
        $price = $quote->getGrandTotal() + $shippingFee;
        $price = Mage::helper('telrtransparent')->getCheckoutDotComPrice($price,$currency);
        $url            = Mage::helper('telrtransparent/config')->getCheckoutDotComApiUrl();
        $privateKey     = Mage::helper('telrtransparent/config')->getCheckoutDotComPrivateKey($currency);
        $ch = curl_init($url);
        $header = array(
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: '.$privateKey
        );

        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $id = $mdlRestmob->getIdByQuoteId($quoteId);
        if ($id) {
            $_order = $mdlRestmob->load($id);
            if($_order->getShippingCustomerInfo()) {
                $shippingCustomerInfo = json_decode($_order->getShippingCustomerInfo(),true);
                $address = $shippingCustomerInfo['address'];
                $shippingFirstname = $firstname = $address['firstname'];
                $shippingLastname = $lastname = $address['lastname'];
                $shippingStreet1 = $street1 = $address['street'];
                $shippingStreet2 = $street2 = "";
                $shippingCity = $city = $address['city'];
                $shippingRegion = $region = $address['region'];
                $shippingPostcode = $postcode = $address['postcode'];
                $shippingCountry = $country = $address['country_id'];
                $shippingTelephone = $telephone = $address['telephone'];
                $email = $address['email'];
                if((trim($region) == "" || trim($region) == null)
                    && (trim($address['region_id']) != "" && trim($address['region_id']) != null))
                {
                    $regionMdl = Mage::getModel('directory/region')->load($address['region_id']);
                    $shippingRegion = $region = $regionMdl->getName();
                }
            }else {
                $billing = $quote->getBillingAddress();
                $shipping = $quote->getShippingAddress();
                $email	    = $quote->getCustomerEmail();

                $firstname = $billing->getFirstname();
                $lastname = $billing->getLastname();
                $street1 = $billing->getStreet(1);
                $street2 = $billing->getStreet(2);
                $city = $billing->getCity();
                $region = $billing->getRegion();
                $postcode = $billing->getPostcode();
                $country = $billing->getCountry();
                $telephone = $billing->getTelephone();

                $shippingFirstname = $shipping->getFirstname();
                $shippingLastname = $shipping->getLastname();
                $shippingStreet1 = $shipping->getStreet(1);
                $shippingStreet2 = $shipping->getStreet(2);
                $shippingCity = $shipping->getCity();
                $shippingRegion = $shipping->getRegion();
                $shippingPostcode = $shipping->getPostcode();
                $shippingCountry = $shipping->getCountry();
                $shippingTelephone = $shipping->getTelephone();
            }

            if ($_order->getIsBilling()) {
                $diffBilling = json_decode($_order->getBillingAddress(), true);
                $firstname = $diffBilling['firstname'];
                $lastname = $diffBilling['lastname'];
                $street1 = $diffBilling['street'];
                $street2 = "";
                $city = $diffBilling['city'];
                $region = $diffBilling['region'];
                $postcode = $diffBilling['postcode'];
                $country = $diffBilling['country_id'];
                $telephone = $diffBilling['telephone'];
                if((trim($region) == "" || trim($region) == null)
                    && (trim($diffBilling['region_id']) != "" && trim($diffBilling['region_id']) != null))
                {
                    $regionMdl = Mage::getModel('directory/region')->load($diffBilling['region_id']);
                    $region = $regionMdl->getName();
                }
            }
        }

        $customerName = $firstname." ".$lastname;
        $customerIPAddr = Mage::helper('core/http')->getRemoteAddr(false);
        $data_string = '{  
          "trackId": "' . $orderId . '",
          "customerIp": "' . $customerIPAddr . '",
          "autoCapture": "Y",
          "autoCapTime": "48",
          "email": "'.$email.'",
          "customerName": "'.$customerName.'",
          "value": "'.$price.'",
          "currency": "'.$currency.'",
		  "chargeMode": 1,
          "cardToken": "'.$_POST['ckoCardToken'].'",
          "shippingDetails": {
            "addressLine1": "' . $shippingStreet1 . '",
            "addressLine2": "' . $shippingStreet2 . '",
            "postcode": "' . $shippingPostcode . '",
            "country": "' . $shippingCountry . '",
            "city": "' . $shippingCity . '",
            "state": "' . $shippingRegion . '",
            "phone": {
                 "number": "' . $shippingTelephone . '"
             }
          },
          "billingDetails": {
            "addressLine1": "'.$street1.'",
            "addressLine2": "'.$street2.'",
            "postcode": "'.$postcode.'",
            "country": "'.$country.'",
            "city": "'.$city.'",
            "state": "'.$region.'",
            "phone": {
                 "number": "'.$telephone.'"
             }
           }
		  }';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $output = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($output,true);
        if(isset($response['errors']) || $response['status'] == 'Declined'){
            $this->_redirect('restmob/CheckoutSoap/cancel', array('_secure' => true));
        }
        else{
            $mdlRestmob = Mage::getModel('restmob/quote_index');
            $id = $mdlRestmob->getIdByReserveId($orderId);
            $mdlRestmob->load($id)->setPaymentStatus(1)->save();
            $this->_redirect('restmob/CheckoutSoap/success', array('_secure' => true));
        }
    }
}