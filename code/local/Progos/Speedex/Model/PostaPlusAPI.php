<?php

class Progos_Speedex_Model_PostaPlusAPI
{
        public    $_SOAP_URL; 
        public    $_LOG_FILE= 'speedex_error.log';
        
	protected $_debug = false;	
 	protected $_login_credentioals = array();
	protected $_client_info_tag = null;
	protected $_cash_on_delivary_amount = 0.00;
	protected $_cash_on_delivary_currency = null;
	protected $_items_details =array();
	protected $_code_currency = null;
	protected $_code_service = null;
	protected $_available_service_codes = array(
			'SRV6'=>'DOMESTIC STANDARD',
			'SRV1'=>'DOMESTIC EXPRESS',
			'SRV3' =>'INTERNATIONAL');
	protected $_shipment_type = null;
	protected $_available_shipment_types = array(
			'SHPT2'=>'NON DOC',
			'SHPT1'=>'DOC');
	protected $_consignment_data_elements = array();
	protected $_connonte_contact_elements = array();
	protected $_connote_description = null;
	protected $_is_connote_insured = null;
	protected $_notes_elements = null;
	protected $_performa_invoice_elements = null;
	protected $_connote_pieces = 1;
	protected $_is_connote_prohibited = null;
	protected $_references_elements = null;
	protected $_cost_of_shipment_amount = 0.00;
	protected $_is_pickup_needed = null;
	protected $_is_round_trip_needed = null;
	protected $trimArray = "TrimArray";

	/**
	 * Construct
	 */
	public function __construct($wsdl,$credentials){
            $this->setSOAP_URL($wsdl);
            $this->setLoginCredentials($credentials);
        }

	// SETTERS

	/**
	 * Set SOAP URL
	 * @param string $url
	 */
	protected function setSOAP_URL($url)
	{
		$this->_SOAP_URL = $url;
	}
        
	/**
	 * Set login credentials array
	 * @param array $credentials
	 */
	protected function setLoginCredentials($credentials)
	{
            $this->_login_credentioals['CODE_STATION'] = (!empty($credentials['CODE_STATION']))?$credentials['CODE_STATION']:'null';
            $this->_login_credentioals['ACCOUNT_ID'] = (!empty($credentials['ACCOUNT_ID']))?$credentials['ACCOUNT_ID']:'null';
            $this->_login_credentioals['USER_NAME'] = (!empty($credentials['USER_NAME']))?$credentials['USER_NAME']:'null';
            $this->_login_credentioals['PASSWORD'] = (!empty($credentials['PASSWORD']))?$credentials['PASSWORD']:'null';
	}

	/**
	 * Set cash on delivery amount
	 * @param real $COD_Amount
	 */
	public function setCashOnDeliveryAmount($COD_Amount = 0.00)
	{
		$this->_cash_on_delivary_amount = $COD_Amount;
	}

	/**
	 * Set cash on delivery currency
	 * @param string $COD_Currency
	 */
	public function setCashOnDeliveryCurrency($COD_Currency = 'USD')
	{
		$this->_cash_on_delivary_currency = $COD_Currency;
	}

	/**
	 * Set Items details array
	 * @param array $items
	 */
	public function setItemsDetailsElement($items)
	{
		$i=0;
		foreach($items as $item){
			$this->_items_details['ITEMDETAILS'][$i]['ConnoteHeight'] = (isset($item['HEIGHT']))?$item['HEIGHT']:0;
			$this->_items_details['ITEMDETAILS'][$i]['ConnoteLength'] = (isset($item['LENGTH']))?$item['LENGTH']:0;
			$this->_items_details['ITEMDETAILS'][$i]['ConnoteWidth'] = (isset($item['WIDTH']))?$item['WIDTH']:0;
			$this->_items_details['ITEMDETAILS'][$i]['ScaleWeight'] = (isset($item['SCALE_WEIGHT']))?$item['SCALE_WEIGHT']:0;
			$this->_items_details['ITEMDETAILS'][$i]['ConnoteWeight'] = ((float)$item['WEIGHT']==0)?0.5:(float)$item['WEIGHT'];
			$i++;
		}
		return $this->_items_details;
	}

	/**
	 * Set currency code
	 * @param string $code_currency
	 */
	public function setCodeCurrency($code_currency = 'USD')
	{
		$this->_code_currency = $code_currency;
	}

