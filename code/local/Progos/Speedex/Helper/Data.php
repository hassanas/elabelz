<?php
class Progos_Speedex_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getSoapUrl()
    {
        if(Mage::getStoreConfig('speedex/settings/sandbox')) {
            return "http://168.187.136.18:8095/APIService/PostaWebClient.svc?wsdl";
        } else {
            return "http://www.postaplus.net/APIService/PostaWebClient.svc?wsdl";
        }
    }
    
    public function getCredentials()
    {
        return array(
            'CODE_STATION' => Mage::getStoreConfig('speedex/settings/station_code'), //your station code
            'ACCOUNT_ID' => Mage::getStoreConfig('speedex/settings/account_number'), // account id
            'USER_NAME' => Mage::getStoreConfig('speedex/settings/user_name'),//  username
            'PASSWORD' => Mage::getStoreConfig('speedex/settings/password') //password
        );
    }
    
    public function getEmails($configPath, $storeId)
    {
        $data = Mage::getStoreConfig($configPath,$storeId);
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }
}