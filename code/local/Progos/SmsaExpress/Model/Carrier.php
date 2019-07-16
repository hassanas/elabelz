<?php
class Progos_SmsaExpress_Model_Carrier extends Mage_Usa_Model_Shipping_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface{

    protected $_code = 'smsaexpress';
    protected $_request 			= null;
    protected $_result 				= null;
    protected $_defaultGatewayUrl 	= null;

    public function collectRates( Mage_Shipping_Model_Rate_Request $request ){
        $result = Mage::getModel('shipping/rate_result');
        /* @var $result Mage_Shipping_Model_Rate_Result */
        $result->append($this->_getCustomShippingMethod());
        return $result;
    }

	protected function _getCustomShippingMethod(){
        $rate = Mage::getModel('shipping/rate_result_method');
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('shippingMethod');
        $rate->setMethodTitle('Shipping Method');
        $rate->setPrice(null);
        $rate->setCost(null);
        return $rate;
    }

	public function isTrackingAvailable(){
		return true;
	}
	public function getAllowedMethods(){
		return true;
	}

	protected function _doShipmentRequest(Varien_Object $request)
	{
		return $result;
	}
}