	/**
	 * Set service code
	 * @param string $service_code
	 */
	public function setCodeService($service_code)
	{
		$this->_code_service = $service_code;
	}

	/**
	 * Set shipment type
	 * @param string $shipment_type
	 */
	public function setShipmentType($shipment_type = 'SHPT2')
	{
		$this->_shipment_type = $shipment_type;
	}

	/**
	 * Set consignment element
	 * @param array $consignment_array
	 */
	public function setConsigneeElements($consignment_array)
	{
		$consignment_elements = array(
				'Company'=>$consignment_array['RECEIVER']['COMPANY_NAME'],
				'FromAddress'=>$consignment_array['SHIPPER']['FULL_ADDRESS'],
				'FromArea'=>$consignment_array['SHIPPER']['FROM_AREA'],
				'FromCity'=>$consignment_array['SHIPPER']['FROM_CITY'],
				'FromCodeCountry'=>$consignment_array['SHIPPER']['COUNTRY_CODE'],
				'FromMobile'=>$consignment_array['SHIPPER']['PHONE_NO'],
				'FromName'=>$consignment_array['SHIPPER']['SHIPPER_NAME'],
				'FromPinCode'=>$consignment_array['SHIPPER']['PIN_CODE'],
				'FromProvince'=>$consignment_array['SHIPPER']['FROM_PROVINCE'],
				'FromTelphone'=>$consignment_array['SHIPPER']['FROM_TELEPHONE'],
				'Remarks'=>$consignment_array['SHIPPER']['REMARKS'],
				'ToAddress'=>$consignment_array['RECEIVER']['TO_ADDRESS'],
				'ToArea'=>$consignment_array['RECEIVER']['TO_AREA'],
				'ToCity'=>$consignment_array['RECEIVER']['TO_CITY'],
				'ToCivilID'=>$consignment_array['RECEIVER']['TO_CIVIL_ID'],
				'ToCodeCountry'=>$consignment_array['RECEIVER']['TO_CODE_COUNTRY'],
				'ToCodeSector'=>$consignment_array['RECEIVER']['TO_CODE_SECTOR'],
				'ToDesignation'=>$consignment_array['RECEIVER']['TO_DESIGNATION'],
				'ToMobile'=>$consignment_array['RECEIVER']['TO_MOBILE'],
				'ToName'=>$consignment_array['RECEIVER']['TO_NAME'],
				'ToTelPhone'=>$consignment_array['RECEIVER']['TO_TELEPHONE'],
				'ToPinCode'=>$consignment_array['RECEIVER']['TO_PIN_CODE'],
				'ToProvince'=>$consignment_array['RECEIVER']['TO_PROVINCE_CODE']
		);

		$this->_consignment_data_elements = $consignment_elements;
	}

	/**
	 * Set connote contact element
	 * @param array $connote_contacts
	 */
	public function setConnoteContactElements($connote_contacts)
	{
		$this->_connonte_contact_elements = array(
				'Email1'=>$connote_contacts['SHIPPER']['EMAIL'],
				'Email2'=>$connote_contacts['RECEIVER']['EMAIL'],
				'TelHome'=>$connote_contacts['RECEIVER']['TELEPHONE'],
				'TelMobile'=>$connote_contacts['RECEIVER']['MOBILE'],
				'WhatsAppNumber'=>$connote_contacts['RECEIVER']['WHATSAPP']
		);
	}

	/**
	 * Set connote description
	 * @param string $connote_description
	 */
	public function setConnoteDescription($connote_description)
	{
                $this->_connote_description = $connote_description;

	}

	/**
	 * Set connote insured details
	 * @param string $connote_insured
	 */
	public function setIsConnoteInsured($connote_insured = 'N')
	{
		$this->_is_connote_insured = $connote_insured;
	}

	/**
	 * Set connote note element
	 * @param array $notes_array
	 */
	public function setConnoteNotesElements($notes_array)
	{
		$notes_elements = array(
				'Note1'=>(isset($notes_array['NOTE1']))?$notes_array['NOTE1']:null,
				'Note2'=>(isset($notes_array['NOTE2']))?$notes_array['NOTE2']:null,
				'Note3'=>(isset($notes_array['NOTE3']))?$notes_array['NOTE3']:null,
				'Note4'=>(isset($notes_array['NOTE4']))?$notes_array['NOTE4']:null,
				'Note5'=>(isset($notes_array['NOTE5']))?$notes_array['NOTE5']:null,
				'Note6'=>(isset($notes_array['NOTE6']))?$notes_array['NOTE6']:null
		);

		$this->_notes_elements = $notes_elements;
	}

