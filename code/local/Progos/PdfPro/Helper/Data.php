<?php
/**
 * @category Progos_PdfPro
 * @package Progos
 * @author Saroop Chand <saroop.chand@progos.org>
 */
class Progos_PdfPro_Helper_Data extends VES_PdfPro_Helper_Data
{

    public function convertCurrencyDestination( $price ,$currency , $destinationArray , $full = false ){

        $baseCode = Mage::app()->getBaseCurrencyCode();
        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCode, array_values($allowedCurrencies));
        foreach($rates as $rate=>$value):
            if($rate == $destinationArray['currencycode']):
                $value_rate = $value;
            endif;
        endforeach;
        $price = $price * $value_rate;
        $price = number_format($price, 2, '.', '');
        if( $full == true )
            $price = $destinationArray['currency']." ".$price;

        return $price;
    }


//    public function currency($price, $currency){
    public function currency($price, $currency = 'USD', $baseCode=null){
        $price = number_format($price, 2, '.', '');
        $price = $currency." ".$price;
        return $price;
    }

    public function getDestCurDetail( $store_code , $destination ){
        $result = array();
        if( strpos($store_code, 'ar') !== false ){
            if($destination == "EG"){
                $destination = "qa";
            }
            elseif($destination == "GB"){
                $destination = "uk";
            }
            elseif($destination != "AE" && $destination != "SA" && $destination != "KW" && $destination != "EG" && $destination != "GB"){
                $destination ="iq";
            }
            $store_code = strtolower('ar_'.$destination );
            $result['currencycode'] = Mage::app()->getStore($store_code)->getCurrentCurrencyCode();

            if( $store_code == "ar_sa" )
                $result['currency'] = "ر.س";
            else if( $store_code == "ar_ae" )
                $result['currency'] = "د.إ";
            else if( $store_code == "ar_kw" )
                $result['currency'] = "د.ك";
            else if( $store_code == "ar_qa")
                $result['currency'] = "ج.م";
            else if($store_code == "ar_eg")
                $result['currency'] = "£";
            else
                $result['currency'] = "$";
        }else{
           if($destination == "EG"){
                $destination = "qa";
            }
            elseif($destination == "GB"){
                $destination = "uk";
            }
            elseif($destination != "AE" && $destination != "SA" && $destination != "KW" && $destination != "EG" && $destination != "GB"){
                $destination ="us";
            }
            $store_code = strtolower( 'en_'.$destination );
            $result['currency'] = Mage::app()->getStore( $store_code )->getCurrentCurrencyCode();
            $result['currencycode'] =  Mage::app()->getStore( $store_code )->getCurrentCurrencyCode();
        }
        return $result;
    }
}
