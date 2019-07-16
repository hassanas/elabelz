<?php
class Aramex_Shipment_RateController extends Mage_Adminhtml_Controller_Action {	
	protected function _isAllowed()
	{
			return true;
	}
	public function postAction(){	
                $post = $this->getRequest()->getPost();
                $storeId = (int)$post['store_id'];
		$account = Mage::getStoreConfig('aramexsettings/settings/account_number', $storeId);		
		$country_code = Mage::getStoreConfig('aramexsettings/settings/account_country_code', $storeId);
		
		$response=array();
				
		$clientInfo = Mage::helper('aramexshipment')->getClientInfo($storeId);		
		try {
			 $country = Mage::getModel('directory/country')->loadByCode($country_code);	
				if (empty($post)) {					
					$response['type']='error';
					$response['error']=$this->__('Invalid form data.');
					print json_encode($response);		
					die();
				}
                if($post['service_type'] == "CDA"){ $aramex_services = "CODS"; }else{ $aramex_services = ""; }
		$params = array(
		'ClientInfo'  			=> $clientInfo,
								
		'Transaction' 			=> array(
									'Reference1'			=> $post['reference'] 
								),
								
		'OriginAddress' 	 	=> array(
									'StateOrProvinceCode'	=>html_entity_decode($post['origin_state']),
									'City'					=> html_entity_decode($post['origin_city']),
									'PostCode'				=>$post['origin_zipcode'],
									'CountryCode'				=> $post['origin_country']
								),
								
		'DestinationAddress' 	=> array(
									'StateOrProvinceCode'	=>html_entity_decode($post['destination_state']),
									'City'					=> html_entity_decode($post['destination_city']),
									'PostCode'				=>$post['destination_zipcode'],
									'CountryCode'			=> $post['destination_country'],
								),
		'ShipmentDetails'		=> array(
									'PaymentType'			 => $post['payment_type'],
									'ProductGroup'			 => $post['product_group'],
									'ProductType'			 => $post['service_type'],
                                                                        'Services'                       => $aramex_services,
									'ActualWeight' 			 => array('Value' => $post['text_weight'], 'Unit' => $post['weight_unit']),
									'ChargeableWeight' 	     => array('Value' => $post['text_weight'], 'Unit' => $post['weight_unit']),
									'NumberOfPieces'		 => $post['total_count']
								),
                'PreferredCurrencyCode' => $post['currency_code']
	);
	
	$baseUrl = Mage::helper('aramexshipment')->getWsdlPath($storeId);
	$soapClient = new SoapClient($baseUrl.'aramex-rates-calculator-wsdl.wsdl', array('trace' => 1, ));
	try{
	$results = $soapClient->CalculateRate($params);	
	
	if($results->HasErrors){
		if(count($results->Notifications->Notification) > 1){
			$error="";
			foreach($results->Notifications->Notification as $notify_error){
				$error.=$this->__('Aramex: ' . $notify_error->Code .' - '. $notify_error->Message)."<br>";				
			}
			$response['error']=$error;
		}else{
			$response['error']=$this->__('Aramex: ' . $results->Notifications->Notification->Code . ' - '. $results->Notifications->Notification->Message);
		}
		$response['type']='error';
	}else{
		$response['type']='success';
		$amount="<p class='amount'>".$results->TotalAmount->Value." ".$results->TotalAmount->CurrencyCode."</p>";
		$text="Local taxes - if any - are not included. Rate is based on account number $account in ".$country->getName();
		$response['html']=$amount.$text;		
		
	}
	} catch (Exception $e) {
			$response['type']='error';
			$response['error']=$e->getMessage();			
	}
	}
	catch (Exception $e) {
			$response['type']='error';
			$response['error']=$e->getMessage();			
	}
	print json_encode($response);		
	die();
	 }
	
}