	/**
	 * Set performa invoce element
	 * @param array $performa_invoice_array
	 */
	public function setPerformaInvoiceElement($performa_invoice_array){

		if($this->getCodeService() == 'SRV3'){
			$i=0;
			foreach($performa_invoice_array as $item){
					
				$performa_invoice['CONNOTEPERMINV'][$i] = array(
						'CodeHS'=>$item['CODE_HS'],
						'CodePackageType'=>$item['CODE_PACKAGE_TYPE'],
						'Description'=>$item['DESCRIPTION'],
						'OrginCountry'=>$item['ORIGIN_COUNTRY_CODE'],
						'Quantity'=>$item['QUANTITY'],
						'RateUnit'=>$item['UNIT_RATE']
				);
				$i++;
			}
			$this->_performa_invoice_elements = $performa_invoice;
		}
	}

	/**
	 * Set connote pieces
	 * @param number $connote_pieces
	 */
	public function setConnotePieces($connote_pieces = 1)
	{
		$this->_connote_pieces = $connote_pieces;
	}

	/**
	 * Set connote prohibited details
	 * @param string $is_connote_prohibited
	 */
	public function setIsConnoteProhibited($is_connote_prohibited = 'N')
	{
		$this->_is_connote_prohibited = $is_connote_prohibited;
	}

	/**
	 * Set references elements
	 * @param array $references_array
	 */
	public function setReferencesElements($references_array)
	{
		$references = array(
				'Reference1'=>(isset($references_array['REFERENCE1']))?$references_array['REFERENCE1']:null,
				'Reference2'=>(isset($references_array['REFERENCE2']))?$references_array['REFERENCE2']:null,
		);
		$this->_references_elements = $references;
	}

	/**
	 * Set cost of shipment amount
	 * @param real $cost_of_shipment_amount
	 */
	public function setCostOfShipmentAmount($cost_of_shipment_amount = 0.00)
	{
		$this->_cost_of_shipment_amount = $cost_of_shipment_amount;
	}

	/**
	 * Set pickup needed value
	 * @param string $is_pickup_needed
	 */
	public function setIsPickupNeeded($is_pickup_needed = 'N')
	{
		$this->_is_pickup_needed = $is_pickup_needed;
	}

	/**
	 * Set Is round trip needed value
	 * @param string $is_round_trip_needed
	 */
	public function setIsRoundTripNeeded($is_round_trip_needed = 'N')
	{
		$this->_is_round_trip_needed = $is_round_trip_needed;
	}

	//GETTERS

	/**
	 * Get login credentials
	 * @return array
	 */
	protected function getLoginCredentials(){
		return $this->_login_credentioals;
	}

	/**
	 * Get client information element
	 * @return array
	 */
	protected function getClientInfoElement(){

		$credentials = $this->getLoginCredentials();
		$clientInfoElement = array(
				'CodeStation'=>$credentials['CODE_STATION'],
				'Password'=>$credentials['PASSWORD'],
				'ShipperAccount'=>$credentials['ACCOUNT_ID'],
				'UserName'=>$credentials['USER_NAME']
		);
		return $clientInfoElement;
	}

	/**
	 * Get cash on delivery amount
	 * @return number|real
	 */
	protected function getCashOnDeliveryAmount()
	{
		return $this->_cash_on_delivary_amount;
	}

	/**
	 * Get cash on delivery currency
	 * @return string
	 */
	protected function getCashOnDeliveryCurrency()
	{
		return $this->_cash_on_delivary_currency;
	}

	/**
	 * Get service code
	 * @return string
	 */
	protected function getCodeService(){
		return $this->_code_service;
	}

	/**
	 * Get Items details element
	 * @return strign
	 */
	protected function getItemsDetailsElement()
	{
		return $this->_items_details;
	}

	/**
	 * Get currency code
	 * @return string
	 */
	protected function getCodeCurrency(){

		return $this->_code_currency;
	}

	/**
	 * Get shipment type
	 * @return string
	 */
	protected function getShipmentType()
	{
		return $this->_shipment_type;
	}

