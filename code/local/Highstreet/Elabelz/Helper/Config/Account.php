<?php

class Highstreet_Elabelz_Helper_Config_Account extends Highstreet_Hsapi_Helper_Config_Account {

    public function login($requestObject) {
        $session = Mage::getSingleton('customer/session');
        $store = Mage::app()->getStore();

        $success = false;
        $message = "";

        $loginArray = $requestObject->getParam('login');

        $email = $loginArray["username"];
        $password = $loginArray["password"];
        $this->log($loginArray, 'User login');
        if ($session->isLoggedIn()) {
            $success = false;
            $message = "hsapi.loginAction.success.already";
        } else {
            try {
                if ($session->login($email, $password)) {
                    if ($session->isLoggedIn()) {
                        $customer = $session->getCustomer();
                        if ($customer->getStoreId() != $store->getStoreId()) {
                            $success = false; 
                            $message = "hsapi.loginAction.incorrectStoreview";
                        } else {
                            $success = true; 
                            $message = "hsapi.loginAction.success";
                        }
                    }
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
        $responseCode = ($success) ? "200 OK" : "400 Bad Request";
        $this->log($response, $responseCode);
        $this->_JSONencodeAndRespond($response, $responseCode);
        return;
    }
    
}
