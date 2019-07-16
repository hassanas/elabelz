<?php
ini_set('default_socket_timeout', 120);
ini_set("max_execution_time", 0);
ini_set("memory_limit", "10000M");
error_reporting(E_ALL);

class Progos_Restmob_SoapController extends Mage_Core_Controller_Front_Action{
	public function preDispatch()
	{
		$this->getLayout()->setArea('adminhtml');
        $this->setFlag('', self::FLAG_NO_START_SESSION, 1); // Do not start standart session
        parent::preDispatch();
        return $this;
    }
    protected $storeId;
    protected $soapURLv1;
    protected $soapURLv2;
    protected $API_USER;
    protected $API_KEY;
    protected $authLogin;
    protected $authKey;
    /** @var SoapClient */
    protected $proxy;
    protected $prxy;

    /**
     * Function to get the values from settings and initialize soap Credentials
     *
     * @access private
     * @params No parameters required
     * @return not return anything
     *
     */
    private function setCredentials(){
        $this->storeId = Mage::app()->getStore()->getId();
        $this->soapURLv1 = Mage::getStoreConfig('api/emapi/v1_field', $this->storeId);
        $this->soapURLv2 = Mage::getStoreConfig('api/emapi/v2_field', $this->storeId);
        $this->API_USER = Mage::getStoreConfig('api/emapi/apiuser_field', $this->storeId);
        $this->API_KEY = Mage::getStoreConfig('api/emapi/apikey_field', $this->storeId);
        $this->authLogin = Mage::getStoreConfig('api/emapi/authuser_field', $this->storeId);
        $this->authKey = Mage::getStoreConfig('api/emapi/authpass_field', $this->storeId);
    }

    /**
     * Function to get the values from settings page initialize the soapClient for V2
     *
     * @access protected
     * @params No parameters required
     * @return not return anything
     *
     */
    protected function setProxy()
    {
        $this->setCredentials();
        try {

            if ($this->authLogin != '' && $this->authKey != '') {
                $this->proxy = new SoapClient($this->soapURLv2, array(
                    'login' => $this->authLogin,
                    'password' => $this->authKey,
                    'trace' => 1
                ));
            } else {
                $this->proxy = new SoapClient($this->soapURLv2, ['trace' => 1]);
            }
        } catch (Exception $error) {
            Mage::log('----REQUEST-----', Zend_Log::INFO, 'emapi-soap-logger.log', true);
            Mage::log((string)$error, Zend_Log::INFO, 'emapi-soap-logger.log', true);
            Mage::log((string)$this->proxy->__getLastRequest(), Zend_Log::INFO, 'emapi-soap-logger.log', true);
            Mage::log((string)$this->proxy->__getLastResponse(), Zend_Log::INFO, 'emapi-soap-logger.log', true);
        }
    }

    /**
     * Function to get the values from settings page initialize the soapClient for V1
     *
     * @access protected
     * @params No parameters required
     * @return not return anything
     *
     */
    protected function setPrxy()
    {
        $this->setCredentials();
        if ($this->authLogin != '' && $this->authKey != '') {
            $this->prxy = new SoapClient($this->soapURLv1, array(
                'login' => $this->authLogin,
                'password' => $this->authKey
            ));
        } else {
            $this->prxy = new SoapClient($this->soapURLv1);
        }
    }

    /**
     * Function to get the sessionId for SOAP calls
     *
     * @access public
     * @params No parameters required
     * @return string sessionId
     *
     */
    public function loginembedded()
    {
        $proxy = $this->proxy;
        $token = $proxy->login((object)array('username' => $this->API_USER, 'apiKey' => $this->API_KEY));
        $token = $token->result;
        return $token;
    }
    
    public function verifyembedded($sessionId){
    	return false;        
    }
}