	/**
	 * Get consignment element
	 * @return array[]
	 */
	protected function getConsignmentElements()
	{
		return $this->_consignment_data_elements;
	}

	/**
	 * Get connote contact element
	 * @return array[]
	 */
	protected function getConnoteContactElements()
	{
		return $this->_connonte_contact_elements;
	}

	/**
	 * Get connote description
	 * @return string
	 */
	protected function getConnoteDescription()
	{
		return $this->_connote_description;
	}

	/**
	 * Get is connote insured value
	 * @return string
	 */
	protected function getIsConnoteInsured()
	{
		return $this->_is_connote_insured;
	}

	/**
	 * Get connote notes element
	 * @return array
	 */
	protected function getConnoteNotesElements()
	{
		return $this->_notes_elements;
	}

	/**
	 * Get performa Invoice element
	 * @return array
	 */
	protected function getPerformaInvoiceElement(){

		return $this->_performa_invoice_elements;
	}

	/**
	 * Get connote pieces
	 * @return number
	 */
	protected function getConnotePieces()
	{
		return $this->_connote_pieces;
	}

	/**
	 * Get is connote prohibited value
	 * @return string
	 */
	protected function getIsConnoteProhibited()
	{
		return $this->_is_connote_prohibited;
	}

	/**
	 * Get references elements
	 * @return array
	 */
	protected function getReferencesElements()
	{
		return $this->_references_elements;
	}

	/**
	 * Get cost of shipment amount
	 * @return number|real
	 */
	protected function getCostOfShipmentAmount()
	{
		return $this->_cost_of_shipment_amount;
	}

	/**
	 * Get is pickup needed value
	 * @return string
	 */
	protected function getIsPickupNeeded()
	{
		return $this->_is_pickup_needed;
	}

	/**
	 * Get is round trip needed value
	 * @return string
	 */
	protected function getIsRoundTripNeeded()
	{
		return $this->_is_round_trip_needed;
	}

	/**
	 * Get SOAP URL
	 * @return string
	 */
	protected function getSOAP_URL()
	{
		return $this->_SOAP_URL;
	}

	/**
	 * Clear string from special chars
	 * @param unknown $string
	 * @return unknown
	 */
	private function cleanStr($string) 
        {
		return preg_replace('/[^\w\s\-,.]+/u', ' ', $string); // Removes special chars.                
	}

	/**
	 * Prepare SOAP request array
	 * @return array
	 */
	public function prepare_SOAP_request_parem_array()
        {

		$SOAP_request_array =
		array('SHIPINFO'=>
				array(
						'ClientInfo'=>$this->getClientInfoElement(),
						'CashOnDelivery' => $this->getCashOnDeliveryAmount(),
						'CashOnDeliveryCurrency'=>$this->getCashOnDeliveryCurrency(),
						'ItemDetails'=> $this->getItemsDetailsElement(),
						'CodeCurrency'=> $this->getCodeCurrency(),
						'CodeService'=>$this->getCodeService(),
						'CodeShippmentType'=>$this->getShipmentType(),
						'Consignee'=>$this->getConsignmentElements(),
						'ConnoteContact'=>$this->getConnoteContactElements(),
						'ConnoteDescription'=>$this->getConnoteDescription(),
						'ConnoteInsured'=>$this->getIsConnoteInsured(),
						'ConnoteNotes'=>$this->getConnoteNotesElements(),
						'ConnotePieces'=>$this->getConnotePieces(),
						'ConnoteProhibited'=>$this->getIsConnoteProhibited(),
						'ConnoteRef'=>$this->getReferencesElements(),
						'CostShipment'=>$this->getCostOfShipmentAmount(),
						'NeedPickUp'=>$this->getIsPickupNeeded(),
						'NeedRoundTrip'=>$this->getIsRoundTripNeeded()
				)
		);

		if($this->getCodeService() == 'SRV3'){
			$SOAP_request_array['SHIPINFO']['ConnotePerformaInvoice'] = $this->getPerformaInvoiceElement();
		}
		$clean_SOAP_request_array = $this->TrimArray($SOAP_request_array);
                               
                //var_dump($clean_SOAP_request_array);exit;
		return $clean_SOAP_request_array;

	}//end of function

