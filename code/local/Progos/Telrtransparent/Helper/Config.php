<?php

/**
 *  This Module is used for Multiple Payments on different payment gateways for Credit Cards
 *  This is configuration helper to get configurations of both telertransparent and checkoutDotCom
 *
 * @category       Progos
 * @package        Progos_Telrtransparent
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           08-02-2017 17:53
 *
 */
class Progos_Telrtransparent_Helper_Config extends Mage_Core_Helper_Abstract
{


    /**
     * @return mixed|string
     */
    public function getCheckoutDotComScriptUrl(){
        $scriptUrl = "";
        $isTestMode = Mage::getStoreConfig('payment/telrtransparent/sandbox');
        if ($isTestMode) {
            $scriptUrl = Mage::getStoreConfig('payment/telrtransparent/sandboxscriptsource');
        } else {
            $scriptUrl = Mage::getStoreConfig('payment/telrtransparent/livescriptsource');
        }
        return $scriptUrl;
    }

    /**
     * @return mixed|string
     */
    public function getCheckoutDotComApiUrl(){
        $apiUrl = "";
        $isTestMode = Mage::getStoreConfig('payment/telrtransparent/sandbox');
        if ($isTestMode) {
            $apiUrl = Mage::getStoreConfig('payment/telrtransparent/sandboxapiurl');
        } else {
            $apiUrl = Mage::getStoreConfig('payment/telrtransparent/liveapiurl');
        }
        return $apiUrl;
    }

    /**
     * @param $currencyCode
     * @return mixed|string
     */
    public function getCheckoutDotComPublicKey($currencyCode)
    {

        $publicKey = "";
        $isTestMode = Mage::getStoreConfig('payment/telrtransparent/sandbox');
        if ($isTestMode) {
            if ($currencyCode == "AED") {
                $publicKey = Mage::getStoreConfig('payment/telrtransparent/sandboxpublickeyaed');
            } else {
                $publicKey = Mage::getStoreConfig('payment/telrtransparent/sandboxpublickeynonaed');
            }
        } else {
            if ($currencyCode == "AED") {
                $publicKey = Mage::getStoreConfig('payment/telrtransparent/livepublickeyaed');
            } else {
                $publicKey = Mage::getStoreConfig('payment/telrtransparent/livepublickeynonaed');
            }
        }
        return $publicKey;
    }

    /**
     * @param $currencyCode
     * @return mixed|string
     */
    public function getCheckoutDotComPrivateKey($currencyCode)
    {
        $privateKey = "";
        $isTestMode = Mage::getStoreConfig('payment/telrtransparent/sandbox');
        if ($isTestMode) {
            if ($currencyCode == "AED") {
                $privateKey = Mage::getStoreConfig('payment/telrtransparent/sandboxprivatekeyaed');
            } else {
                $privateKey = Mage::getStoreConfig('payment/telrtransparent/sandboxprivatekeynonaed');
            }
        } else {
            if ($currencyCode == "AED") {
                $privateKey = Mage::getStoreConfig('payment/telrtransparent/liveprivatekeyaed');
            } else {
                $privateKey = Mage::getStoreConfig('payment/telrtransparent/liveprivatekeynonaed');
            }
        }
        return $privateKey;
    }


    /**
     * @return payment method key which is active
     */
    public function getActivePaymentMethod(){
        return Mage::getStoreConfig('payment/telrtransparent/paymentmethodtouse');
    }


}