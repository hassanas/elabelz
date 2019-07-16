<?php

/**
 * Progos_Responsys.
 *
 * @category Elabelz
 *
 * @Author Hassan Ali Shahzad <hassan.ali@progos.org>
 * @Date 28-06-2018 15:44
 *
 */
class Progos_Responsys_Model_Responsys extends Mage_Core_Model_Abstract
{
    protected $curl = null;
    protected $config;
    protected $_token = null;

    public function __construct()
    {
        $this->curl = new Zend_Http_Client();
        $this->config = Mage::getSingleton('progos_responsys/Config');
    }

    /**
     * This Function is responsible to add new entry or update customer into responsys
     * @param $requestBody This variable contains curl request body as per responsys Documentation
     */
    public function addUpdateCustomer($requestBody)
    {

        $url = $this->config->getMemberList();
        try {
            $token = $this->getToken();

            if (!empty($token)) {
                $response = null;
                $requestBody = json_encode($requestBody);
                $response = $this->_sendRequest($url, $requestBody, $token);
                $response = json_decode(Zend_Http_Response::extractBody($response));

                if ($response->errorCode) {
                    Mage::log(print_r($response, true), null, 'responsys.log');
                    return false;
                } else {
                    return true;
                }
            } else {
                Mage::log("Empty Authuntication Token. ", null, 'responsys.log');
            }
        } catch (Exception $e) {
            Mage::log("Some Error Occured,on new customer subscription: " . $e->getMessage(), null, 'responsys.log');
        }
    }

    /**
     * This function will trigger custom event
     * @param $requestBody ; url
     * @param $eventName   ; event name which need to trigger
     * @return bool
     */
    public function triggerCustomEvent($requestBody,$eventName)
    {
        $url = $this->config->getCustomEventUrl()."/".$eventName;

        try {
            $token = $this->getToken();

            if (!empty($token)) {
                $response = null;
                $requestBody = json_encode($requestBody);
                $response = $this->_sendRequest($url, $requestBody, $token);
                $response = json_decode(Zend_Http_Response::extractBody($response));

                if ($response->success == true || $response->success == 1 || $response->errorMessage == null) {
                    return true;
                } else {
                    Mage::log(print_r("Error in custom event:- ". $url, true), null, 'responsys.log');
                    Mage::log(print_r($response, true), null, 'responsys.log');
                    return false;
                }
            } else {
                Mage::log("Empty Authuntication Token on Custom Event. ", null, 'responsys.log');
            }

        } catch (Exception $e) {
            Mage::log("Some Error Occured,on custom event: " . $e->getMessage(), null, 'responsys.log');
        }
    }

    /**
     * This function is responsible to send request
     * @param $url
     * @param $requestBody
     * @param $token
     * @return string|Zend_Http_Response
     */
    protected function _sendRequest($url, $requestBody, $token)
    {
        try {
            $headers = ['Authorization' => $token, 'Content-Type' => 'application/json'];
            // Such big timeout is needed because SkuVault updateProducts request is taking a lot of time.
            $this->curl->setConfig(array('timeout' => 120));
            $this->curl->setUri($url);
            $this->curl->setRawData($requestBody);
            $this->curl->setHeaders($headers);

            return $response = $this->curl->request(Zend_Http_Client::POST);
        } catch (Exception $e) {
            Mage::log("Authuntication Fail. " . $e->getMessage(), null, 'responsys.log');
            return "";
        }
    }

    /**
     * @param null $url
     * @param array() $headers
     * @param array() $requestBody
     * @param 1 $count
     * @return null|string
     *
     * Send request to Resposys Api and get authentication token for more api call.
     */
    protected function getToken($url = "", $headers = array(), $requestBody = array(), $count = 1)
    {
        if($this->_token != null){
            return $this->_token;
        }
        else{
            $url = $this->config->getLoginApi();
            $response = null;
            $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
            $requestBody = ['user_name' => $this->config->getUsername(), 'password' => $this->config->getPassword(), 'auth_type' => 'password',];

            try {
                // Such big timeout is needed because SkuVault updateProducts request is taking a lot of time.
                $this->curl->setConfig(array('timeout' => 120));
                $this->curl->setUri($url);
                $this->curl->setParameterPost($requestBody);
                $this->curl->setHeaders($headers);
                $response = $this->curl->request(Zend_Http_Client::POST);
            } catch (Exception $e) {
                if ($count == 1) {
                    $this->getToken($url, $headers, $requestBody, 2);
                }
                Mage::log("Authuntication Fail. " . $e->getMessage(), null, 'responsys.log');
                return "";
            }
            $response = json_decode(Zend_Http_Response::extractBody($response));
            if ($response->authToken){
                $this->_token = $response->authToken;
                return $this->_token;
            }
            else
                Mage::log(print_r($response, true), null, 'responsys.log');
            return "";
        }
    }

}