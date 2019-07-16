<?php

class Progos_Speedex_Request
{
    public function createShipment()
    {
        $wsdl = 'http://172.53.1.34:8095/APIService/PostaWebClient.svc?wsdl';
        $this->proxy = new SoapClient($this->soapURLv2,array(
    			'login' => $this->authLogin,
    			'password' => $this->authKey
    			));
        $parameters =array('SHIPINFO' => array(
    			'CashOnDelivery' => 100,
    			'CashOnDeliveryCurrency' => 'CUR6',
    			'ClientInfo' => array(
    					'CodeStation' => 'KWI',
    					'Password' => 'shr',
    					'ShipperAccount' => 'Test7474',
    					'UserName' => 'shareefsh'    					
    			),
    			'CodeCurrency' => 'KWD',
    			'CodeService' => 'SRV6',
    			'CodeShippmentType' => 'SHPT1',
    			'ConnoteContact' => array(
    					'Email1' => 'test@mail.com',
    					'Email2' => 'test@mail.com',
    					'TelHome' => '123456',
    					'TelMobile' => '12345',
    					'WhatsAppNumber' => '1233'
    			),
    			'ConnoteDescription' => 'This is a test enty by roshan done via API from Magento',
    			'ConnoteInsured' => 'Y',
    			'ConnoteNotes' => array(
    					'Note1' => 'test1',
    					'Note2' => 'test1',
    					'Note3' => 'test1',
    					'Note4' => 'test1',
    					'Note5' => 'test1'    					
    			),
    			'ConnotePerformaInvoice' => array(
    					'CONNOTEPERMINV' => array(
                                            'CodeHS' => 'TEST',
                                            'CodePackageType' => 'TEST',
                                            'Description' => 'TEST',
                                            'OrginCountry' => 'KWI',
                                            'Quantity' => '1',
                                            'RateUnit' => '1'    			
    					)
    			),
    			'ConnotePieces'=>'1',
    			'ConnoteProhibited'=>'N',
    			'ConnoteRef'=>array(
    					'Reference1'=>'test1',
    					'Reference2'=>'test1'
    			),
    			'Consignee'=>array(
    					'Company'=>'home',
    					'FromAddress'=>'kuwait city',
    					'FromArea'=>'AREA75',
    					'FromCity'=>'CITY96303',
    					'FromCodeCountry'=>'KWT',
    					'FromMobile'=>'12345678',
    					'FromName'=>'Roshan2',
    					'FromPinCode'=>'1234',
    					'FromProvince'=>'KW',
    					'FromTelphone'=>'123',
    					'Remarks'=>'test entry by roshan via Magento',
    					'ToAddress'=>'Kuwait',
    					'ToArea'=>'AREA71',
    					'ToCity'=>'CITY24051',
    					'ToCivilID'=>'123456',
    					'ToCodeCountry'=>'KWT',
    					'ToCodeSector'=>'',
    					'ToDesignation'=>'',
    					'ToMobile'=>'123456',
    					'ToName'=>'Roshan Magento',
    					'ToPinCode'=>'123',
    					'ToProvince'=>'KW',
    					'ToTelPhone'=>'123456'  					
    			),
    			'CostShipment'=>'1234',
    			'ItemDetails'=> array(
    					'ITEMDETAILS'=>array(
    							'ConnoteHeight'=>'10',
    							'ConnoteLength'=>'10',
    							'ConnoteWeight'=>'10',
    							'ConnoteWidth'=>'10',
    							'ScaleWeight'=>'10'
    					)
    			),
    			'NeedRoundTrip'=>'N'   			
    	));
    }
}

