<?php
/**
 * Progos_Responsys.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand <saroop.chand@progos.org>
 * @Date 08 -03-2018
 *
 */
class Progos_Responsys_Model_Config
{
    public function getUsername(){
        return Mage::getStoreConfig('responsys_settings/responsys/username');
    }

    public function getPassword(){
        return Mage::getStoreConfig('responsys_settings/responsys/password');
    }

    public function getLoginApi(){
        return Mage::getStoreConfig('responsys_settings/responsys/login_api_endpoint');
    }

    public function getSubscriptionApi(){
        return Mage::getStoreConfig('responsys_settings/responsys/subscription_api_endpoint');
    }

    public function getMaleCampaign(){
        return Mage::getStoreConfig('responsys_settings/responsys/male');
    }

    public function getFemaleCampaign(){
        return Mage::getStoreConfig('responsys_settings/responsys/female');
    }

    public function getMemberList(){
        return Mage::getStoreConfig('responsys_settings/responsys/memberlist');
    }


    public function getCustomEventUrl(){
        return Mage::getStoreConfig('responsys_settings/responsys/customeventurl');
    }

    public function getSubscriber(){
        return Mage::getStoreConfig('responsys_settings/responsys/customeventsubscriber');
    }

    public function getStatus(){
        $status = Mage::getStoreConfig('responsys_settings/responsys/enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}