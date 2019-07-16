<?php
class Progos_SmsaExpress_Helper_Data extends  Mage_Core_Helper_Abstract
{
    public function getApiKey(){
      	$key = "";
      	if( Mage::getStoreConfig('carriers/smsaexpress/test_mode') == '1' ){
      		$key = Mage::getStoreConfig('carriers/smsaexpress/test_key');
      		if( $key == "" )
      			$key = "Testing0";
      	}else{
      		$key = Mage::getStoreConfig('carriers/smsaexpress/api_key');
      	}
      	return $key;
    }

      public function getApiUrl(){
  		$apiUrl = Mage::getStoreConfig('carriers/smsaexpress/api_url');
  		if( $apiUrl == "" ){
  			$apiUrl = "http://track.smsaexpress.com/SECOM/SMSAwebService.asmx?wsdl";
  		}
  		return $apiUrl;
      }

      public function getStatus(){
      	if( Mage::getStoreConfig('carriers/smsaexpress/status') == '1' )
      		return true;
      	else
      		return false;
      }

      public function getMethod(){
      	return Mage::getStoreConfig('carriers/smsaexpress/method_type');
      }
}