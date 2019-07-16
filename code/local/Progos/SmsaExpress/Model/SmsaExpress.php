<?php
class Progos_SmsaExpress_Model_SmsaExpress extends Mage_Core_Model_Abstract {
    public function createShipment( $params ){
    	$apiParams = array();
        $response  = array();
        $helper = Mage::helper('progos_smsaexpress');
        $apiParams['passKey'] = $helper->getApiKey();
    	$apiParams['refNo'] = $params['smsaexpress_shipment_original_reference'];
    	$apiParams['sentDate'] = date("Y-m-d");
    	$apiParams['idNo'] = $params['smsaexpress_shipment_original_reference'];
    	$apiParams['cName'] = $params['cName'];
    	$apiParams['cntry'] = $params['cntry'];
    	$apiParams['cCity'] = $params['cCity'];
    	$apiParams['cZip'] = $params['cZip'];
    	$apiParams['cPOBox'] = '';
    	$apiParams['cMobile'] = $params['cMobile'];
    	$apiParams['cTel1'] = '';
    	$apiParams['cTel2'] = '';
    	$apiParams['cAddr1'] = $params['cAddr1'];
    	$apiParams['cAddr2'] = '';
    	$apiParams['shipType'] = $helper->getMethod(); //Get from Setting which have different type shipment. Hardcode
    	$apiParams['PCs'] = $params['PCs'];
    	$apiParams['cEmail'] = $params['cEmail'];
    	$apiParams['carrValue'] = '0'; // Carraige Value , shipment charges
		$apiParams['carrCurr'] = $params['order_currency_code']; // Order Currancy / store currency
		$apiParams['codAmt'] = $params['total_due'];// Total due jaega
		$apiParams['weight'] = '1';//
		$apiParams['custVal'] = $params['customs_value'];//Customs Value
		$apiParams['custCurr'] = '';//Customs Currency
		$apiParams['insrAmt'] = '';//Insurance Value
		$apiParams['insrCurr'] = '';//Insurance Currency
		$apiParams['itemDesc'] = $params['smsaexpress_description'];//Description
		$orderNumber = $params['smsaexpress_shipment_original_reference'];
    	try{
    		$url        = $helper->getApiUrl(); //Dynamics Request
			$client     = new SoapClient($url, array("trace" => 1, "exception" => 0));
			$apiResponse = $client->addShipment($apiParams);

			$trackingCode =  $apiResponse->addShipmentResult;
            if (preg_match('/Failed/',$trackingCode)){
                $response['status']     = false;
                Mage::getSingleton('adminhtml/session')->addError($trackingCode);
                $trackingCode = false;
            }
			if( $trackingCode  ){
				try{
					$orderNumber = $params['smsaexpress_shipment_original_reference'];
					$shipmentid = Mage::getModel('sales/order_shipment_api')->create($orderNumber , $params['smsaexpress_items'], "AWB No. ".$trackingCode." - Order No. ".$orderNumber." - <a href='javascript:void(0);' onclick='smsaexpressObj.printLabel();'>Print Label</a>");
					$ship 		= true;						
					try{
						$ship = Mage::getModel('sales/order_shipment_api')->addTrack($shipmentid, 'smsaexpress', 'Smsa Express', $trackingCode );
                        $response['status']  = true;
                        $response['trackingNumber'] = $trackingCode;
                        $response['shipmentId'] = $shipmentid;
					}catch( Exception $addTracking ){
                        $response['status']     = false;
                        $response['msg']        = "Magento Tracking not create.";
                        $response['trackingNumber'] = $trackingCode;
                        $response['shipmentId'] = $shipmentid;
						Mage::getSingleton('adminhtml/session')->addError($addTracking->getMessage());
					}		
				}catch( Exception $createShipment ){
                    $response['status']     = false;
                    $response['msg']        = "Shipment Not Created.";
                    $response['trackingNumber'] = $trackingCode;
					Mage::getSingleton('adminhtml/session')->addError($createShipment->getMessage());
				}
			}
    	}catch( Exception $createTracking){
            $response['status']     = false;
            $response['msg']        = "Smsa Tracking not created.";
            $response['trackingNumber'] = $trackingCode;
    		Mage::getSingleton('adminhtml/session')->addError($createTracking->getMessage());
    	}
        return $response;
    }

    public function toOptionArray(){
            $options    = array(
                array(
                    'value' => 'DLV',
                    'label' => Mage::helper('adminhtml')->__('DLV')
                ),
                array(
                    'value' => 'VAL',
                    'label' => Mage::helper('adminhtml')->__('VAL')
                ),
                array(
                    'value' => 'HAL',
                    'label' => Mage::helper('adminhtml')->__('HAL')
                ),
                array(
                    'value' => 'BLT',
                    'label' => Mage::helper('adminhtml')->__('BLT')
                ),
            );
            return $options;
    }
}
?>