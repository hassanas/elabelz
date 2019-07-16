<?php
require_once dirname(__FILE__) . '/CartSoapController.php';

class Progos_Emapi_CheckoutSoapController extends Progos_Emapi_CartSoapController
{
    public function indexAction()
    {
        return;
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
        $sessionId = $this->getRequest()->getPost('sid');
        $quoteId = $this->getRequest()->getPost('qid');
        $newsletter = $this->getRequest()->getPost('newsletter');
        $customer_id = $this->getRequest()->getPost('customer_id');
        $email = $this->getRequest()->getPost('email');
        $cartItems = $this->getRequest()->getPost('items');
        Mage::log('Checkout initiated quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . '.. ' . '\n', null, 'checkout_time.log');
        if ($newsletter && $customer_id) {
            $customer = Mage::getModel('customer/customer')->load($customer_id);
            $email = $customer->getEmail();
            $this->subunsubnewsletter($email);
        } elseif ($newsletter && $email) {
            $this->subunsubnewsletter($email);
        }
        $response = array('success' => 0, 'message' => '', 'res' => false, 'retry' => 0, 'sid' => $sessionId);
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        if (is_null($quote->getCustomerEmail()) || $quote->getCustomerEmail() == "") {
            $email = $this->getRequest()->getPost('email');
            $storeId = Mage::app()->getStore()->getWebsiteId();
            $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
            $customerAsGuest = array(
                'customer_id' => $customer->getId(),
                'mode' => 'customer'
            );
            parent::setPrxy();
            $prxy = $this->prxy;
            $sessionId1 = $prxy->login($this->API_USER, $this->API_KEY);
            $prxy->call($sessionId1, 'cart_customer.set', array($quoteId, $customerAsGuest));
        }
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        if ($this->getRequest()->getPost('payment_method') != 'free'
            ||
            ($this->getRequest()->getPost('payment_method') == 'free' && $quote->getBaseSubtotalWithDiscount() == 0)
        ){
            $nexmoActivated = Mage::getStoreConfig('api/emapi/nexmo_activated');
            $nexmoStores =  Mage::getStoreConfig('api/emapi/nexmo_stores');
            $nexmoStores = explode(',', $nexmoStores);
            $currentStore =  Mage::app()->getStore()->getCode();
            $response['nexmo'] = 0;
            if ($nexmoActivated == 1 && in_array($currentStore, $nexmoStores)) {
                $response['nexmo'] = 1;
            }
            try {
                $quote->reserveOrderId()->save();
                $res = $quote->getReservedOrderId();
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
                $mdlEmapi = Mage::getModel('restmob/quote_index');
                $id = $mdlEmapi->getIdByReserveId($res);
                if ($id) {
                    $mdlEmapi->load($id);
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setPayemntMethod($this->getRequest()->getPost('payment_method'));
                    $mdlEmapi->setShippingMethod($this->getRequest()->getPost('shipment_method'));
                    $mdlEmapi->setPaymentStatus($paymentStatus);
                    if ($quote->getItemsCount() == 0) {
                        $mdlEmapi->setStatus(2);
                    }else{
                        $mdlEmapi->setStatus(0);
                    }
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->setCcCid($this->getRequest()->getPost('cc_cid'));
                    $mdlEmapi->setCcOwner($this->getRequest()->getPost('cc_owner'));
                    $mdlEmapi->setCcNumber($this->getRequest()->getPost('cc_number'));
                    $mdlEmapi->setCcType($this->getRequest()->getPost('cc_type'));
                    $mdlEmapi->setCcExpYear($this->getRequest()->getPost('cc_exp_year'));
                    $mdlEmapi->setCcExpMonth($this->getRequest()->getPost('cc_exp_month'));
                    $mdlEmapi->setCartItems($cartItems);
                    $mdlEmapi->setCreatedAt(Varien_Date::now());
                    $mdlEmapi->save();
                } else {
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setPayemntMethod($this->getRequest()->getPost('payment_method'));
                    $mdlEmapi->setShippingMethod($this->getRequest()->getPost('shipment_method'));
                    $mdlEmapi->setPaymentStatus($paymentStatus);
                    if ($quote->getItemsCount() == 0) {
                        $mdlEmapi->setStatus(2);
                    }else{
                        $mdlEmapi->setStatus(0);
                    }
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->setCcCid($this->getRequest()->getPost('cc_cid'));
                    $mdlEmapi->setCcOwner($this->getRequest()->getPost('cc_owner'));
                    $mdlEmapi->setCcNumber($this->getRequest()->getPost('cc_number'));
                    $mdlEmapi->setCcType($this->getRequest()->getPost('cc_type'));
                    $mdlEmapi->setCcExpYear($this->getRequest()->getPost('cc_exp_year'));
                    $mdlEmapi->setCcExpMonth($this->getRequest()->getPost('cc_exp_month'));
                    $mdlEmapi->setCartItems($cartItems);
                    $mdlEmapi->setCreatedAt(Varien_Date::now());
                    $mdlEmapi->save();
                }
                if ($this->getRequest()->getParam('payment_method') == 'telrtransparent') {
                    $response['webViewUrl'] = Mage::getUrl('emapi/CheckoutSoap/redirect', array('_secure' => true, '_query' => 'cvv=' . $this->getRequest()->getPost('cc_cid') . '&oid=' . $res . '&qid=' . $quoteId));
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
                    $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['error_message'], '_')) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
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
                Mage::log('soap less error quoteId = ' . $quoteId . ' on Progos_Emapi_CheckoutSoapController processPaymentAction action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }
        } else {
            $response['error_code'] = 0;
            $response['message'] = "Nothing in your bag";
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    public function processPaymentStoreCreditAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $sessionId = $this->getRequest()->getPost('sid');
        $quoteId = $this->getRequest()->getPost('qid');
        $bin_discount = $this->getRequest()->getPost('bin_discount');
        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $id = $mdlRestmob->getIdByQuoteId($quoteId);
        if ($id) {
            $mRestmob = $mdlRestmob->load($id);
            if($mRestmob->getStoreCredit() == 1){
                $quoteId = $mRestmob->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                if ($storeCredit) {
                    $storeBalance = $storeCredit->getBalance();
                    $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                    foreach($quoteStorecredits as $quoteStorecredit){
                        $linkId = $quoteStorecredit->getLinkId();
                        $baseStorecreditAmount = $quoteStorecredit->baseStorecreditAmount;
                        $storeCredit
                            ->setBalance($storeBalance + $baseStorecreditAmount)
                            ->save()
                        ;
                        $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
                        if (null !== $quoteStoreCreditModel) {
                            $quoteStoreCreditModel
                                ->setBaseStorecreditAmount(0)
                                ->setStorecreditAmount(0)
                                ->save()
                            ;
                        }
                    }
                }
            }
        }

        $newsletter = $this->getRequest()->getPost('newsletter');
        $customer_id = $this->getRequest()->getPost('customer_id');
        $email = $this->getRequest()->getPost('email');
        $cartItems = $this->getRequest()->getPost('items');
        $store_credit = $this->getRequest()->getPost('store_credit');
        $store_credit_value = $this->getRequest()->getPost('store_credit_value');
        $version_string = $this->getRequest()->getPost('version_string');
        Mage::log('Checkout initiated quoteId = ' . $quoteId . ' at ' . date('d/m H:i:s:u') . '.. ' . '\n', null, 'checkout_time.log');
        if ($newsletter && $customer_id) {
            $customer = Mage::getModel('customer/customer')->load($customer_id);
            $email = $customer->getEmail();
            $this->subunsubnewsletter($email);
        } elseif ($newsletter && $email) {
            $this->subunsubnewsletter($email);
        }
        $billingAddressDiff = array();
        $isBilling = 0;
        if(trim($this->getRequest()->getPost('firstname')) != ""
            && trim($this->getRequest()->getPost('lastname')) != ""
            && trim($this->getRequest()->getPost('street')) != ""
            && trim($this->getRequest()->getPost('telephone')) != ""
            && trim($this->getRequest()->getPost('city')) != ""
            && trim($this->getRequest()->getPost('country_id')) != ""
        ){
            $isBilling = 1;
            $billingAddressDiff = array(
                'firstname' => $this->getRequest()->getPost('firstname'),
                'lastname' => $this->getRequest()->getPost('lastname'),
                'street' => $this->getRequest()->getPost('street'),
                'city' => $this->getRequest()->getPost('city'),
                'country_id' => $this->getRequest()->getPost('country_id'),
                'telephone' => $this->getRequest()->getPost('telephone'),
                'postcode' => $this->getRequest()->getPost('postcode'),
                'region' => $this->getRequest()->getPost('region'),
                'region_id' => $this->getRequest()->getPost('region_id'),
                "is_default_shipping" => 0,
                "is_default_billing" => 0,
                "mode" => "billing",
                "email" => $this->getRequest()->getPost('email')
            );
        }

        $response = array('success' => 0, 'message' => '', 'res' => false, 'retry' => 0, 'sid' => $sessionId);
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        if (is_null($quote->getCustomerEmail()) || $quote->getCustomerEmail() == "") {
            $email = $this->getRequest()->getPost('email');
            $storeId = Mage::app()->getStore()->getWebsiteId();
            $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
            $customerAsGuest = array(
                'customer_id' => $customer->getId(),
                'mode' => 'customer'
            );
            parent::setPrxy();
            $prxy = $this->prxy;
            $sessionId1 = $prxy->login($this->API_USER, $this->API_KEY);
            $prxy->call($sessionId1, 'cart_customer.set', array($quoteId, $customerAsGuest));
        }
        if ($this->getRequest()->getPost('payment_method') != 'free'
            ||
            ($this->getRequest()->getPost('payment_method') == 'free' && $store_credit)
            ||
            ($this->getRequest()->getPost('payment_method') == 'free' && $quote->getBaseSubtotalWithDiscount() == 0)
        ){
            if($bin_discount){
                Mage::helper('emapi')->autoApplyCouponToQuote($quoteId);
            }else{
                //code to remove binary coupon code in case of declined payment
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $storeId = Mage::app()->getStore()->getId();
                if($quote->getCouponCode() == trim(Mage::getStoreConfig('api/emapi/coupon_code', $storeId))){
                    $quote->setCouponCode('')
                        ->collectTotals()
                        ->save();
                }
                //End of code to remove binary coupon code in case of declined payment
            }
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            $nexmoActivated = Mage::getStoreConfig('api/emapi/nexmo_activated');
            $nexmoStores =  Mage::getStoreConfig('api/emapi/nexmo_stores');
            $nexmoStores = explode(',', $nexmoStores);
            $currentStore =  Mage::app()->getStore()->getCode();
            $response['nexmo'] = 0;
            if ($nexmoActivated == 1 && in_array($currentStore, $nexmoStores)) {
                $response['nexmo'] = 1;
            }
            try {
                $quote->reserveOrderId()->save();
                $res = $quote->getReservedOrderId();
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

                /*
                 * Code for store credit
                 */
                $storecreditInfo = array();
                if($store_credit) {
                    $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                    if ($storeCredit) {
                        $storeBalance = $storeCredit->getBalance();
                        $sbc = (float)number_format((float)Mage::helper('core')->currency($storeBalance, false, false), 2,'.','');
                        if ($sbc != trim($store_credit_value)) {
                            $storeCredit = array();
                            $storeCredit[0]['code'] = 'store_credit';
                            $storeCredit[0]['title'] = "Store Credit";
                            $storeCredit[0]['price'] = $sbc;
                            $response['message'] = "Your store credit totals are updated. Please review your order and try again.";
                            $response['store_credit'] = $storeCredit;
                            header("Content-Type: application/json");
                            echo json_encode($response);
                            die;
                        }
                        $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                        $quote->setStorecreditInstance($storeCredit);
                        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                        $quoteTotal = $quote->getBaseSubtotalWithDiscount();
                        //select shipping charges
                        $shippingCountry = $quote->getShippingAddress()->getCountryId();
                        $baseShippingFee = Mage::helper('emapi')->getShippingCharges($shippingCountry, $quote->getBaseSubtotal());
                        //check added for adding VAT charges in the totals as well
                        $shippingAddress = $quote->getShippingAddress();
                        $baseVatValue = 0;
                        if($shippingAddress->getTaxAmount()){
                            $baseVatValue = $shippingAddress->getBaseTaxAmount();
                        }
                        $quoteTotal = $quoteTotal + $baseShippingFee + $baseVatValue;

                        if ($this->getRequest()->getParam('payment_method') == 'msp_cashondelivery') {
                            $storeId = $store = Mage::app()->getStore()->getId();
                            $currency = $quote->getQuoteCurrencyCode();
                            $address = $quote->getShippingAddress();
                            Mage::getModel('msp_cashondelivery/quote_total')->collect($address);
                            $zoneType = $address->getCountryId() == Mage::getStoreConfig('shipping/origin/country_id', $storeId) ? 'local' : 'foreign';
                            if ($zoneType == 'local')
                                $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
                            else
                                $baseMspFee = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

                            if ($address->getCountryId() == "SA") {
                                $additionalFee = Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_sa');
                                $baseMspFee = $baseMspFee + $additionalFee;
                            }

                            if (strtolower($address->getCountryId()) == "iq") {
                                $baseMspFee = Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');
                            }

                            $quoteTotal = $quoteTotal + $baseMspFee;
                        }

                        if ($quoteTotal > $storeBalance) {
                            $subtract = $storeBalance;
                        } else {
                            $subtract = $quoteTotal;
                        }
                        $storeCredit
                            ->setBalance($storeBalance - $subtract)
                            ->save()
                        ;
                        //update store credits in quote store credit table
                        $currency = $quote->getQuoteCurrencyCode();
                        $sc = Mage::helper('directory')->currencyConvert($subtract, "AED", $currency);
                        if (count($quoteStorecredits) == 0) {
                            Mage::getModel('aw_storecredit/quote_storecredit')
                                ->setQuoteEntityId($quote->getId())
                                ->setStorecreditId($storeCredit->getId())
                                ->setBaseStorecreditAmount($subtract)
                                ->setStorecreditAmount($sc)
                                ->save()
                            ;
                        }else{
                            foreach($quoteStorecredits as $quoteStorecredit) {
                                $linkId = $quoteStorecredit->getLinkId();
                                $baseStoreCreditBalance = trim($quoteStorecredit->getBaseStorecreditAmount());
                                if($baseStoreCreditBalance == "0.0000" || $baseStoreCreditBalance == null){
                                    $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
                                    $quoteStoreCreditModel
                                        ->setBaseStorecreditAmount($subtract)
                                        ->setStorecreditAmount($sc)
                                        ->save()
                                    ;
                                }
                            }
                        }
                    }
                    $storecreditInfo['id'] = $storeCredit->getId();
                    $storecreditInfo['total'] = ($storeBalance - $subtract);
                    $storecreditInfo['spent'] = "-".$subtract;
                }
                /*
                 * End Code for store credit
                 */

                $mdlEmapi = Mage::getModel('restmob/quote_index');
                $id = $mdlEmapi->getIdByReserveId($res);
                if ($id) {
                    $mdlEmapi->load($id);
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setPayemntMethod($this->getRequest()->getPost('payment_method'));
                    $mdlEmapi->setShippingMethod($this->getRequest()->getPost('shipment_method'));
                    $mdlEmapi->setPaymentStatus($paymentStatus);
                    if ($quote->getItemsCount() == 0) {
                        $mdlEmapi->setStatus(2);
                    }else{
                        $mdlEmapi->setStatus(0);
                    }
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->setCcCid($this->getRequest()->getPost('cc_cid'));
                    $mdlEmapi->setCcOwner($this->getRequest()->getPost('cc_owner'));
                    $mdlEmapi->setCcNumber($this->getRequest()->getPost('cc_number'));
                    $mdlEmapi->setCcType($this->getRequest()->getPost('cc_type'));
                    $mdlEmapi->setCcExpYear($this->getRequest()->getPost('cc_exp_year'));
                    $mdlEmapi->setCcExpMonth($this->getRequest()->getPost('cc_exp_month'));
                    $mdlEmapi->setStoreCredit($this->getRequest()->getPost('store_credit'));
                    $mdlEmapi->setVersionString($version_string);
                    $mdlEmapi->setCartItems($cartItems);
                    $mdlEmapi->setCreatedAt(Varien_Date::now());
                    $mdlEmapi->setIsBilling($isBilling);
                    $mdlEmapi->setBillingAddress(json_encode($billingAddressDiff));
                    $mdlEmapi->setScInfo(json_encode($storecreditInfo));
                    $mdlEmapi->setBinDiscount($bin_discount);
                    $mdlEmapi->save();
                } else {
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setPayemntMethod($this->getRequest()->getPost('payment_method'));
                    $mdlEmapi->setShippingMethod($this->getRequest()->getPost('shipment_method'));
                    $mdlEmapi->setPaymentStatus($paymentStatus);
                    if ($quote->getItemsCount() == 0) {
                        $mdlEmapi->setStatus(2);
                    }else{
                        $mdlEmapi->setStatus(0);
                    }
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->setCcCid($this->getRequest()->getPost('cc_cid'));
                    $mdlEmapi->setCcOwner($this->getRequest()->getPost('cc_owner'));
                    $mdlEmapi->setCcNumber($this->getRequest()->getPost('cc_number'));
                    $mdlEmapi->setCcType($this->getRequest()->getPost('cc_type'));
                    $mdlEmapi->setCcExpYear($this->getRequest()->getPost('cc_exp_year'));
                    $mdlEmapi->setCcExpMonth($this->getRequest()->getPost('cc_exp_month'));
                    $mdlEmapi->setStoreCredit($this->getRequest()->getPost('store_credit'));
                    $mdlEmapi->setVersionString($version_string);
                    $mdlEmapi->setCartItems($cartItems);
                    $mdlEmapi->setCreatedAt(Varien_Date::now());
                    $mdlEmapi->setIsBilling($isBilling);
                    $mdlEmapi->setBillingAddress(json_encode($billingAddressDiff));
                    $mdlEmapi->setScInfo(json_encode($storecreditInfo));
                    $mdlEmapi->setBinDiscount($bin_discount);
                    $mdlEmapi->save();
                }
                if ($this->getRequest()->getParam('payment_method') == 'telrtransparent') {
                    $response['webViewUrl'] = Mage::getUrl('emapi/CheckoutSoap/redirect', array('_secure' => true, '_query' => 'cvv=' . $this->getRequest()->getPost('cc_cid') . '&oid=' . $res . '&qid=' . $quoteId . '&store_credit=' . $this->getRequest()->getPost('store_credit')));
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
                    $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['error_message'], '_')) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
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
                Mage::log('soap less error quoteId = ' . $quoteId . ' on Progos_Emapi_CheckoutSoapController processPaymentAction action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }
        } else {
            $response['error_code'] = 0;
            $response['message'] = "Nothing in your bag";
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    // The redirect action is to send and confirm transparent payment
    public function redirectAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'emapi', array('template' => 'emapi/redirect.phtml'));
        $this->getLayout()->getBlock('head')->append($block);
        $this->renderLayout();
    }

    // Placeholder url for app
    public function successAction()
    {
    }

    // Placeholder URL for app
    public function cancelAction()
    {
    }


    public function successcallbackAction()
    {
        $reservedOrderId = $_POST['cart_id'];
        $telrReferenceId = $_POST['auth_tranref'];
        $mdlEmapi = Mage::getModel('restmob/quote_index');
        $id = $mdlEmapi->getIdByReserveId($reservedOrderId);
        $mdlEmapi->load($id)->setPaymentStatus(1)->setTelrReferenceId($telrReferenceId)->save();
    }

    public function cancelcallbackAction()
    {
        $reservedOrderId = $this->getRequest()->getParam('oid');
        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $id = $mdlRestmob->getIdByReserveId($reservedOrderId);
        if ($id) {
            $mRestmob = $mdlRestmob->load($id);
            $quoteId = $mRestmob->getQid();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            if($mRestmob->getStoreCredit() == 1){
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                if ($storeCredit) {
                    $storeBalance = $storeCredit->getBalance();
                    $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                    foreach($quoteStorecredits as $quoteStorecredit){
                        $linkId = $quoteStorecredit->getLinkId();
                        $baseStorecreditAmount = $quoteStorecredit->baseStorecreditAmount;
                        $storeCredit
                            ->setBalance($storeBalance + $baseStorecreditAmount)
                            ->save()
                        ;
                        $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
                        if (null !== $quoteStoreCreditModel) {
                            $quoteStoreCreditModel
                                ->setBaseStorecreditAmount(0)
                                ->setStorecreditAmount(0)
                                ->save()
                            ;
                        }
                    }
                }
            }
            //code to remove binary coupon code in case of declined payment
            $storeId = Mage::app()->getStore()->getId();
            if($quote->getCouponCode() == trim(Mage::getStoreConfig('api/emapi/coupon_code', $storeId))){
                $quote->setCouponCode('')
                    ->collectTotals()
                    ->save();
            }
            //End of code to remove binary coupon code in case of declined payment
        }

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
     * Nexmo order verification
     *
     */
    public function verifyNexmoAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $response = array('success' => 0, 'message' => '');
        try {
            if (Mage::getStoreConfig('api/emapi/checkout')) {
                $realOrderId = $this->getRequest()->getPost('id');
                $this->verifyNexmo($realOrderId);
                $response = array('success' => 1, 'message' => 'verified');
            } elseif (Mage::getStoreConfig('api/emapi/enableNewCheckout')) {
                $orderId = $this->getRequest()->getPost('id');
                $mdlRestmob = Mage::getModel('restmob/quote_index');
                $id = $mdlRestmob->getIdByReserveId($orderId);
                if ($id) {
                    $order = $mdlRestmob->load($id);
                    if ($order->getStatus == "0") {
                        $order->setNexmoStatus(1)->save();
                    } else {
                        $order->setNexmoStatus(1)->save();
                        $realOrderId = $order->getRealOrderId();
                        $this->verifyNexmo($realOrderId);
                    }
                }
                $response = array('success' => 1, 'message' => 'verified');
            }
        } catch (Exception $e) {
            $response = array('success' => 0, 'message' => $e->getMessage());
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
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

    /**
     *  This function will be called after checkoutdotcom form submitted
     */
    public function checkoutdotcomAction(){


        $orderId    = $_POST['orderId'];
        $quoteId    = $_POST['quoteId'];

        $store_credit = $_POST['store_credit'];
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        $currency = $quote->getQuoteCurrencyCode();

        //select shipping charges
        $shippingCountry = $quote->getShippingAddress()->getCountryId();
        $mdlEmapi = Mage::getModel('restmob/quote_index');
        $id = $mdlEmapi->getIdByQuoteId($quoteId);
        $_order = $mdlEmapi->load($id);
        if($_order->getShippingCustomerInfo()) {
            $shippingCustomerInfo = json_decode($_order->getShippingCustomerInfo(),true);
            $address = $shippingCustomerInfo['address'];
            //select shipping charges
            $shippingCountry = $address['country_id'];
        }
        $baseShippingFee = Mage::helper('emapi')->getShippingCharges($shippingCountry, $quote->getBaseSubtotal());
        $shippingFee = Mage::helper('directory')->currencyConvert($baseShippingFee, "AED", $currency);
        $price = $quote->getGrandTotal() + $shippingFee;
        /*
         * Code for store credit
         */
        $storeBalance = 0;
        $baseStoreBalance = 0;
        if ($store_credit) {
            $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
            if (count($quoteStorecredits) > 0) {
                foreach ($quoteStorecredits as $quoteStorecredit) {
                    $_baseStorecreditAmount = $quoteStorecredit->getBaseStorecreditAmount();
                    $baseStoreBalance = Mage::helper('directory')->currencyConvert($_baseStorecreditAmount, "AED", $currency);
                    $storeBalance = Mage::helper('directory')->currencyConvert($_baseStorecreditAmount, "AED", $currency);
                }
            }
        }
        if (trim($currency) != "AED") {
            $shippingFee = $baseShippingFee;
            $price = $quote->getBaseGrandTotal() + $shippingFee;
            $price = Mage::helper('directory')->currencyConvert($price, "AED", $currency);
            $price = number_format(($price - $storeBalance), 2, '.', '');
        } else {
            $price = number_format(($price - $baseStoreBalance), 2, '.', '');
        }
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
        }else{
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

        //check if user selected different billing address on checkout
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

        $price = Mage::helper('telrtransparent')->getCheckoutDotComPrice($price,$currency);
        $url            = Mage::helper('telrtransparent/config')->getCheckoutDotComApiUrl();
        $privateKey     = Mage::helper('telrtransparent/config')->getCheckoutDotComPrivateKey($currency);

        $ch = curl_init($url);
        $header = array(
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: '.$privateKey
        );



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

        if(isset($response['errorCode']) || $response['status'] == 'Declined'){
            $mdlRestmob = Mage::getModel('restmob/quote_index');
            $id = $mdlRestmob->getIdByReserveId($orderId);
            if ($id) {
                $mRestmob = $mdlRestmob->load($id);
                $quoteId = $mRestmob->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                if($mRestmob->getStoreCredit() == 1){
                    $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                    if ($storeCredit) {
                        $storeBalance = $storeCredit->getBalance();
                        $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                        foreach($quoteStorecredits as $quoteStorecredit){
                            $linkId = $quoteStorecredit->getLinkId();
                            $baseStorecreditAmount = $quoteStorecredit->baseStorecreditAmount;
                            $storeCredit
                                ->setBalance($storeBalance + $baseStorecreditAmount)
                                ->save()
                            ;
                            $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
                            if (null !== $quoteStoreCreditModel) {
                                $quoteStoreCreditModel
                                    ->setBaseStorecreditAmount(0)
                                    ->setStorecreditAmount(0)
                                    ->save()
                                ;
                            }
                        }
                    }
                }
                //update order with transaction id and response code in comments @RT
                if (Mage::helper('onestepcheckout')->isCheckoutResToOrder()) {
                    $mRestmob->setTelrReferenceId($response['id'])
                        ->setTelrRespCode($response['responseCode'])
                        ->save();
                }
                //code to remove binary coupon code in case of declined payment
                $storeId = Mage::app()->getStore()->getId();
                if($quote->getCouponCode() == trim(Mage::getStoreConfig('api/emapi/coupon_code', $storeId))){
                    $quote->setCouponCode('')
                        ->collectTotals()
                        ->save();
                }
                //End of code to remove binary coupon code in case of declined payment
            }
            $this->_redirect('emapi/CheckoutSoap/cancel', array('_secure' => true));
        }
        else{
            $mdlEmapi = Mage::getModel('restmob/quote_index');
            $id = $mdlEmapi->getIdByReserveId($orderId);
            $mdlEmapi->load($id)->setPaymentStatus(1)->save();
            //update order with transaction id and response code in comments @RT
            if (Mage::helper('onestepcheckout')->isCheckoutResToOrder()) {
                $mdlEmapi->setTelrReferenceId($response['id'])
                    ->setTelrRespCode($response['responseCode'])
                    ->save();
            }
            $this->_redirect('emapi/CheckoutSoap/success', array('_secure' => true));
        }
    }
}
