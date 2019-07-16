<?php
class Progos_Speedex_Model_Carrier extends Mage_Usa_Model_Shipping_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'speedex';
    protected $_request = null;
    protected $_result 	= null;
    protected $_defaultGatewayUrl = null;

    public function collectRates( Mage_Shipping_Model_Rate_Request $request )
    {
        $result = Mage::getModel('shipping/rate_result');
        $result->append($this->_getCustomShippingMethod());
        return $result;
    }

    protected function _getCustomShippingMethod()
    {
        $rate = Mage::getModel('shipping/rate_result_method');
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('shippingMethod');
        $rate->setMethodTitle('Shipping Method');
        $rate->setPrice(null);
        $rate->setCost(null);
        return $rate;
    }

    public function isTrackingAvailable()
    {
        return true;
    }
    public function getAllowedMethods()
    {
        return true;
    }

    protected function _doShipmentRequest(Varien_Object $request)
    {
        return $result;
    }
    
    public function getTracking($trackings) 
    {
        $this->_result = Mage::getModel('shipping/tracking_result');
        $speedexHelper = Mage::helper('speedex');
        $SOAP_URL = $speedexHelper->getSoapUrl();
        $credentials = $speedexHelper->getCredentials();
        $debug = false;
        $PP = new Progos_Speedex_Model_PostaPlusAPI($SOAP_URL, $credentials);
        $params = array(
            'awbno' => $trackings,    
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
        } else {
            foreach($return_data as $track) {

                $message = "Event Code: ".$track-> Event.'<br>';
                $message .= "Event Date and time: ".$track-> DateTime."<br>";
                $message .= "Event Name: ".$track-> EventName."<br>";
                $message .= "Notes : ".$track-> Note."<br>";
                $message .= "Errors : ".$track-> ErrorMsg."<br><br>";
            }
        }
        $tracking = Mage::getModel('shipping/tracking_result_status');
        $tracking->setCarrier('speedex');
        $tracking->setCarrierTitle('Speedex');
        $tracking->setTracking($trackings);
        
        $tracking->setTrackSummary($message);
        $this->_result->append($tracking);
        return $this->_result;
        
    }
}