	/**
	 * Trim and clean array elements
	 *
	 * @param Associated $arr
	 * @return Associated array
	 */
	function TrimArray($arr)
        {
		if (!is_array($arr)){ return $arr; }

		while (list($key, $value) = each($arr)){
				
			$dirty_value = $value;
			$clen_value = null;
				
			if (is_array($dirty_value)){
				$clen_value = $this->TrimArray($dirty_value);
				$arr[$key] = $clen_value;
			}
			else {
				//trim
				$clen_value = trim($value);

				if($key != 'Email1' && $key != 'Email2' ){
						
					//remove special chars exept hexa values
					//$clen_value = preg_replace('/[^A-Za-z0-9\-,.]/', ' ', $clen_value);
                                        $clen_value = preg_replace('/[^\w\s\-,.]+/u','' ,$clen_value);
				}
				$arr[$key] = $clen_value;
			}
		}
                
		return $arr;
	}

	/**
	 * Create SOAP PostaPlus shipment
	 * @param SOAP array $params
	 * @return string
	 */
	public function create_shipment($params,$debug = false)
        {
            
                $request = null;
                $response = null;
                $return_string = null;
                $xml_data = null;
                
                $client = $this->create_SOAP_client();
		
		try {
                    $soapResponse = $client->Shipment_Creation($params);
                    $request = $client->__getLastRequest();
                    $response  = $client->__getLastResponse();
                    $return_string = $soapResponse->Shipment_CreationResult;
                    
                    // IF RETURN DATA IS NOT A NUMERIC VALUE (AWB NO ALWAYS BE NUMERIC) THEN ITS AN ERROR
                    if(!is_numeric($return_string)){
                        
                        $data=array(
                        'params'=>$params,
                        'function'=>"create_shipment",
                        'return_data'=>$return_string
                        );
                        $this->log_entry($data);                                             

                    }
                    
		} catch (SoapFault $fault) {
                    
                    $data=array(
                        'params'=>$params,
                        'function'=>"create_shipment",
                        'return_data'=>$fault->faultstring
                        );
                        $this->log_entry($data);                                              

                    $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
		}
                
                if($debug){
                    
                    $xml_data .= '<br><br>Request XML<br>';
                    $xml_data .= '<textarea cols="50" rows="10">';
                    $xml_data .= $request;
                    $xml_data .= '</textarea><br>';                    
                    $xml_data .= '<br>Response XML<br>';
                    $xml_data .= '<textarea cols="50" rows="10">';
                    $xml_data .= $response;
                    $xml_data .= '</textarea><br>';                    
                    $return_string = $return_string . $xml_data;                    
                }
		return $return_string;
	}
        
        /**
         * Create special special shipments without HSCODES for international shipments 
         * @param array $params
         * @param bool $debug
         * @return string AWB number on success or error on fail 
         */
        public function create_special_shipment($params,$debug = false)	
        {
            
                $request = null;
                $response = null;
                $return_string = null;
                $xml_data = null;
                
                $client = $this->create_SOAP_client();
		
		try {
                    
                    $soapResponse = $client->Special_Shipment_Package($params);
                    $request = $client->__getLastRequest();
                    $response  = $client->__getLastResponse();
                    $return_string = $soapResponse->Special_Shipment_PackageResult;
                    
                    // IF RETURN DATA IS NOT A NUMERIC VALUE (AWB NO ALWAYS BE NUMERIC) THEN ITS AN ERROR
                    if(!is_numeric($return_string)){
                        
                        $data=array(
                        'params'=>$params,
                        'function'=>"create_special_shipment",
                        'return_data'=>$return_string
                        );
                        $this->log_entry($data);                                              
                    }
                    
		} catch (SoapFault $fault) {                           
                    $data=array(
                    'params'=>$params,
                    'function'=>"create_special_shipment",
                    'return_data'=>$fault->faultstring
                    );
                    $this->log_entry($data);                                              
                    
                    $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
		}
                
                if($debug){
                    
                    $xml_data .= '<br><br>Request XML<br>';
                    $xml_data .= '<textarea cols="50" rows="10">';
                    $xml_data .= $request;
                    $xml_data .= '</textarea><br>';                    
                    $xml_data .= '<br>Response XML<br>';
                    $xml_data .= '<textarea cols="50" rows="10">';
                    $xml_data .= $response;
                    $xml_data .= '</textarea><br>';                    
                    $return_string = $return_string . $xml_data;                    
                }
		return $return_string;
	}
        
