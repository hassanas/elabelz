<?php

class Aramex_Shipment_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getClientInfo($storeId) {
        $username = Mage::getStoreConfig('aramexsettings/settings/user_name', $storeId);
        $password = Mage::getStoreConfig('aramexsettings/settings/password', $storeId);
        $account = Mage::getStoreConfig('aramexsettings/settings/account_number', $storeId);
        $pin = Mage::getStoreConfig('aramexsettings/settings/account_pin', $storeId);
        $entity = Mage::getStoreConfig('aramexsettings/settings/account_entity',$storeId);
        $country_code = Mage::getStoreConfig('aramexsettings/settings/account_country_code',$storeId);
        return array(
            'AccountCountryCode' => $country_code,
            'AccountEntity' => $entity,
            'AccountNumber' => $account,
            'AccountPin' => $pin,
            'UserName' => $username,
            'Password' => $password,
            'Version' => 'v1.0',
            'Source' => 31
        );
    }

    public function getClientInfoCOD($storeId) {
        $username = Mage::getStoreConfig('aramexsettings/settings/user_name', $storeId);
        $password = Mage::getStoreConfig('aramexsettings/settings/password', $storeId);
        $account = Mage::getStoreConfig('aramexsettings/settings/cod_account_number', $storeId);
        $pin = Mage::getStoreConfig('aramexsettings/settings/cod_account_pin', $storeId);
        $entity = Mage::getStoreConfig('aramexsettings/settings/cod_account_entity', $storeId);
        $country_code = Mage::getStoreConfig('aramexsettings/settings/cod_account_country_code', $storeId);
        return array(
            'AccountCountryCode' => $country_code,
            'AccountEntity' => $entity,
            'AccountNumber' => $account,
            'AccountPin' => $pin,
            'UserName' => $username,
            'Password' => $password,
            'Version' => 'v1.0',
            'Source' => 31
        );
    }

    public function getWsdlPath($storeId, $force_live = false) {
        $wsdlBasePath = Mage::getBaseDir()  . '/media/aramex_api/wsdl/Aramex/';
        if (Mage::getStoreConfig('aramexsettings/config/sandbox_flag', $storeId) == 1) {
            if ($force_live == false) {
                $wsdlBasePath .='TestMode/';
            }
        }
        return $wsdlBasePath;
    }

    public function getMultiSort($data) {
        usort($data, $this->_getUsort($a, $b));
    }

    public function _getUsort($a, $b) {
        return $a['name'] - $b['name'];
    }

}
