<?php
/**
 * Highstreet_HSAPI_module
 *
 * @package     Highstreet_Hsapi
 * @author      Tim Wachter (tim@touchwonders.com) ~ Touchwonders
 * @copyright   Copyright (c) 2015 Touchwonders b.v. (http://www.touchwonders.com/)
 */

class Highstreet_Hsapi_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * Coupon constants
     **/
    const COUPON_SUCCESS_REPLACE = "{coupon_code}";
    const COUPON_CODE_FATAL = -1;
    const COUPON_CODE_ERROR = 1;
    const COUPON_CODE_SUCCESS = 0;
    const COUPON_CODE_MAX_LENGTH = 255;


    public function indexAction() { 
        return false;
    } 


    

    /**
     * Gives all the information to make the checkout work from the initial loading of the page.
     */
    public function startAction() {
        $checkoutModel = Mage::getModel('highstreet_hsapi/checkoutV2');

        $this->_JSONencodeAndRespond($checkoutModel->getStartData());
    }

    /**
     * Countries action. Returns the available countries for the checkout
     */
    public function countriesAction() {
        $countryCollection = Mage::getSingleton('directory/country')->getResourceCollection()->loadByStore();
        $options = $countryCollection->toOptionArray();
        $returnOptions = array();
        foreach ($options as $key => $value) {
            $value['value'] = trim($value['value']);
            $value['label'] = trim($value['label']);
            if (empty($value['value']) === false &&
                empty($value['label']) === false) {
                $returnOptions[] = array("name" => $value['label'], "code" => $value['value']);
            } 
        }

        $this->_JSONencodeAndRespond($returnOptions);
    }

    /**
     * Logout action
     */
    public function logoutAction() {
        Mage::getSingleton('customer/session')->logout();
        $this->_JSONencodeAndRespond(array("OK"));
    }

    /** 
     * Logs the user in. Gets the parameters "email" and "password" from POST
     *
     * @author Tim Wachter
     *
     */
    public function loginAction() {
        $session = Mage::getSingleton('customer/session');

        $success = false;
        $message = "";

        $requestObject = Mage::app()->getRequest();

        $loginArray = $requestObject->getParam('login');

        $email = $loginArray["username"];
        $password = $loginArray["password"];

        if ($session->isLoggedIn()) {
            $success = false; 
            $message = "hsapi.loginAction.success.already";
        } else {
            try {
                if ($session->login($email, $password)) {
                    $success = true; 
                    $message = "hsapi.loginAction.success";
                }
            } catch (Mage_Core_Exception $e) {
                switch ($e->getCode()) {
                    case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED: { // E-mail not confirmed
                        $success = false; 
                        $message = "hsapi.loginAction.error.activate";
                        break;
                    }
                    case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD: { // E-mail or password wrong
                        $success = false; 
                        $message = "hsapi.loginAction.error";
                        break;
                    }
                    default: {
                        $success = false; 
                        $message = "hsapi.loginAction.error.fatal";
                        break;
                    }
                }
            } catch (Exception $e) {
                $success = false; 
                $message = "hsapi.loginAction.error.fatal";
            }
        }

        $response = array();
        $response["success"] = $success;
        $response["message"] = $message;

        $this->_JSONencodeAndRespond($response);
    }

    /**
     * Re-used from Mage_Checkout_OnepageController:348
     */
    public function saveMethodAction() {
        $method = $this->getRequest()->getPost('method');
        $result = $this->getOnepage()->saveCheckoutMethod($method);
        $this->_JSONencodeAndRespond($result);
    }

    /**
     * Largely re-used from Mage_Checkout_OnepageController:363
     */
    public function saveBillingAction() {
        $data = $this->getRequest()->getPost('billing', array());
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

        if (isset($data['email'])) {
            $data['email'] = trim($data['email']);
        }

        if (isset($data['telephone'])) { // We return the telephone number from Magento with a trailing space, put it trough trim before saving
            $data['telephone'] = trim($data['telephone']);
        }

        $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

        if (!isset($result['error'])) {
            if (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                $result['goto_section'] = 'shipping_method';

                $shippingMethods = $this->_getShippingMethods();

                Mage::dispatchEvent(
                        'highstreet_hsapi_checkout_shipping_methods_after', 
                        array(
                            'shipping_methods' => &$shippingMethods, 
                            'quote' => $this->getOnepage()->getQuote(),
                            'request' => $this->getRequest()));

                $result['update_section'] = array(
                    'name' => 'shipping-method',
                    'data' => $shippingMethods
                );

                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
            } else {
                $result['goto_section'] = 'shipping';
            }
        }

        $this->_JSONencodeAndRespond($result);
    }

    /**
     * Largely re-used from Mage_Checkout_OnepageController:405
     */
    public function saveShippingAction()
    {
        $data = $this->getRequest()->getPost('shipping', array());
        $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);

        if (isset($data['telephone'])) { // We return the telephone number from Magento with a trailing space, put it trough trim before saving
            $data['telephone'] = trim($data['telephone']);
        }
        
        $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

        $shippingMethods = $this->_getShippingMethods();

        Mage::dispatchEvent(
                'highstreet_hsapi_checkout_shipping_methods_after', 
                array(
                    'shipping_methods' => &$shippingMethods, 
                    'quote' => $this->getOnepage()->getQuote(),
                    'request' => $this->getRequest()));

        if (!isset($result['error'])) {
            $result['goto_section'] = 'shipping_method';
            $result['update_section'] = array(
                'name' => 'shipping-method',
                'data' => $shippingMethods
            );
        }
        $this->_JSONencodeAndRespond($result);
    }

    /**
     * Largely re-used from Mage_Checkout_OnepageController:429
     */
    public function saveShippingMethodAction()
    {
        $data = $this->getRequest()->getPost('shipping_method', '');
        $result = $this->getOnepage()->saveShippingMethod($data);
        // $result will contain error data if shipping method is empty
        if (!$result) {
            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                 array(
                      'request' => $this->getRequest(),
                      'quote'   => $this->getOnepage()->getQuote()));
            $this->getOnepage()->getQuote()->collectTotals();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

            $result['goto_section'] = 'payment';

            $paymentMethods = $this->_getPaymentMethods();

            Mage::dispatchEvent(
                'highstreet_hsapi_checkout_payment_methods_after', 
                array(
                    'payment_methods' => &$paymentMethods, 
                    'quote' => $this->getOnepage()->getQuote(),
                    'request' => $this->getRequest()));

            $result['update_section'] = array(
                'name' => 'payment-method',
                'data' => $paymentMethods
            );
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();
        $this->_JSONencodeAndRespond($result);
    }

    /** 
     * Adds a coupon to the quote.
     * Code is partially duplicated from couponPostAction in the Mage_Checkout_CartController
     *
     * @author Tim Wachter
     *
     */
    public function addCouponAction() {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        
        // Shopping cart is empty
        // The checkout should fire a restart
        if (!$quote->getItemsCount()) {
            $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_FATAL, "message" => 'hsapi.addCouponAction.error.fatal'));
            return;
        }

        $couponCode = (string) $this->getRequest()->getParam('coupon_code');
        if ($this->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
        }
        $oldCouponCode = $quote->getCouponCode();

        // No coupon code given 
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_ERROR, "message" => 'hsapi.addCouponAction.error.invalid'));
            return;
        }

        try {
            $codeLength = strlen($couponCode);
            
            if ($codeLength >= self::COUPON_CODE_MAX_LENGTH) {
                $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_ERROR, "message" => 'hsapi.addCouponAction.error.length'));
                return;
            }

            $quote->setCouponCode($couponCode)
                  ->collectTotals() // Makes sure that the totals are recalculated with the new discount. Without this the coupon simply gets added, but not calculated in the price
                  ->save();

            if ($codeLength) {
                if ($couponCode == $quote->getCouponCode()) { // Code was successfully added
                    $message = str_replace(self::COUPON_SUCCESS_REPLACE, $this->getRequest()->getParam('coupon_code'), 'hsapi.addCouponAction.success');
                    $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_SUCCESS, "message" => $message));
                    return;
                } else { // Code was not valid
                    $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_ERROR, "message" => 'hsapi.addCouponAction.error.invalid'));
                    return;
                }
            } else {
                $message = str_replace(self::COUPON_SUCCESS_REPLACE, $this->getRequest()->getParam('coupon_code'), 'hsapi.addCouponAction.success.removed');
                $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_SUCCESS, "message" => $message));
                return;
            }

        } catch (Mage_Core_Exception $e) {
            $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_FATAL, "message" => 'hsapi.addCouponAction.error.fatal'));
            return;
        } catch (Exception $e) {
            $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_FATAL, "message" => 'hsapi.addCouponAction.error.fatal'));
            return;
        }

        $this->_JSONencodeAndRespond(array("error" => self::COUPON_CODE_FATAL, "message" => 'hsapi.addCouponAction.error.fatal'));
        return;
    }

    /**
     * Largely re-used from Mage_Checkout_OnepageController:463
     */
    public function savePaymentAction()
    {
        try {
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'data' => $this->_getReviewData()
                );
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }
        $this->_JSONencodeAndRespond($result, false);
    }

    /**
     * Largely re-used from Mage_Checkout_OnepageController:543
     */
    public function saveOrderAction() {
        $result = array();
        try {
            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }
            $this->getOnepage()->saveOrder(); 

            // In the saveOrder function (^^^) in the file /app/code/core/Mage/Checkout/Model/Type/Onepage.php:767 the order is finalized and saved. 
            // On line 796 the "LastSuccessQuoteId" value is set in the session of the user
            // We can read this value to create a hash from it and insert it in the comment. 
            // Later on we re-use this hash to check if a specific order was a Highstreet order
            // On line 823 the "LastRealOderId" value is set in the session of the user
            // We can read this value to get the order ID and insert a comment in the order. 
            // This comment in the order is absolutely crucial for the order tracking of Highstreet. 

            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error']   = false;
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $result['error_messages'] = $message;
            }
            $result['goto_section'] = 'payment';
            $result['update_section'] = array(
                'name' => 'payment-method',
                'html' => $this->_getPaymentMethodsHtml()
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();

            $gotoSection = $this->getOnepage()->getCheckout()->getGotoSection();
            if ($gotoSection) {
                $result['goto_section'] = $gotoSection;
                $this->getOnepage()->getCheckout()->setGotoSection(null);
            }
            $updateSection = $this->getOnepage()->getCheckout()->getUpdateSection();
            if ($updateSection) {
                if (isset($this->_sectionUpdateFunctions[$updateSection])) {
                    $updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
                    $result['update_section'] = array(
                        'name' => $updateSection,
                        'html' => $this->$updateSectionFunction()
                    );
                }
                $this->getOnepage()->getCheckout()->setUpdateSection(null);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success']  = false;
            $result['error']    = true;
            $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
        }
        $this->getOnepage()->getQuote()->save();
        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }

        $this->_JSONencodeAndRespond($result);
    }

    private function getOnepage() {
        return Mage::getSingleton('checkout/type_onepage');
    }

    protected function _getShippingMethods() {
        $shippingMethods = array();

        $shouldGetPostNL = true;
        $postNlOptions = array();
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote(); // Get quote for filled in address data
            $shippingAddressData = $quote->getShippingAddress()->getData();

            $cif = Mage::getModel('postnl_deliveryoptions/cif');
            if ($cif) {
                
                preg_match('([0-9]+)', $shippingAddressData["street"], $matches);
                $housenumber = 1; //default to 1.
                if(isset($matches) && count($matches) > 0) {
                    $housenumber = $matches[0];
                }


                $postNlOptions = $cif->setStoreId(Mage::app()->getStore()->getId())
                                ->getDeliveryTimeframes(array(
                                    'postcode'     => str_replace(" ", "", $shippingAddressData["postcode"]), // Postcode
                                    'housenumber'  => $housenumber,
                                    'deliveryDate' => date('d-m-Y', strtotime('+ 1 day')), // Set delivery day to tomorrow
                                ));
            } else {
                $shouldGetPostNL = false;
            }
        } catch (Exception $e) {
            $shouldGetPostNL = false;
        }

        $quote = Mage::getSingleton('checkout/cart')->getQuote();

        $quote->getShippingAddress()->collectShippingRates();

        foreach ($quote->getShippingAddress()->getGroupedAllShippingRates() as $_rates) { 
            foreach ($_rates as $_rate){
                $checked = false;

                $shippingMethod;
                try {
                    $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
                } catch (Exception $e) {}

                if (!empty($shippingMethod) && $_rate->getCode() === $shippingMethod) {
                    $checked = true;
                }

                $shippingMethod = array();
                $shippingMethod['title'] = $_rate->getMethodTitle();
                $shippingMethod['price'] = $_rate->getData('price');
                $shippingMethod['code'] = $_rate->getCode();
                $shippingMethod['checked'] = $checked;
                $shippingMethod['custom_fields'] = array();

                if (strstr($_rate->getCode(), "postnl") !== false) {
                    $shippingMethod['sub_options'] = $postNlOptions;
                }

                $shippingMethods[] = $shippingMethod;

            }
        }

        return $shippingMethods;
    }

    protected function _getPaymentMethods() {
        $paymentMethods = array();

        $model = new Mage_Checkout_Block_Onepage_Payment_Methods();

        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $selectedPaymentMethod;
        try {
            $selectedPaymentMethod = $quote->getPayment()->getData('method');
        } catch (Exception $e) {}

        foreach ($model->getMethods() as $method) {
            $object = array();
            $code = $method->getCode();

            $methodTitle = $method->getTitle();
            if ($code == "paypal_express") { // PayPal. Has logo and strange label text, override
                $methodTitle = "PayPal";
            }

            $checked = false;

            if (!empty($selectedPaymentMethod) && $selectedPaymentMethod == $code) {
                $checked = true;
            } 
            
            $object["title"] = str_replace("<br>", " ", $methodTitle);
            $object["code"] = $code;
            $object["checked"] = $checked;

            $object["sub_options"] = $this->_getSuboptionsForPaymentMethod($method);
            $object["custom_fields"] = array();

            /** Begin: Adding additional COD fee for Mageworx OrderEdit */
            if ($code === 'msp_cashondelivery') {
                $object["title"] .= "  –  " . Mage::helper('core')->currency($quote->getMspCashondeliveryInclTax(), true, false);
            }

            $paymentMethods[] = $object;
        }

        return $paymentMethods;
    }

    protected function _getSuboptionsForPaymentMethod($method) {
        if ($method->getCode() === "buckaroo3extended_ideal") {
            $options = array();

            $session = Mage::getSingleton('checkout/session');
            $sessionValue = $session->getData('buckaroo3extended_ideal_BPE_Issuer');
            $buckarooIdealModel = new TIG_Buckaroo3Extended_Block_PaymentMethods_Ideal_Checkout_Form();
            $issuerList = $buckarooIdealModel->getIssuerList();

            foreach ($issuerList as $issuer => $issuerDetails) {
                $option = array();
                $optionChecked = false;
                if (!empty($sessionValue) && array_key_exists($sessionValue, $issuerList))  {
                    if ($issuer == $sessionValue) {
                        $optionChecked = true;
                    }
                }

                $option["checked"] = $optionChecked;
                $option["title"] = $issuerDetails['name'];
                $option["code"] = $issuer;
                $option["image"] = $issuerDetails['logo'];
                $options[] = $option;
            }

            return $options;
        }

        return null;
    }

    private function _getReviewData() {
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $billingAddressData = $billingAddress->getData();
        $shippingAddressData = $shippingAddress->getData();

        $response = array();

        // Billing address information
        $billing_address = array();
        $billing_address["email"] = $quote->getCustomerEmail();
        $billing_address["firstname"] = $billingAddressData["firstname"];
        $billing_address["lastname"] = $billingAddressData["lastname"];
        $billing_address["telephone"] = (string) $billingAddressData["telephone"] . " ";
        $billingStreet = $billingAddress->getStreet();
        if (is_array($billingStreet)) {
            $billing_address["street"] = implode(' ', $billingStreet);
        } else {
            $billing_address["street"] = $billingStreet;
        }
        $billing_address["postcode"] = $billingAddressData["postcode"];
        $billing_address["city"] = $billingAddressData["city"];

        $response["billing_address"] = $billing_address;

        // Shipping address information
        $shipping_address = array();
        $shipping_address["firstname"] = $shippingAddressData["firstname"];
        $shipping_address["lastname"] = $shippingAddressData["lastname"];
        $shipping_address["telephone"] = (string) $shippingAddressData["telephone"] . " ";
        $shippingStreet = $shippingAddress->getStreet();
        if (is_array($shippingStreet)) {
            $shipping_address["street"] = implode(' ', $shippingStreet);
        } else {
            $shipping_address["street"] = $shippingStreet;
        }
        $shipping_address["postcode"] = $shippingAddressData["postcode"];
        $shipping_address["city"] = $shippingAddressData["city"];

        if (!$this->_billingAndShippingAddressesAreTheSame($response["billing_address"], $shipping_address)) {
            $response["shipping_address"] = $shipping_address;
        } else {
            $response["shipping_address"] = array();
        }


        // Shipping method information
        $response["shipping_method"]["name"] = $shippingAddress->getShippingDescription();

        // Payment method information
        $response["payment_method"]["name"] = $quote->getPayment()->getMethodInstance()->getTitle();
        $response["payment_method"]["coupon_code"] = $quote->getCouponCode();


        return $response;
    }

    /**
     * Conveinience method, compares 2 formatted address arrays
     */
    protected function _billingAndShippingAddressesAreTheSame($billingAddressArray = array(), $shippingAddressArray = array()) {
        if (count($billingAddressArray) == 0 || count($shippingAddressArray) == 0) {
            return true;
        }

        if ($billingAddressArray["firstname"] !== $shippingAddressArray["firstname"] ||
            $billingAddressArray["lastname"] !== $shippingAddressArray["lastname"] ||
            $billingAddressArray["telephone"] !== $shippingAddressArray["telephone"] ||
            $billingAddressArray["street"] !== $shippingAddressArray["street"] ||
            $billingAddressArray["postcode"] !== $shippingAddressArray["postcode"] ||
            $billingAddressArray["city"] !== $shippingAddressArray["city"]) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Sets the proper headers
     */
    private function _setHeader()
    {
        Mage::getSingleton('core/session')->setLastStoreCode(Mage::app()->getStore()->getCode());
        header_remove('Pragma'); // removes 'no-cache' header
        $this->getResponse()->setHeader('Content-Type','application/json', true);
    }

    /**
     * Sets headers and body with proper JSON encoding
     */
    protected function _JSONencodeAndRespond($data, $numericCheck = true) {
        //set response body
        $this->_setHeader();
        if ($numericCheck === FALSE || version_compare(PHP_VERSION, '5.3.3', '<')) {
            $this->getResponse()->setBody(json_encode($data));
        } else {
            $this->getResponse()->setBody(json_encode($data, JSON_NUMERIC_CHECK));
        }

    }
}