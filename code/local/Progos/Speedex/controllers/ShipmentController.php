<?php
class Progos_Speedex_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    const XML_PATH_TRANS_IDENTITY_EMAIL  = 'trans_email/ident_general/email';
    const XML_PATH_TRANS_IDENTITY_NAME   = 'trans_email/ident_general/name';
    const XML_PATH_SHIPMENT_EMAIL_TEMPLATE = 'speedex/template/shipment_template';
    const XML_PATH_SHIPMENT_EMAIL_COPY_TO     = 'speedex/template/copy_to';
    const XML_PATH_SHIPMENT_EMAIL_COPY_METHOD = 'speedex/template/copy_method';
    public function postAction()
    {
        $speedexHelper = Mage::helper('speedex');
        $SOAP_URL = $speedexHelper->getSoapUrl();
        $credentials = $speedexHelper->getCredentials();
        
        $error = false;
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
            if($post['cash_on_delivery'] == 'Y') {
                $PP->setCashOnDeliveryAmount($post['cash_on_delivery']);
                $PP->setCashOnDeliveryCurrency($post['speedex_shipment_cash_on_delivery_currency']);
            }
            $PP->setCodeCurrency($post['speedex_shipment_currency_code']);
            $PP->setCodeService($post['speedex_shipment_service_code']);
            $PP->setShipmentType($post['speedex_shipment_shipment_type']);
            $contacts = array(
                            'SHIPPER'=>array(
                                            'EMAIL' => $post['speedex_shipment_shipper_email']				
                            ),
                            'RECEIVER'=>array(
                                            'EMAIL' => $post['speedex_shipment_receiver_email'],
                                            'TELEPHONE' => $post['speedex_shipment_receiver_phone'],
                                            'MOBILE' => $post['speedex_shipment_receiver_mobileno'],
                                            'WHATSAPP' => $post['speedex_shipment_receiver_whatsup'])		
                            );
            $PP->setConnoteContactElements($contacts);
            $PP->setConnoteDescription($post['description']);
            $PP->setIsConnoteInsured($post['speedex_shipment_insured']);
            $notes = array(
                            'NOTE1' => isset($post['note1']) ? $post['note1'] : '',
                            'NOTE2' => isset($post['note2']) ? $post['note2'] : '',
            );
            $PP->setConnoteNotesElements($notes);
            if($post['speedex_shipment_service_code'] == 'SRV3') {
                $invoiceCountry = Mage::getModel('directory/country')->load($post['invoice_origin_country_code_0']);
                $count = $post['speedex_invoice_items'];
                $performa_invoice= array(
                    0 => array(
                        'CODE_HS' => $post['invoice_hs_name_0'],
                        'CODE_PACKAGE_TYPE' => $post['invoice_package_type_0'],
                        'DESCRIPTION' => $post['invoice_item_description_0'],
                        'ORIGIN_COUNTRY_CODE' => $invoiceCountry->getIso3Code(),
                        'QUANTITY' => $post['invoice_quantity_0'],
                        'UNIT_RATE' => $post['invoice_unit_rate_0'])

                );
                for($i = 1; $i <= $count; $i++) {
                    $invoiceCountry = Mage::getModel('directory/country')->load($post['invoice_origin_country_code_0_'. $i]);
                    $performa_invoice[$i] = array(
                        'CODE_HS' => $post['invoice_hs_name_0_'. $i],
                        'CODE_PACKAGE_TYPE' => $post['invoice_package_type_0_'. $i],
                        'DESCRIPTION' => $post['invoice_item_description_0_'. $i],
                        'ORIGIN_COUNTRY_CODE' => $invoiceCountry->getIso3Code(),
                        'QUANTITY' => $post['invoice_quantity_0_'. $i],
                        'UNIT_RATE' => $post['invoice_unit_rate_0_'. $i]
                        );
                }
                $PP->setPerformaInvoiceElement($performa_invoice);
            }
            $PP->setConnotePieces($post['pieces']);
            $PP->setIsConnoteProhibited($post['speedex_shipment_prohibited']);
            $ref = array(
		'REFERENCE1' => $post['reference1'],
		'REFERENCE2' => $post['reference2']
            );
            $shipperCountry = Mage::getModel('directory/country')->load($post['speedex_shipment_shipper_country']);
            $receiverCountry = Mage::getModel('directory/country')->load($post['speedex_shipment_receiver_country']);
            $PP->setReferencesElements($ref);
            $consignee_data = array(
		'SHIPPER' => array(							
			'FULL_ADDRESS' => $post['speedex_shipment_shipper_street'],
			'FROM_AREA' => 'NA',
			'FROM_CITY' => $post['speedex_shipment_shipper_city_code'],
			'COUNTRY_CODE' => $shipperCountry->getIso3Code(),
			'PHONE_NO' => $post['speedex_shipment_shipper_phone'],
			'SHIPPER_NAME' => $post['speedex_shipment_shipper_name'],
			'PIN_CODE' => 'NA',
			'FROM_PROVINCE' => $post['speedex_shipment_shipper_state'],
			'FROM_TELEPHONE' => $post['speedex_shipment_shipper_phone'],
			'REMARKS' => $post['remarks'] ),
		'RECEIVER' => array(				
                        'COMPANY_NAME' => $post['speedex_shipment_receiver_company'],
			'TO_ADDRESS' => $post['speedex_shipment_receiver_street'],
			'TO_AREA' => 'NA',
			'TO_CITY' => $post['speedex_shipment_receiver_city_code'],
			'TO_CIVIL_ID' => 'NA',
			'TO_CODE_COUNTRY' => $receiverCountry->getIso3Code(),
			'TO_CODE_SECTOR'=>'NA',
			'TO_DESIGNATION' => $post['speedex_shipment_receiver_designation'],
			'TO_MOBILE' => $post['speedex_shipment_receiver_mobileno'],
			'TO_NAME' => $post['speedex_shipment_receiver_name'],
			'TO_TELEPHONE' => $post['speedex_shipment_receiver_phone'],
			'TO_PIN_CODE' => 'NA',
			'TO_PROVINCE_CODE' => $post['speedex_shipment_receiver_state']
                    )		
		);
                $PP->setConsigneeElements($consignee_data);
                $PP->setCostOfShipmentAmount($post['cost_shipment']);
                $items = array(
                        0 => array(
                            'HEIGHT' => $post['height'],
                            'LENGTH' => $post['length'],
                            'WIDTH' => $post['width'],
                            'SCALE_WEIGHT' => $post['scale_weight'],
                            'WEIGHT' => $post['weight'] 
                        )
                );
                $PP->setItemsDetailsElement($items);
                $PP->setIsPickupNeeded('N');
                $PP->setIsRoundTripNeeded($post['speedex_shipment_need_round_trip']);
                $params =$PP->prepare_SOAP_request_parem_array();
                $debug = false;
                $return_string = $PP->create_shipment($params,$debug);
                if(is_numeric($return_string)){
                    $order = Mage::getModel('sales/order')->loadByIncrementId($post['speedex_shipment_original_reference']);
                    $payment = $order->getPayment();
                    if($order->canShip()) {
                        try {
                           $shipmentid = Mage::getModel('sales/order_shipment_api')->create($order->getIncrementId(), $post['speedex_items'], "AWB No. ".$return_string." - Order No. - <a href='javascript:void(0);' onclick='myObj1.printLabel();'>Print Label</a>");
                            $ship = true;						
                            $ship = Mage::getModel('sales/order_shipment_api')->addTrack($shipmentid, 'speedex', 'Speedex', $return_string); 
                            if($ship){
                                $storeId = $order->getStore()->getId();
                                $copyTo = $speedexHelper->getEmails(self::XML_PATH_SHIPMENT_EMAIL_COPY_TO, $storeId);
                                $copyMethod = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_EMAIL_COPY_METHOD, $storeId);
                                $templateId = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_EMAIL_TEMPLATE, $storeId);
                                if ($order->getCustomerIsGuest()) {					
                                    $customerName = $order->getBillingAddress()->getName();
                                } else {									
                                    $customerName = $order->getCustomerName();
                                }
                                $shipments_id = $return_string;
                                $mailer = Mage::getModel('core/email_template_mailer');
                                $emailInfo = Mage::getModel('core/email_info');
                                $emailInfo->addTo($order->getCustomerEmail(), $customerName);
                                if ($copyTo && $copyMethod == 'bcc') {
                                    foreach ($copyTo as $email) {
                                        $emailInfo->addBcc($email);
                                    }
                                }
                                $mailer->addEmailInfo($emailInfo);

                                if ($copyTo && $copyMethod == 'copy') {
                                    foreach ($copyTo as $email) {
                                        $emailInfo = Mage::getModel('core/email_info');
                                        $emailInfo->addTo($email);
                                        $mailer->addEmailInfo($emailInfo);
                                    }
                                }
                                $senderName = Mage::getStoreConfig(self::XML_PATH_TRANS_IDENTITY_NAME, $storeId);
                                $senderEmail = Mage::getStoreConfig(self::XML_PATH_TRANS_IDENTITY_EMAIL, $storeId); 
                                $mailer->setSender(array('name' => $senderName, 'email' =>$senderEmail));
                                $mailer->setStoreId($storeId);
                                $mailer->setTemplateId($templateId);
                                $mailer->setTemplateParams( array(
                                            'order'        => $order,						
                                            'shipments_id' => $shipments_id					
                                        )
                                );
                                try {
                                    $mailer->send();
                                }
                                catch(Exception $ex) {
                                    Mage::getSingleton('core/session')->addError('Unable to send email. '. $ex->getMessage());
                                }
                            }
                        } catch (Exception $ex) {
                            Mage::getSingleton('core/session')->addError($ex->getMessage());
                        }
                        Mage::getSingleton('core/session')->addSuccess('Speedex Shipment Number: '. $return_string. ' has been created.');
                    }
                    else {
                        Mage::getSingleton('core/session')->addError('Cannot do shipment for the order.');
                    }
                } else {
                    Mage::getSingleton('core/session')->addError('An Error Occured'. $return_string);
                }
            
            $formSession = Mage::getSingleton('adminhtml/session');
            $formSession->setData("form_data",$post);
            
            
        } catch (Exception $ex) {
            Mage::getSingleton('core/session')->addError($ex->getMessage());
        }
        $this->_redirectUrl($post['speedex_shipment_referer']);
    }
    
    public function trackShipmentAction()
    {
        $speedexHelper = Mage::helper('speedex');
        $SOAP_URL = $speedexHelper->getSoapUrl();
        $credentials = $speedexHelper->getCredentials();
        $debug = false;
        $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
        $params = array(
            'awbno'=>'YOUR_AWB_NUMBER',    
            'ref1'=>'',
            'ref2'=>''
        );

        $return_data = $PP->shipment_tracking($params, $debug);
        $message = "";
        if(count($return_data->TRACKSHIPMENT) > 1 ) {
            foreach($return_data->TRACKSHIPMENT as $track) {
                //var_dump($track);
                $message .= "Event Code: ".$track->Event.'<br>';
                $message .= "Event Date and time: ".$track->DateTime."<br>";
                $message .= "Event Name: ".$track->EventName."<br>";
                $message .= "Notes : ".$track->Note."<br>";
                $message .= "Errors : ".$track->ErrorMsg."<br><br>";
            }
            Mage::getSingleton('core/session')->addSuccess($message);
        } else {
            foreach($return_data as $track) {

                $message = "Event Code: ".$track-> Event.'<br>';
                $message .= "Event Date and time: ".$track-> DateTime."<br>";
                $message .= "Event Name: ".$track-> EventName."<br>";
                $message .= "Notes : ".$track-> Note."<br>";
                $message .= "Errors : ".$track-> ErrorMsg."<br><br>";
            }
            Mage::getSingleton('core/session')->addSuccess($message);
        }
    }
    
    public function voidShipmentAction()
    {
        $speedexHelper = Mage::helper('speedex');
        $SOAP_URL = $speedexHelper->getSoapUrl();
        $credentials = $speedexHelper->getCredentials();
        $debug = false;
        $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
        $params = array(
            'AWBNO' => 'AWB_NUMBER',
            'Reason' => 'REASON FOR VOIDING'    
        );

        $return_data = $PP->void_shipment($params, $debug);
        
    }
    
    public function costCalculationtAction()
    {
        $response = array();
        try {
            $speedexHelper = Mage::helper('speedex');
            $SOAP_URL = $speedexHelper->getSoapUrl();
            $credentials = $speedexHelper->getCredentials();
            $debug = false;
            $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
            $post = $this->getRequest()->getPost();
            $originCountry = Mage::getModel('directory/country')->load($post['origin_country']);
            $destinationCountry = Mage::getModel('directory/country')->load($post['destination_country']);
            $params = array(
                "ScaleWeight" => $post['scaleweight'],
                "Length" => $post['length'],                
                "Height" => $post['height'],                
                "Width" => $post['width'],                
                "OriginCountryCode" => $originCountry->getIso3Code(),                
                "DestinationCountryCode" => $destinationCountry->getIso3Code(),                    
            );
            $return_data = $PP->calculate_shipping_cost($params, $debug);
            if($return_data->Response == 'SUCCESS' ){
                $response['type'] = 'success';
                $response['html'] = 'Amount : '.$return_data->Amount.'<br>'. 'Message : '.$return_data->Message.'<br>'.'Response : '.$return_data->Response.'<br>';

            } else {
                $response['error'] = is_object($return_data)? $return_data->Message: $return_data;
                $response['type']  = 'error';
            }
        } catch (Exception $e) {
            $response['type'] = 'error';
            $response['error'] = $e->getMessage();			
	}
        print json_encode($response);		
	die();
    }
    
    public function pickUpCancelAction()
    {
        $speedexHelper = Mage::helper('speedex');
        $SOAP_URL = $speedexHelper->getSoapUrl();
        $credentials = $speedexHelper->getCredentials();
        $debug = false;
        $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
        $params = array(
            'PickupNumber' => 'YOUR_PICKUP_NUMBER',
            'Reason' => 'REASON FOR CANCELLING'    
        );

        $return_data = $PP->cancel_pickup($params, $debug);
        
    }
    
    public function pickUpCreationAction()
    {
        $response = array();
        try {
            $speedexHelper = Mage::helper('speedex');
            $SOAP_URL = $speedexHelper->getSoapUrl();
            $credentials = $speedexHelper->getCredentials();
            $debug = false;
            $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
            $post = $this->getRequest()->getPost();
            $params = array(
                "CodeService" => $post['code_service'],            
                "ContactPerson" => $post['contact_person'],
                "DeliverContactPerson" => $post['delivery_contact_person'],           
                "DeliveryAddress" => $post['delivery_address'],
                "DeliveryCity" => $post['delivery_city'],            
                "DeliveryPhone" => $post['delivery_phone'],
                "GoodsType" => $post['goods_type'],
                "Notes" => $post['notes'],            
                "PickAddress" => $post['pick_address'],            
                "PickCity" => $post['pick_city'],        
                "PickDate" => $post['pick_date'],
                "PickPhone" => $post['pick_phone'],  
                "RequestedPerson" => $post['requested_person'],                        
                "TimeEnd" => $post['time_end'],
                "TimeOffice" => $post['time_office'],
                "TimeStart" => $post['time_start']
            );
            $return_data = $PP->create_pickup($params, $debug);
            if(!empty($return_data->Response)){
                $message = '<br>>Pickup Message :'. $return_data->Message."<br>";
                $message .= 'Response :'.$return_data->Response."<br>";
                $message .= 'Request Sequence: '.$return_data->RequestSequence."<br>";
                $response['type'] = 'success';
                $response['html'] = $message;
                
            } else {
                $response['type'] = 'error';
                $response['error'] = 'Exceptional Error<br>'.$return_data;
                
            }
        } catch (Exception $e) {
            $response['type'] = 'error';
            $response['error'] = $e->getMessage();			
	}
        print json_encode($response);		
	die();
        
    }
}