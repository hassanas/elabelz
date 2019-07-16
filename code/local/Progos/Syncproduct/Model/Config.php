<?php
class Progos_Syncproduct_Model_Config
{
    public function getUrl(){
        return Mage::getStoreConfig('syncproduct_settings/syncproduct/url');
    }

    public function getSoapUrl(){
        return Mage::getStoreConfig('syncproduct_settings/syncproduct/soap_url');
    }

    public function getUsername(){
        return Mage::getStoreConfig('syncproduct_settings/syncproduct/username');
    }

    public function getApiKey(){
        return Mage::getStoreConfig('syncproduct_settings/syncproduct/apikey');
    }

    public function getTimeOut(){
        return Mage::getStoreConfig('syncproduct_settings/syncproduct/timeout');
    }

    public function getSkus(){
        $skus = Mage::getStoreConfig('syncproduct_settings/syncproduct/skus');
        return explode(",",$skus);
    }

    public function getStatus(){
        $status = Mage::getStoreConfig('syncproduct_settings/syncproduct/enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}