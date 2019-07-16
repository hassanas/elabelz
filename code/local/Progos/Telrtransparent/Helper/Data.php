<?php
class Progos_Telrtransparent_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param $price
     * @param $currencyCode
     * @return float
     */
    public function getCheckoutDotComPrice($price, $currencyCode){
        // In case of KWD need to multiply price with 1000 for remaining 100 checkoutDonCom requirment
        if($currencyCode == "KWD"){
            $price = round($price * 1000);
        }
        else{
            $price = round($price * 100);
        }
        return $price;
    }
}