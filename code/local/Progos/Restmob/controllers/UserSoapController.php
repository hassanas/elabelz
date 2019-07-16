<?php
require_once dirname(__FILE__) . '/CartSoapController.php';

class Progos_Restmob_UserSoapController extends Progos_Restmob_CartSoapController
{

    /**
     * Function to check for the customer if it is registered or not
     *
     * @access public
     * @params String email
     * @return Array user status if it is registered and not confirmed, not registered, registered with confirmed with status
     *
     */
    public function checkGuestCustomerAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        /*$email = $this->getRequest()->getPost('email');
        $storeId = Mage::app()->getWebsite()->getId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
        if ($customer->getId()) {
            if (trim($customer->getConfirmation()) == "") {
                $response = array('error' => 2, 'message' => 'Email confirmed');
            } else {
                $response = array('error' => 1, 'message' => 'Email not confirmed');
            }
        } else {
            $response = array('error' => 3, 'message' => 'Customer not registered.');
        }*/
        $response = array('error' => 3, 'message' => 'Customer not registered.');
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to resend confirmation link so the customer can verify his/her account
     *
     * @access public
     * @params String email
     * @return Array with status code and status message
     *
     */
    public function resendConfirmationEmailAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $email = $this->getRequest()->getPost('email');
        $storeId = Mage::app()->getWebsite()->getId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
        if ($customer->getId()) {
            if ($customer->isConfirmationRequired()) {
                $customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
            }
            $response = array('error' => 0, 'message' => 'Confirmation email sent successfully.');
        } else {
            $response = array('error' => 1, 'message' => 'Email address provided is not a customer email.');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to validate the customer password with hash stored in database
     *
     * @access public
     * @params String password, string hash
     * @return bool true / false
     *
     */
    public function validateHash($password, $hash)
    {
        $hashArr = explode(':', $hash);
        switch (count($hashArr)) {
            case 1:
                return md5($password) === $hash;
            case 2:
                return md5($hashArr[1] . $password) === $hashArr[0];
        }
    }

    /**
     * Function to login customer
     *
     * @access public
     * @params String email, string password
     * @return Array with customer data and status
     *
     */
    public function loginAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $sessionId = $this->getRequest()->getPost('sid');
        $email = $this->getRequest()->getPost('email');
        $password = $this->getRequest()->getPost('password');
        /*
         * Social login code is added on 11/22/2017 by Naveed Abbas
         */
        $is_social = $this->getRequest()->getPost('is_social');
        if($is_social) {
            try {
                $storeId = Mage::app()->getWebsite()->getId();
                $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
                if(!$customer->getId()){
                    $firstname = $this->getRequest()->getPost('firstname');
                    $lastname = $this->getRequest()->getPost('lastname');
                    $customer->setFirstname($firstname);
                    $customer->setLastname($lastname);
                    $customer->setEmail($email);
                    $cpass = $customer->generatePassword(10);
                    $customer->setPassword($cpass);
                    $customer->setCredentials($cpass);
                    $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $customer->save();
                    if (Mage::getStoreConfig('customer/create_account/confirm')) {
                        $customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
                    }else{
                        $customer->sendNewAccountEmail('registered', '', $customer->getStoreId());
                    }
                }
                //creating the output
                $response['success'] = 1;
                $response['sid'] = "";
                $response['message'] = 'Login successful';
                $response['customer'] = array();
                $response['customer']['customer_id'] = $customer->getId();
                $response['customer']['created_at'] = $customer->getCreatedAt();
                $response['customer']['full_name'] = $customer->getName();
                $response['customer']['first_name'] = $customer->getFirstname();
                $response['customer']['last_name'] = $customer->getLastname();
                $response['customer']['updated_at'] = $customer->getUpdatedAt();
                $response['customer']['store_id'] = $customer->getStoreId();
                $response['customer']['website_id'] = $customer->getWebsiteId();
                $response['customer']['created_in'] = $customer->getCreatedIn();
                $response['customer']['email'] = $customer->getEmail();
                $response['customer']['group_id'] = $customer->getGroupId();
                $response['customer']['password_hash'] = $customer->getPasswordHash();
            }catch (Exception $e) {
                if (method_exists($e, 'getCustomMessage')) {
                    $response['error_message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['error_message'] = $e->getMessage();
                }
                if (is_null($response['error_message'])) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['message'], '_')) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
                }
                $response['sid'] = $sessionId;
                $response['message'] = "Email and password is incorrect, please try again";
                Mage::log('error on login action1.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }
            header("Content-Type: application/json");
            echo json_encode($response);
            die;
        }
        /*
         * End of social login code
         */
        $customerObj = new stdClass();
        $response = array('success' => 0, 'message' => '', 'customer' => $customerObj, 'customer_orders' => array());
        $session = Mage::getSingleton('customer/session');
        try {
            $session->login($email, $password);
            $customer = $session->getCustomer();
            if ($this->validateHash($password, $customer->getPasswordHash())) {
                $response['success'] = 1;
                $response['sid'] = $sessionId;
                $response['message'] = 'Login successful';
                $response['customer'] = array();
                $response['customer']['customer_id'] = $customer->getId();
                $response['customer']['created_at'] = $customer->getCreatedAt();
                $response['customer']['full_name'] = $customer->getName();
                $response['customer']['first_name'] = $customer->getFirstname();
                $response['customer']['last_name'] = $customer->getLastname();
                $response['customer']['updated_at'] = $customer->getUpdatedAt();
                $response['customer']['store_id'] = $customer->getStoreId();
                $response['customer']['website_id'] = $customer->getWebsiteId();
                $response['customer']['created_in'] = $customer->getCreatedIn();
                $response['customer']['email'] = $customer->getEmail();
                $response['customer']['group_id'] = $customer->getGroupId();
                $response['customer']['password_hash'] = $customer->getPasswordHash();
                //$response['customer_orders'] = $orders;
            } else {
                $response['message'] = "Password is incorrect.";
                $response['sid'] = $sessionId;
                Mage::log('error on login action2.. ' . $response['message'] . '\n', null, 'mobile_app.log');
            }
        } catch (Exception $e) {
            if (method_exists($e, 'getCustomMessage')) {
                $response['error_message'] = $e->getCustomMessage();
            } elseif (method_exists($e, 'getMessage')) {
                $response['error_message'] = $e->getMessage();
            }
            if (is_null($response['error_message'])) {
                $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
            } elseif (strstr($response['message'], '_')) {
                $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
            }
            $response['sid'] = $sessionId;
            $response['message'] = "Email and password is incorrect, please try again";
            Mage::log('error on login action1.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }


    /**
     * Function to register a new customer through app
     *
     * @access public
     * @params String email, string password, int gender
     * @return Array with customer data
     *
     */
    public function addCustomerAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $storeId = Mage::app()->getWebsite()->getId();
        $sessionId = $this->getRequest()->getPost('sid');
        $email = $this->getRequest()->getPost('email');
        $password = $this->getRequest()->getPost('password');
        $gender = $this->getRequest()->getPost('gender');
        $fname = $this->getRequest()->getPost('firstname');
        $lname = $this->getRequest()->getPost('lastname');
        $phone = $this->getRequest()->getPost('phone');
        $country = $this->getRequest()->getPost('country');
        $customerObj = new stdClass();
        $customerObj->customer_data = new stdClass();
        $response = array('success' => 0, 'message' => '', 'customer' => $customerObj, 'sid' => $sessionId);
        $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
        if (!$customer->getId()) {
            $customer->setEmail($email);
            $customer->setFirstname($fname);
            $customer->setLastname($lname);
            $customer->setPhoneNumber($phone);
            $customer->setCustomerCountry($country);
            $customer->setPassword($password);
            $customer->setGender($gender);
            try {
                $customer->save();
                if (Mage::getStoreConfig('customer/create_account/confirm')) {
                    $customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
                }else{
                    $customer->sendNewAccountEmail('registered', '', $customer->getStoreId());
                }
                $response['success'] = 1;
                $response['message'] = 'Thank you for registered at Elabelz';
                $response['customer'] = array("customer_data" => array("customer_id" => $customer->getId(), "created_at" => $customer->getCreatedAt(), "updated_at" => $customer->getUpdatedAt(), "store_id" => $customer->getStoreId(), "website_id" => $customer->getWebsiteId(), "created_in" => $customer->getCreatedIn(), "email" => $customer->getEmail(), "group_id" => $customer->getGroupId(), "password_hash" => $customer->getPasswordHash(), "login_message" => "", "customer_orders" => array()));
                $response['customer']['customer_data']['full_name'] = $customer->getName();
                $response['customer']['customer_data']['first_name'] = $customer->getFirstname();
                $response['customer']['customer_data']['last_name'] = $customer->getLastname();
                $response['customer']['customer_data']['phone_number'] = $customer->getPhoneNumber();
                $response['customer']['customer_data']['customer_country'] = $customer->getCustomerCountry();
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['message'] = $e->getMessage();
                }
                if (is_null($response['message'])) {
                    $response['message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['message'], '_')) {
                    $response['message'] = Mage::helper('emapi')->checkError($response['message']);
                }
                Mage::log('error on add Customer action.. ' . $response['message'] . '\n', null, 'mobile_app.log');
            }
        } else {
            $response['message'] = "This email already registered.";
            Mage::log('error on add Customer action.. ' . $response['message'] . '\n', null, 'mobile_app.log');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to subscribe or unsubscribe customer from newsletter
     *
     * @access public
     * @params int customer id
     * @return array with status and message
     *
     */
    public function subunsubnewsletterAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $customer_id = $this->getRequest()->getPost('customer_id');
        $customer = Mage::getModel('customer/customer')->load($customer_id);
        $email = $customer->getEmail();
        $emailExist = Mage::getModel('newsletter/subscriber')->load($email, 'subscriber_email');
        if ($emailExist->getSubscriberStatus() == 1) {
            Mage::getModel('newsletter/subscriber')->loadByEmail($email)->unsubscribe();
            $response["status"] = "1";
            $response["email"] = $email;
            $response["msg"] = "Unsubscribed Successfully";
        } else {
            Mage::getModel('newsletter/subscriber')->load($email, 'subscriber_email')->subscribe($email);
            $response["status"] = "1";
            $response["email"] = $email;
            $response["msg"] = "Subscribed Successfully";
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to update customer information through app
     *
     * @access public
     * @params int customer id, and customer details to update
     * @return array with status and customer details
     *
     */
    public function updateCustomerAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $email = $this->getRequest()->getPost('email');
        $firstname = $this->getRequest()->getPost('firstname');
        $lastname = $this->getRequest()->getPost('lastname');
        $phone = $this->getRequest()->getPost('phone');
        $gender = $this->getRequest()->getPost('gender');
        $country = $this->getRequest()->getPost('country');
        $customer_id = $this->getRequest()->getPost('customer_id');
        $storeId = Mage::app()->getWebsite()->getId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
        //condition to match provided email and customer id
        if ($customer->getId() != $customer_id) {return;}
        $customer_data = array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone_number' => $phone,
            'customer_country' => $country
        );
        $response = array('success' => 0, 'message' => '', 'customer' => array());
        try {
            $customer = Mage::getModel('customer/customer')->load($customer_id);
            if ($customer->getId()) {
                $customer->setEmail($email);
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setPhoneNumber($phone);
                $customer->setCustomerCountry($country);
                $customer->setGender($gender);
                $customer->save();
                $response['success'] = 1;
                $response['message'] = 'Customer updated';
                $response['customer'] = $customer_data;
            } else {
                $response['message'] = "Invalid customer id provided.";
            }

        } catch (Exception $e) {
            $response['error_code'] = $e->getCode();
            if (method_exists($e, 'getCustomMessage')) {
                $response['message'] = $e->getCustomMessage();
            } elseif (method_exists($e, 'getMessage')) {
                $response['message'] = $e->getMessage();
            }
            if (is_null($response['message'])) {
                $response['message'] = Mage::helper('emapi')->checkError($e->getMessage());
            } elseif (strstr($response['message'], '_')) {
                $response['message'] = Mage::helper('emapi')->checkError($response['message']);
            }
            Mage::log('error on update Customer action.. ' . $response['message'] . '\n', null, 'mobile_app.log');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to update customer password through app
     *
     * @access public
     * @params int customer id, and string password
     * @return array with status
     *
     */
    public function updateCustomerPasswordAction()
    {
        return;
        /*if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $customer_id = $this->getRequest()->getPost('customer_id');
        $password = $this->getRequest()->getPost('password');
        //use getPost in case if getPost not working
        $response = array('success' => 0, 'message' => '');
        try {
            $customer = Mage::getModel('customer/customer')->load($customer_id);
            $customer->setPassword($password);
            $customer->save();
            $response['success'] = 1;
            $response['message'] = 'Your Password has been Changed Successfully';
        } catch (Exception $ex) {
            $response['message'] = 'Error : ' . $ex->getMessage();
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;*/
    }

    /**
     * Function to get all the available addresses and assign it to cart
     *
     * @access public
     * @params int customer id, string email, int qoute id
     * @return array with available addresses and available payment and shipping methods
     *
     */
    public function getCustomerAddressAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $customerId = $this->getRequest()->getPost('cid');
        $email = $this->getRequest()->getPost('email');
        $sessionId = $this->getRequest()->getPost('sid');
        $quoteId = $this->getRequest()->getPost('qid');
        $storeId = Mage::app()->getWebsite()->getId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
        //condition to match provided email and customer id
        if ($customer->getId() != $customerId) {return;}
        $addresses = new stdClass();
        $addresses->complexObjectArray = array();
        $response = array('success' => 0, 'message' => '', 'addresses' => $addresses, 'sid' => $sessionId);
        $payment = array();
        $shipping = array();
        if (Mage::getStoreConfig('api/emapi/getCustomerAddress')) {
            try {
                $res = new stdClass();
                $customerAddressApiMdl = Mage::getSingleton('restmob/customer_address_api');
                $result = $customerAddressApiMdl->items($customerId);
                if (!empty($result)) {
                    foreach ($result as $re) {
                        if ($re['is_default_shipping'] == 1) {
                            $data['sid'] = $sessionId;
                            $data['qid'] = $quoteId;
                            $data['mode'] = "shipping";
                            $data['firstname'] = $re['firstname'];
                            $data['email'] = $email;
                            $data['lastname'] = $re['lastname'];
                            $data['street1'] = $re['street'];
                            $data['street'] = $re['street'];
                            $data['city'] = $re['city'];
                            $data['country_id'] = $re['country_id'];
                            $data['telephone'] = $re['telephone'];
                            $data['postcode'] = $re['postcode'];
                            $data['region'] = $re['region'];
                            $data['region_id'] = $re['region_id'];
                            $data['diff'] = false;
                            $data['customer_id'] = $customerId;
                            $retParent = parent::setShippingAddress($data);
                            if ($retParent['status']) {
                                $response['set_shipping'] = true;
                                $response['shipping_message'] = "Shipping address attached successfully.";
                            } else {
                                $response['set_shipping'] = false;
                                $response['shipping_message'] = "Error occurred while setting shipping address";
                            }
                        }
                    }
                    $showPayment = true;
                    if($response['set_shipping']){
                        $showPayment = true;
                        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                        if (Mage::getStoreConfig('api/emapi/setShippingAddress')) {
                            $mdlEmapi = Mage::getModel('restmob/quote_index');
                            $id = $mdlEmapi->getIdByQuoteId($quoteId);
                            $mdlEmapi->load($id);
                            if($mdlEmapi->getShippingCustomerInfo()) {
                                $showPayment = true;
                            }else{
                                $showPayment = false;
                            }
                        }else{
                            //new condition to check if address is properly attached to quote
                            //$qsa = quote shipping address
                            //$qba = quote billing address
                            $qsa = $quote->getShippingAddress();
                            $qba = $quote->getBillingAddress();
                            if (($qsa->getFirstname() == null || $qsa->getLastname() == null
                                    || $qsa->getStreet() == null || $qsa->getCity() == null ||
                                    $qsa->getCountryId() == null || $qsa->getTelephone() == null
                                    || $qsa->getEmail() == null
                                ) &&
                                ($qba->getFirstname() == null || $qba->getLastname() == null
                                    || $qba->getStreet() == null || $qba->getCity() == null ||
                                    $qba->getCountryId() == null || $qba->getTelephone() == null
                                    || $qba->getEmail() == null
                                )
                            ){
                                $showPayment = false;
                            }else{
                                $showPayment = true;
                            }
                        }
                    }else{
                        $showPayment = false;
                    }
                    if($showPayment){
                        if (Mage::getStoreConfig('api/emapi/getPaymentShipment')) {
                            $paymentShipment = parent::getPaymentShipmentListSoapless($data['country_id'],$data['country_id'],$quote->getBaseSubtotal());
                        }else {
                            $paymentShipment = parent::getPaymentShipmentList($quoteId);
                        }
                        if ($paymentShipment['status']) {
                            $response['success'] = 1;
                            $response['message'] = 'data found';
                            $payment = $paymentShipment['payment'];
                            $shipping = $paymentShipment['shipping'];
                        } else {
                            $response['success'] = 0;
                            $response['message'] = 'Try again, shipping address are not loaded';
                        }
                    }else{
                        $response['success'] = 0;
                        $response['message'] = 'Try again, shipping address are not loaded';
                    }
                    $res->complexObjectArray = $result;
                } else {

                    $res->complexObjectArray = array();
                }
                $response['payment_methods'] = $payment;
                $response['shipping_methods'] = $shipping;
                $response['addresses'] = $res;
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['error_message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['error_message'] = $e->getMessage();
                }
                if (is_null($response['error_message'])) {
                    $response['error_message'] = Mage::helper('restmob')->checkError($e->getMessage());
                } elseif (strstr($response['message'], '_')) {
                    $response['error_message'] = Mage::helper('restmob')->checkError($response['message']);
                }
                $response['message']="Address not created, Please try again";
                Mage::log('Soap less error on getCustomerAddress action3.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }
        }else {
            parent::setProxy();
            $sessionId = parent::loginembedded();
            try {
                $proxy = $this->proxy;
                $res = $proxy->customerAddressList((object)array('sessionId' => $sessionId, 'customerId' => $customerId));
                $res = $res->result;
                if (!empty($res->complexObjectArray)) {
                    if (sizeof($res->complexObjectArray) == 1) {
                        $res->complexObjectArray = array($res->complexObjectArray);
                    }
                    foreach ($res->complexObjectArray as $re) {
                        if ($re->is_default_shipping == 1) {
                            $data['sid'] = $sessionId;
                            $data['qid'] = $quoteId;
                            $data['mode'] = "shipping";
                            $data['firstname'] = $re->firstname;
                            $data['email'] = $email;
                            $data['lastname'] = $re->lastname;
                            $data['street1'] = $re->street;
                            $data['city'] = $re->city;
                            $data['country_id'] = $re->country_id;
                            $data['telephone'] = $re->telephone;
                            $data['postcode'] = $re->postcode;
                            $data['region'] = $re->region;
                            $data['region_id'] = $re->region_id;
                            $data['diff'] = false;
                            $data['customer_id'] = $customerId;
                            $retParent = parent::setShippingAddress($data);
                            if ($retParent['status']) {
                                $response['set_shipping'] = true;
                                $response['shipping_message'] = "Shipping address attached successfully.";
                            } else {
                                $response['set_shipping'] = false;
                                $response['shipping_message'] = "Error occured while setting shipping address";
                            }
                        }
                    }
                    $showPayment = true;
                    if($response['set_shipping']){
                        $showPayment = true;
                        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                        if (Mage::getStoreConfig('api/emapi/setShippingAddress')) {
                            $mdlEmapi = Mage::getModel('restmob/quote_index');
                            $id = $mdlEmapi->getIdByQuoteId($quoteId);
                            $mdlEmapi->load($id);
                            if($mdlEmapi->getShippingCustomerInfo()) {
                                $showPayment = true;
                            }else{
                                $showPayment = false;
                            }
                        }else{
                            //new condition to check if address is properly attached to quote
                            //$qsa = quote shipping address
                            //$qba = quote billing address
                            $qsa = $quote->getShippingAddress();
                            $qba = $quote->getBillingAddress();
                            if (($qsa->getFirstname() == null || $qsa->getLastname() == null
                                    || $qsa->getStreet() == null || $qsa->getCity() == null ||
                                    $qsa->getCountryId() == null || $qsa->getTelephone() == null
                                    || $qsa->getEmail() == null
                                ) &&
                                ($qba->getFirstname() == null || $qba->getLastname() == null
                                    || $qba->getStreet() == null || $qba->getCity() == null ||
                                    $qba->getCountryId() == null || $qba->getTelephone() == null
                                    || $qba->getEmail() == null
                                )
                            ){
                                $showPayment = false;
                            }else{
                                $showPayment = true;
                            }
                        }
                    }else{
                        $showPayment = false;
                    }
                    if($showPayment){
                        if (Mage::getStoreConfig('api/emapi/getPaymentShipment')) {
                            $paymentShipment = parent::getPaymentShipmentListSoapless($data['country_id'],$data['country_id'],$quote->getBaseSubtotal());
                        }else {
                            $paymentShipment = parent::getPaymentShipmentList($quoteId);
                        }
                        if ($paymentShipment['status']) {
                            $response['success'] = 1;
                            $response['message'] = 'data found';
                            $payment = $paymentShipment['payment'];
                            $shipping = $paymentShipment['shipping'];
                        } else {
                            $response['success'] = 0;
                            $response['message'] = 'Try again, shipping address are not loaded';
                        }
                    }else{
                        $response['success'] = 0;
                        $response['message'] = 'Try again, shipping address are not loaded';
                        Mage::log('Line 751 usersoap: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
                    }
                } else {
                    $res->complexObjectArray = array();
                }
                $response['payment_methods'] = $payment;
                $response['shipping_methods'] = $shipping;
                $response['addresses'] = $res;
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                $response['message'] = $e->getMessage();
                Mage::log('Line 751 usersoap: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to update customer address
     *
     * @access public
     * @params string sessionid, int address id, array customer data
     * @return array result or exception
     *
     */
    protected function updateCustomerAddress($addressId, $customerData)
    {
        if (Mage::getStoreConfig('api/emapi/soaplessAddressUpdate')) {
            $customerAddressApiMdl = Mage::getModel('emapi/customer_address_api');
            $res = $customerAddressApiMdl->update($addressId,array(
                'firstname' => $customerData['firstname'],
                'lastname' => $customerData['lastname'],
                'street' => array($customerData['street1'], $customerData['street2']),
                'city' => $customerData['city'],
                'country_id' => $customerData['country_id'],
                'postcode' => $customerData['postcode'],
                'telephone' => $customerData['telephone'],
                'region' => $customerData['region'],
                'region_id' => $customerData['region_id'],
                'is_default_billing' => $customerData['is_default_billing'],
                'is_default_shipping' => $customerData['is_default_shipping']
            ));
        }else {
            parent::setProxy();
            $sessionId = parent::loginembedded();
            $proxy = $this->proxy;
            $result = $proxy->customerAddressUpdate((object)array('sessionId' => $sessionId, 'addressId' => $addressId, 'addressData' => ((object)array(
                'firstname' => $customerData['firstname'],
                'lastname' => $customerData['lastname'],
                'street' => array($customerData['street1'], $customerData['street2']),
                'city' => $customerData['city'],
                'country_id' => $customerData['country_id'],
                'postcode' => $customerData['postcode'],
                'telephone' => $customerData['telephone'],
                'region' => $customerData['region'],
                'region_id' => $customerData['region_id'],
                'is_default_billing' => $customerData['is_default_billing'],
                'is_default_shipping' => $customerData['is_default_shipping']
            ))));
            $res = $result->result;
        }
        return $res;
    }

    /**
     * Function to update customer address through app
     *
     * @access public
     * @params int address id, and address details
     * @return array with status and message
     *
     */
    public function updateCustomerAddressAction()
    {
        return;
        /*if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $addressId = $this->getRequest()->getPost('aid');
        $customerData = array();
        $customerData['firstname'] = $this->getRequest()->getPost('firstname');
        $customerData['lastname'] = $this->getRequest()->getPost('lastname');
        $customerData['street1'] = $this->getRequest()->getPost('street1');
        $customerData['street2'] = $this->getRequest()->getPost('street2');
        $customerData['city'] = $this->getRequest()->getPost('city');
        $customerData['country_id'] = $this->getRequest()->getPost('country_id');
        $customerData['postcode'] = $this->getRequest()->getPost('postcode');
        $customerData['telephone'] = $this->getRequest()->getPost('telephone');
        $customerData['region'] = $this->getRequest()->getPost('region');
        $customerData['region_id'] = $this->getRequest()->getPost('region_id');
        $customerData['is_default_billing'] = $this->getRequest()->getPost('defafult_billing');
        $customerData['is_default_shipping'] = $this->getRequest()->getPost('default_shipping');
        $response = array('success' => 0, 'message' => '', 'customer' => false);
        try {
            $res = $this->updateCustomerAddress($addressId, $customerData);;
            $response['success'] = 1;
            $response['sid'] = '';
            $response['message'] = 'Customer address updated';
            $response['customer'] = $res;

        } catch (Exception $e) {
            $response['error_code'] = $e->getCode();
            $response['error_message'] = $e->getMessage();
            $response['sid'] = $sessionId;
            $response['message'] = 'Customer address is not updated, please try again';
            Mage::log('Soap less error on updateCustomerAddressAction  action1.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;*/
    }

    /**
     * Function to delete customer address through app
     *
     * @access public
     * @params int address id
     * @return array with status and message
     *
     */
    public function deleteCustomerAddressAction()
    {
        return;
        /*if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $addressId = $this->getRequest()->getPost('aid');
        $response = array('success' => 0, 'message' => '', 'customer' => false);
        try {
            if (Mage::getStoreConfig('api/emapi/soaplessAddressDelete')) {
                $sessionId = '';
                $customerAddressApiMdl = Mage::getModel('emapi/customer_address_api');
                $res = $customerAddressApiMdl->delete($addressId);
            }else{
                parent::setProxy();
                $sessionId = parent::loginembedded();
                $proxy = $this->proxy;
                $result = $proxy->customerAddressDelete((object)array('sessionId' => $sessionId, 'addressId' => $addressId));
                $res = $result->result;
            }
            $response['success'] = 1;
            $response['sid'] = $sessionId;
            $response['message'] = 'Customer address deleted successfully';
            $response['customer'] = $res;

        } catch (Exception $e) {
            $response['error_code'] = $e->getCode();
            $response['message'] = $e->getMessage();
            $response['sid'] = $sessionId;
            Mage::log('error on deleteCustomerAddressAction  action.. ' . $response['message'] . '\n', null, 'mobile_app.log');
        }

        header("Content-Type: application/json");
        echo json_encode($response);
        die;*/
    }

    //todo
    public function customerSuscribtionAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $customerId = $this->getRequest()->getPost('cid');
        $subscribe = $this->getRequest()->getPost('sub');
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $customer_email = $customer->getEmail();
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer_email);
        if ($subscribe == "true") {
            Mage::getModel('newsletter/subscriber')->subscribe($customer_email);
        } elseif ($subscribe == "false") {
            $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
            $subscriber->save();
            Mage::getModel('newsletter/subscriber')->loadByEmail($customer_email)->unsubscribe();
        }
        echo $subscribe;
    }

    //todo
    public function getCustomerSuscribtionAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $customerId = $this->getRequest()->getPost('cid');
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $customer_email = $customer->getEmail();
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer_email);

        if (!$subscriber->getId()
            || $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED
            || $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE
        ) {
            echo 'false';
        } else {
            echo 'true';
        }
        die();

    }

    /**
     * Function to get the add new address into customer address book
     *
     * @access public
     * @params int customer id and customer details
     * @return array with status and message
     *
     */
    public function addCustomerAddressAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $sessionId = "";
        $customerId = $this->getRequest()->getPost('customer_id');
        $quoteId = $this->getRequest()->getPost('qid');
        $customerData = array();
        $customerData['sid'] = $sessionId;
        $customerData['qid'] = $this->getRequest()->getPost('qid');
        $customerData['firstname'] = $this->getRequest()->getPost('firstname');
        $customerData['email'] = $this->getRequest()->getPost('email');
        $customerData['lastname'] = $this->getRequest()->getPost('lastname');
        $customerData['street1'] = $this->getRequest()->getPost('street1') . ', ' . $this->getRequest()->getPost('street2');
        $customerData['street'] = $this->getRequest()->getPost('street1') . ', ' . $this->getRequest()->getPost('street2');
        $customerData['city'] = $this->getRequest()->getPost('city');
        $customerData['country_id'] = $this->getRequest()->getPost('country_id');
        $customerData['postcode'] = $this->getRequest()->getPost('postcode');
        $customerData['telephone'] = $this->getRequest()->getPost('telephone');
        $customerData['region'] = $this->getRequest()->getPost('region');
        $customerData['region_id'] = $this->getRequest()->getPost('region_id');
        $customerData['is_default_billing'] = $this->getRequest()->getPost('default_billing');
        $customerData['is_default_shipping'] = $this->getRequest()->getPost('default_shipping');
        $customerData['customer_id'] = $this->getRequest()->getPost('customer_id');
        $addressObj = new stdClass();
        $addressObj->complexObjectArray = array();
        $response = array('success' => 0, 'message' => '', 'customer' => array(), 'payment_methods' => array(), 'shipping_methods' => array(), 'addresses' => $addressObj);
        $payment = array();
        $shipping = array();
        try {
            $res = parent::addCustomerAddressSoapless($customerId, $customerData);
            if ($quoteId != "") {
                $customerData['sid'] = $sessionId;
                $customerData['mode'] = "shipping";
                $retParent = parent::setShippingAddress($customerData);
                if($retParent['status']){
                    $response['set_shipping'] = true;
                }else{
                    $response['set_shipping'] = false;
                }
                $showPayment = true;
                if($response['set_shipping']){
                    $showPayment = true;
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    if (Mage::getStoreConfig('api/emapi/setShippingAddress')) {
                        $mdlEmapi = Mage::getModel('restmob/quote_index');
                        $id = $mdlEmapi->getIdByQuoteId($quoteId);
                        $mdlEmapi->load($id);
                        if($mdlEmapi->getShippingCustomerInfo()) {
                            $showPayment = true;
                        }else{
                            $showPayment = false;
                        }
                    }else{
                        //new condition to check if address is properly attached to quote
                        //$qsa = quote shipping address
                        //$qba = quote billing address
                        $qsa = $quote->getShippingAddress();
                        $qba = $quote->getBillingAddress();
                        if (($qsa->getFirstname() == null || $qsa->getLastname() == null
                                || $qsa->getStreet() == null || $qsa->getCity() == null ||
                                $qsa->getCountryId() == null || $qsa->getTelephone() == null
                                || $qsa->getEmail() == null
                            ) &&
                            ($qba->getFirstname() == null || $qba->getLastname() == null
                                || $qba->getStreet() == null || $qba->getCity() == null ||
                                $qba->getCountryId() == null || $qba->getTelephone() == null
                                || $qba->getEmail() == null
                            )
                        ){
                            $showPayment = false;
                        }else{
                            $showPayment = true;
                        }
                    }
                }else{
                    $showPayment = false;
                }
                if($showPayment){
                    if (Mage::getStoreConfig('api/emapi/getPaymentShipment')) {
                        $paymentShipment = parent::getPaymentShipmentListSoapless($customerData['country_id'],$customerData['country_id'],$quote->getBaseSubtotal());
                    }else {
                        $paymentShipment = parent::getPaymentShipmentList($quoteId);
                    }
                    if ($paymentShipment['status']) {
                        $response['success'] = 1;
                        $response['message'] = 'data found';
                        $payment = $paymentShipment['payment'];
                        $shipping = $paymentShipment['shipping'];
                    } else {
                        $response['success'] = 0;
                        $response['message'] = 'Try again, shipping address are not loaded';
                    }
                }else{
                    $response['success'] = 0;
                    $response['message'] = 'Try again, shipping address are not loaded';
                    Mage::log('Line 1099s usersoap: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
                }

                $response['payment_methods'] = $payment;
                $response['shipping_methods'] = $shipping;
                $response['sid'] = $sessionId;
            } else {

                $response['success'] = 1;
                $response['sid'] = $sessionId;
                $response['message'] = 'Address Created';
            }
            //addresses
            $customerAddressApiMdl = Mage::getSingleton('restmob/customer_address_api');
            $res = $customerAddressApiMdl->items($customerId);
            if (sizeof($res) > 0) {
                $dataArray = $res;
                $res = new stdClass();
                $res->complexObjectArray = $dataArray;
            } else {
                $res = new stdClass();
                $res->complexObjectArray = array();
            }
            $response['addresses'] = $res;
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
            $response['sid'] = '';
            $response['message'] = 'Try again, shipping address are not created';
            Mage::log('Line 1141 usersoap: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to recover customer password from app
     *
     * @access public
     * @params string email
     * @return array with status and message
     *
     */
    public function forgotPasswordAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $email = $this->getRequest()->getPost('email');
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($email);
        if ($customer->getId()) {
            try {
                $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                $customer->sendPasswordResetConfirmationEmail();
                $response = array('success' => '1', 'message' => 'Email sent successfully');
            } catch (Exception $exception) {
                $response = array('success' => '0', 'message' => 'Email not sent, please try again');
            }
        } else {
            $response = array('success' => '0', 'message' => 'Wrong email address supplied');
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    public function getCustomerDetails2Action()
    {
        return;
        /*if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $customerId = $this->getRequest()->getPost('customer_id');
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getId()) {
            $ret['sid'] = '';
            $ret['error'] = 1;
            $ret['message'] = "Invalid customer id provided.";
            $ret['orders'] = array();
            $ret['addresses'] = new stdClass();

            header("Content-Type: application/json");
            echo json_encode($ret);
            die;
        }
        // Get all order of customers
        $orderCollection = Mage::getModel("sales/order")->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->addAttributeToSort('entity_id', 'DESC')
            ->setCurPage(1)->setPageSize(5);
        $email = $customer->getEmail();
        $name = $customer->getName();
        $orders = array();
        foreach ($orderCollection as $_order) {
            $order = array();
            $order['order_id'] = $_order->getRealOrderId();
            $order['grand_total'] = $_order->getGrandTotal();
            $order['currency'] = $_order->getOrderCurrencyCode();
            $order['status_label'] = $_order->getStatusLabel();
            $order['created_at'] = $_order->getCreatedAt();
            $order['updated_at'] = $_order->getUpdatedAt();
            $order['tracking'] = array();
            $i = 0;
            if($_order->hasShipments()) { //adding the check if the order have shipment then check for tracking info
                foreach ($_order->getTracksCollection() as $_track) {
                    $hash = Mage::helper('core')->urlEncode("order_id:{$_order->getId()}:{$_order->getProtectCode()}");
                    $order['tracking'][$i]['id'] = $_track->getEntityId();
                    $order['tracking'][$i]['hash'] = $hash;
                    $order['tracking'][$i]['popup_url'] = Mage::getBaseUrl() . "shipping/tracking/popup/hash/" . $hash . "/";
                    $order['tracking'][$i]['parent_id'] = $_track->getParentId();
                    $order['tracking'][$i]['number'] = $_track->getTrackNumber();
                    $order['tracking'][$i]['title'] = $_track->getTitle();
                    $order['tracking'][$i]['code'] = $_track->getCarrierCode();
                    $i++;
                }
            }
            try {
                $order['payment_method'] = $_order->getPayment()->getMethodInstance()->getTitle();
            } catch (Exception $e) {
                $order['payment_method'] = '';
            }
            $order['shipment_method'] = $_order->getShippingDescription();

            $address = $_order->getBillingAddress();
            $order['billing_address'] = array('firstname' => $address->getFirstname(), 'lastname' => $address->getLastname(), 'city' => $address->getCity(), 'country' => $address->getCountryId(), 'postcode' => $address->getPostcode(), 'email' => "", 'phone' => "", 'company' => $address->getCompany());
            $street = $address->getStreet();
            if (sizeof($street) == 1) {
                $street = $street[0];
            } else {
                $street = $street[0] . ', ' . $street[1];
            }
            $order['billing_address']['street'] = $street;


            $address = $_order->getShippingAddress();
            $order['shipping_address'] = array('firstname' => $address->getFirstname(), 'lastname' => $address->getLastname(), 'city' => $address->getCity(), 'country' => $address->getCountryId(), 'postcode' => $address->getPostcode(), 'email' => "", 'phone' => "", 'company' => $address->getCompany());
            $street = $address->getStreet();
            if (sizeof($street) == 1) {
                $street = $street[0];
            } else {
                $street = $street[0] . ', ' . $street[1];
            }
            $order['shipping_address']['street'] = $street;
            $orderItems = $_order->getAllItems();
            $i = 0;
            $orderCurrency = $_order->getOrderCurrencyCode();
            foreach ($orderItems as $sItem) {
                $sItem = $sItem->getData();
                if ($sItem['product_type'] == "configurable") {
                    $parentQty[$sItem['item_id']] = $sItem['qty_ordered'];
                    unset($orderItems[$i]);
                    $i++;
                    continue;
                }
                $product_id = $sItem['product_id'];
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product_id);
                $parentId = $parentIds[0];
                $obj = Mage::getModel('catalog/product')->load($parentId);
                if ($sItem['price'] == 0) {
                    $price = $obj->getSpecialPrice();
                    if (!$price) {
                        $price = $obj->getPrice();
                    }
                    $rowTotal = ($price * $parentQty[$sItem['parent_item_id']]);
                    $strgPrice = $price;
                    $sItem['price'] = (string) ceil(Mage::helper('directory')->currencyConvert($strgPrice, "AED", $orderCurrency));
                    $sItem['qty_ordered'] = $parentQty[$sItem['parent_item_id']];
                    $sItem['row_total'] = (string) ceil(Mage::helper('directory')->currencyConvert($rowTotal, "AED", $orderCurrency));
                    $sItem['name'] = $obj->getName();
                }
                $product = Mage::getModel('catalog/product')->load($product_id);
                $image = (string)Mage::helper('catalog/image')->init($product, 'small_image');
                $product_attributes = array();
                $attributes = $product->getAttributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->getIsVisibleOnFront()) {
                        $value = $attribute->getFrontend()->getValue($product);
                        $product_attributes[$attribute->getAttributeCode()] = $value;
                    }
                }
                $sItem['img'] = $image;
                $sItem['img2'] = $product->getImageUrl();

                if ($product->getData('color')) {
                    $sItem['color_id'] = $product->getData('color');
                    $sItem['color_value'] = $product->getAttributeText('color');
                }
                if ($product->getData('size')) {
                    $sItem['size_id'] = $product->getData('size');
                    $sItem['size_value'] = $product->getAttributeText('size');
                }
                if ($product->getData('styles')) {
                    $sItem['styles_id'] = $product->getData('styles');
                    $sItem['styles_value'] = $product->getAttributeText('styles');
                }
                $sItem['in_stock_qty'] = (int)$product->getStockItem()->getQty();
                $sItem['currency'] = $orderCurrency;
                $sItem['min_sale_qty'] = $product->getStockItem()->getMinSaleQty();
                $sItem['max_sale_qty'] = $product->getStockItem()->getMaxSaleQty();
                $orderItems[$i] = $sItem;
                $i++;
            }
            $orderItems = array_values($orderItems);
            $order['items_details'] = $orderItems;
            $orders[] = $order;
        }
        //getting customer addresses
        $customerAddressApiMdl = Mage::getSingleton('restmob/customer_address_api');
        $addresses = $customerAddressApiMdl->items($customerId);
        if (!empty($addresses)) {
            $total = count($addresses);
            $start = ($total - 1);
            $end = 0;
            if ($total > 6) {
                $end = ($total - 6);
            }
            $finalAddresses = array();
            for ($i = $start; $i >= $end; $i--) {
                $addresses[$i]['telephone'] = "";
                $finalAddresses[] = $addresses[$i];
            }
            $dataArray = $finalAddresses;
            $addresses = new stdClass();
            $addresses->complexObjectArray = $dataArray;
        } else {
            $addresses = new stdClass();
            $addresses->complexObjectArray = array();
        }
        $ret = array();
        $ret['sid'] = '';
        $ret['name'] = $name;
        $ret['firstname'] = $customer->getFirstname();
        $ret['lastname'] = $customer->getLastname();
        $ret['email'] = "";//$email;
        $ret['error'] = 0;
        $ret['message'] = "Record found.";

        $emailExist = Mage::getModel('newsletter/subscriber')->load($email, 'subscriber_email');
        if ($emailExist->getSubscriberStatus() == 1) {
            $ret["newsletter_status"] = "1";
        } else {
            $ret["newsletter_status"] = "0";
        }


        $ret['orders'] = $orders;
        $ret['addresses'] = $addresses;
        header("Content-Type: application/json");
        echo json_encode($ret);
        die;*/
    }

    /**
     * Function to check if customer is subscribed or not
     *
     * @access public
     * @params int customer id
     * @return array with newsletter status and message
     *
     */
    public function getNewsletterStatusAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $c = Mage::getModel('customer/customer')->load($this->getRequest()->getPost('customer_id'));
        $email = $c->getEmail();
        $response = array();
        $emailExist = Mage::getModel('newsletter/subscriber')->load($email, 'subscriber_email');
        if ($emailExist->getSubscriberStatus() == 1) {
            $response["status"] = "1";
        } else {
            $response["status"] = "0";
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }
}
