<?php

class Progos_Emapi_Model_Storecredit
{
    private $customerId;
    private $isFloat;
    private $storeCredit = array();
    private $storeBalance = 0;
    private $storeCreditEnabled;

    /*
     * constructor
     */
    public function __construct()
    {
        $this->storeCredit = array();
        $this->storeBalance = 0;
        $this->storeCreditEnabled = Mage::getStoreConfig('api/emapi/enable_storecredit');
    }

    /*
     * function to set store credits data
     */
    public function setStoreCreditParams($customerId,$isFloat){
        $this->isFloat = $isFloat;
        $this->customerId = $customerId;
    }

    /*
     * Function to get store credits
     */
    public function customerStoreCredits()
    {
        $data = array();
        if ($this->storeCreditEnabled) {
            $storeCreditModel = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($this->customerId);
            $this->storeBalance = $storeCreditModel->getBalance();
        }
        $data[0]['code'] = 'store_credit';
        $data[0]['title'] = "Store Credit";
        if ($this->isFloat) {
            $data[0]['price'] = (float)number_format((float)Mage::helper('core')->currency($this->storeBalance, false, false), 2, '.', '');
        } else {
            $data[0]['price'] = ceil(Mage::helper('core')->currency($this->storeBalance, false, false));
        }
        $this->storeCredit = $data;
    }

    /*
     * function to return store credit
     */
    public function getStoreCredit()
    {
        return $this->storeCredit;
    }

}