        /**
         * Shipment tracing details for a given AWB no
         * @param array $params
         * @param bool $debug
         * @return Object
         */
        public function shipment_tracking($params,$debug){
            
            $request = null;
            $response = null;
            $return_string = null;
            $xml_data = null;
            $params = array(
                "CodeStation" => $this->_login_credentioals['CODE_STATION'],
                "UserName" => $this->_login_credentioals['USER_NAME'],
                "Password" => $this->_login_credentioals['PASSWORD'],
                "ShipperAccount" => $this->_login_credentioals['ACCOUNT_ID'],
                "AirwaybillNumber" => (!empty($params['awbno']))?$params['awbno']:"",
                "Reference1" => (!empty($params['ref1']))?$params['ref1']:"",
                "Reference2" => (!empty($params['ref2']))?$params['ref2']:"",
            );
            try{
                $client = $this->create_SOAP_client();
                $soapResponse = $client->Shipment_Tracking($params);
                $request = $client->__getLastRequest();
                $response  = $client->__getLastResponse();
                $return_string = $soapResponse->Shipment_TrackingResult;
                //var_dump($return_string);exit;
                
                // IF ANY ERROR RETURNS
                if(empty($return_string->TRACKSHIPMENT->Event)){                    
                    $data=array(
                    'params'=>$params,
                    'function'=>"shipment_tracking",
                    'return_data'=>$return_string
                    );
                    $this->log_entry($data);                                              
                }
                
            }catch(SoapFault $fault){
                
                $data=array(
                'params'=>$params,
                'function'=>"shipment_tracking",
                'return_data'=>$fault->faultstring
                );
                $this->log_entry($data);                                              
                $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
            }
            if($debug){                    
                $xml_data .= 'Tracking Request XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $request;
                $xml_data .= '</textarea><br>';

                $xml_data .= '<br>Tracking Response XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $response;
                $xml_data .= '</textarea><br>';                                                     
                echo $xml_data;
            }            
            return $return_string;		
        }
        
        /**
         * Voids a provided shipment AWB number
         * @param array $params
         * @param bool $debug
         * @return string
         */
        public function void_shipment($params,$debug)
        {
            
            $request = null;
            $response = null;
            $return_string = null;
            $xml_data = null;
            $params = array("CLIENTINFO"=>array(
                    "CodeStation" => $this->_login_credentioals['CODE_STATION'],
                    "UserName" => $this->_login_credentioals['USER_NAME'],
                    "Password" => $this->_login_credentioals['PASSWORD'],
                    "ShipperAccount" => $this->_login_credentioals['ACCOUNT_ID'],
                ),
                "VOID"=>array(
                    "Connote" => (!empty($params['AWBNO']))?$params['AWBNO']:"",
                    "Reason" => (!empty($params['Reason']))?$params['Reason']:"",
                )
            );
            
            try{
                $client = $this->create_SOAP_client();
                $soapResponse = $client->ShipmentVoid($params);
                $request = $client->__getLastRequest();
                $response  = $client->__getLastResponse();                
                $return_string = $soapResponse->ShipmentVoidResult;     
                
            }catch(SoapFault $fault){
                
                $data=array(
                'params'=>$params,
                'function'=>"void_shipment",
                'return_data'=>$fault->faultstring
                );
                $this->log_entry($data);                              
                $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
            }
            
            if($debug){                    
                $xml_data .= 'Shipment Void Request XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $request;
                $xml_data .= '</textarea><br>';

                $xml_data .= '<br>Shipment Void Response XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $response;
                $xml_data .= '</textarea><br>';                                                     
                echo $xml_data;
            }            
            return $return_string;		
        }
        
        /**
         * Creates pickup entry in PostaPlus
         * @param array $params
         * @param bool $debug
         * @return object
         */
        public function create_pickup($params,$debug)
        {
            
            $request = null;
            $response = null;
            $return_string = null;
            $xml_data = null;
            $params = array(
                "PICKUPINFO"=>array(
                    "ClientInfo"=>array(
                        "CodeStation" => $this->_login_credentioals['CODE_STATION'],
                        "UserName" => $this->_login_credentioals['USER_NAME'],
                        "Password" => $this->_login_credentioals['PASSWORD'],
                        "ShipperAccount" => $this->_login_credentioals['ACCOUNT_ID'],     
                    ),
                    "CodeService"=>(!empty($params["CodeService"]))?$params["CodeService"]:"",            
                    "ContactPerson"=>(!empty($params["ContactPerson"]))?$params["ContactPerson"]:"",            
                    "DeliverContactPerson"=>(!empty($params["DeliverContactPerson"]))?$params["DeliverContactPerson"]:"",            
                    "DeliveryAddress"=>(!empty($params["DeliveryAddress"]))?$params["DeliveryAddress"]:"",            
                    "DeliveryCity"=>(!empty($params["DeliveryCity"]))?$params["DeliveryCity"]:"",            
                    "DeliveryPhone"=>(!empty($params["DeliveryPhone"]))?$params["DeliveryPhone"]:"",            
                    "GoodsType"=>(!empty($params["GoodsType"]))?$params["GoodsType"]:"",            
                    "Notes"=>(!empty($params["Notes"]))?$params["Notes"]:"",            
                    "PickAddress"=>(!empty($params["PickAddress"]))?$params["PickAddress"]:"",            
                    "PickCity"=>(!empty($params["PickCity"]))?$params["PickCity"]:"",            
                    "PickDate"=>(!empty($params["PickDate"]))?$params["PickDate"]:"",            
                    "PickPhone"=>(!empty($params["PickPhone"]))?$params["PickPhone"]:"",            
                    "RequestedPerson"=>(!empty($params["RequestedPerson"]))?$params["RequestedPerson"]:"",            
                    "TimeEnd"=>(!empty($params["TimeEnd"]))?$params["TimeEnd"]:"",            
                    "TimeOffice"=>(!empty($params["TimeOffice"]))?$params["TimeOffice"]:"",            
                    "TimeStart"=>(!empty($params["TimeStart"]))?$params["TimeStart"]:"",            
            ));
            //var_dump($params);exit;
            try{
                $client = $this->create_SOAP_client();
                $soapResponse = $client->Pickup_Creation($params);
                $request = $client->__getLastRequest();
                $response  = $client->__getLastResponse();                                
                $return_string = $soapResponse->Pickup_CreationResult;     
                
                if($return_string->Response == "FAILED"){                    
                    $data=array(
                    'params'=>$params,
                    'function'=>"create_pickup",
                    'return_data'=>$return_string
                    );
                    $this->log_entry($data);              
                }
            }catch(SoapFault $fault){ 
                
                $data=array(
                    'params'=>$params,
                    'function'=>"create_pickup",
                    'return_data'=>$fault->faultstring
                    );
                $this->log_entry($data);                              
                $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
            }
            
            if($debug){                    
                $xml_data .= 'Pickup Creation Request XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $request;
                $xml_data .= '</textarea><br>';

                $xml_data .= '<br>Pickup Creation Response XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $response;
                $xml_data .= '</textarea><br>';                                                     
                echo $xml_data;
            }            
            return $return_string;		
        }
        
        /**
         * Cancels submitted pickup
         * @param array $params
         * @param bool $debug
         * @return string on success object on error
         */
        public function cancel_pickup($params,$debug)
        {
            $request = null;
            $response = null;
            $return_string = null;
            $xml_data = null;
            $params = array(
                "CLIENTINFO"=>array(
                    "CodeStation" => $this->_login_credentioals['CODE_STATION'],
                    "UserName" => $this->_login_credentioals['USER_NAME'],
                    "Password" => $this->_login_credentioals['PASSWORD'],
                    "ShipperAccount" => $this->_login_credentioals['ACCOUNT_ID'],
                ),                
                "PickupNumber" => (!empty($params['PickupNumber']))?$params['PickupNumber']:"",
                "Reason" => (!empty($params['Reason']))?$params['Reason']:"",                
            );
            
            try{
                $client = $this->create_SOAP_client();
                $soapResponse = $client->Pickup_Cancel($params);
                $request = $client->__getLastRequest();
                $response  = $client->__getLastResponse();                
                $return_string = $soapResponse->Pickup_CancelResult;
                
                if($return_string->Response == "FAILED"){
                    $data=array(
                    'params'=>$params,
                    'function'=>"cancel_pickup",
                    'return_data'=>$return_string
                    );
                    $this->log_entry($data);                                  
                }
                
            }catch(SoapFault $fault){  
                $data=array(
                    'params'=>$params,
                    'function'=>"cancel_pickup",
                    'return_data'=>$fault->faultstring
                    );
                $this->log_entry($data);              
                $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
            }
            
            if($debug){                    
                $xml_data .= 'Pickup cancel Request XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $request;
                $xml_data .= '</textarea><br>';

                $xml_data .= '<br>Pickup cancel Response XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $response;
                $xml_data .= '</textarea><br>';                                                     
                echo $xml_data;
            }            
            return $return_string;		

        }
        
        /**
         * Calculates shipping cost based on 
         * @param array $params
         * @param bool $debug
         * @return object
         */
        public function calculate_shipping_cost($params,$debug=false)
        {
            $request = null;
            $response = null;
            $return_string = null;
            $xml_data = null;
            $params = array(
                "CI"=>array(
                    "CodeStation" => $this->_login_credentioals['CODE_STATION'],
                    "UserName" => $this->_login_credentioals['USER_NAME'],
                    "Password" => $this->_login_credentioals['PASSWORD'],
                    "ShipperAccount" => $this->_login_credentioals['ACCOUNT_ID'],
                ),                
                "ScaleWeight" => (!empty($params['ScaleWeight']))?$params['ScaleWeight']:"",
                "Length" => (!empty($params['Length']))?$params['Length']:"",                
                "Height" => (!empty($params['Height']))?$params['Height']:"",                
                "Width" => (!empty($params['Width']))?$params['Width']:"",                
                "OriginCountryCode" => (!empty($params['OriginCountryCode']))?$params['OriginCountryCode']:"",                
                "DestinationCountryCode" => (!empty($params['DestinationCountryCode']))?$params['DestinationCountryCode']:""                                
            );
            
            try{
                $client = $this->create_SOAP_client();
                $soapResponse = $client->ShipmentCostCalculation($params);
                $request = $client->__getLastRequest();
                $response  = $client->__getLastResponse();                
                $return_string = $soapResponse->ShipmentCostCalculationResult;                
                
                if($return_string->Response == "FALSE RESPONSE"){
                    $data=array(
                    'params'=>$params,
                    'function'=>"calculate_shipping_cost",
                    'return_data'=>$return_string
                    );
                    $this->log_entry($data);                                  
                }
                
            }catch(SoapFault $fault){  
                $data=array(
                    'params'=>$params,
                    'function'=>"calculate_shipping_cost",
                    'return_data'=>$fault->faultstring
                    );
                $this->log_entry($data);              
                $return_string = "SOAP Fault: faultcode: {$fault->faultcode} - Fault string: {$fault->faultstring}";//, E_USER_ERROR);                    
            }
            
            if($debug){                    
                $xml_data .= 'Shipment Void Request XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $request;
                $xml_data .= '</textarea><br>';

                $xml_data .= '<br>Shipment Void Response XML<br>';
                $xml_data .= '<textarea cols="50" rows="10">';
                $xml_data .= $response;
                $xml_data .= '</textarea><br>';                                                     
                echo $xml_data;
            }            
            return $return_string;		

        }
        
        /**
         * Creates SOAP client object
         * @return \SoapClient
         */
        protected function create_SOAP_client()
        {
            $context = stream_context_create(
                            array(
                                'ssl' => array(
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true
                                ),
                                'http'=>array(
                                        'user_agent' => 'PHPSoapClient'
                                )
                            ));

            $client = new SoapClient($this->getSOAP_URL(),
                            array(
                                'stream_context' => $context,
                                'trace' => 1,
                                'soap_version'   => SOAP_1_1,
                                'style' => SOAP_DOCUMENT,
                                'encoding' => SOAP_LITERAL,
                                'cache_wsdl' => WSDL_CACHE_NONE
                            ));

            return $client;
        }
        
        /**
         * Logs all error messages and responses comes from the server
         * @param type $param
         * @param type $return_data
         */
        protected function log_entry($data)
        {
            $message  = "DATE & TIME : ".date("F j, Y, g:i a").PHP_EOL.                    
                    "FUNCTION: ".$data["function"].PHP_EOL.
                    "PASSED_DATA ARRAY: ".print_r($data['params'],true).PHP_EOL.                                                
                    " ".PHP_EOL.
                    "RESPONSE: ".print_r($data['return_data'],true).PHP_EOL.                            
                    "-------------------------------------------------".PHP_EOL;
            Mage::log($message, Zend_log::ALERT, $this->_LOG_FILE);
            
        }

}