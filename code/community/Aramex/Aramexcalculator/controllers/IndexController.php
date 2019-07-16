<?php

class Aramex_Aramexcalculator_IndexController extends Mage_Core_Controller_Front_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function getConfigData($field, $store_id)
    {
        $path = 'carriers/aramex/' . $field;
        return Mage::getStoreConfig($path, $store_id);
    }

    public function CalculatorAction()
    {
        $post = $this->getRequest()->getPost();

        if (empty($post)) {
            $response['type'] = 'error';
            $response['error'] = $this->__('Invalid form data.');
            print json_encode($response);
            die();
        }
        $destination_city = $this->getRequest()->getPost('city');
        $destination_post_code = $this->getRequest()->getPost('post_code');
        $destination_country_code = $this->getRequest()->getPost('country_code');
        $currency = $this->getRequest()->getPost('currency');
        $storeId = $this->getRequest()->getPost('store_id');
        $product_id = $this->getRequest()->getPost('product_id');
        $product_group = 'EXP';
        $allowed_methods = Mage::getSingleton('aramex/carrier_aramex_source_internationalmethods')->toKeyArray();
        $allowed_methods_key = 'allowed_international_methods';

        if (Mage::getStoreConfig('aramexsettings/shipperdetail/country', $storeId) == $destination_country_code) {
            $product_group = 'DOM';
            $allowed_methods = Mage::getSingleton('aramex/carrier_aramex_source_domesticmethods')->toKeyArray();
            $allowed_methods_key = 'allowed_domestic_methods';
        }


        $admin_allowed_methods = explode(',', $this->getConfigData($allowed_methods_key));
        $admin_allowed_methods = array_flip($admin_allowed_methods);
        $allowed_methods = array_intersect_key($allowed_methods, $admin_allowed_methods);

        $baseUrl = Mage::helper('aramexshipment')->getWsdlPath($storeId);
        $clientInfo = Mage::helper('aramexshipment')->getClientInfo($storeId);
        $product = Mage::getModel('catalog/product')->load($product_id);
        $weight = $product->getWeight();

        $OriginAddress = array(
            'StateOrProvinceCode' => Mage::getStoreConfig('aramexsettings/shipperdetail/state', $storeId),
            'City' => Mage::getStoreConfig('aramexsettings/shipperdetail/city', $storeId),
            'PostCode' => Mage::getStoreConfig('aramexsettings/shipperdetail/postalcode', $storeId),
            'CountryCode' => Mage::getStoreConfig('aramexsettings/shipperdetail/country', $storeId),
        );
        $DestinationAddress = array(
            'StateOrProvinceCode' => "",
            'City' => $destination_city,
            'PostCode' => $destination_post_code,
            'CountryCode' => $destination_country_code,
        );
        $ShipmentDetails = array(
            'PaymentType' => 'P',
            'ProductGroup' => $product_group,
            'ProductType' => '',
            'ActualWeight' => array('Value' => $weight, 'Unit' => 'KG'),
            'ChargeableWeight' => array('Value' => $weight, 'Unit' => 'KG'),
            'NumberOfPieces' => 1
        );

        $params = array(
            'ClientInfo' => $clientInfo,
            'OriginAddress' => $OriginAddress,
            'DestinationAddress' => $DestinationAddress,
            'ShipmentDetails' => $ShipmentDetails,
            'PreferredCurrencyCode' => $currency
        );
        //SOAP object		 
        $soapClient = new SoapClient($baseUrl . 'aramex-rates-calculator-wsdl.wsdl');
        $priceArr = array();
        foreach ($allowed_methods as $m_value => $m_title) {
            $params['ShipmentDetails']['ProductType'] = $m_value;
            if ($m_value == "CDA") {
                $params['ShipmentDetails']['Services'] = "CODS";
            } else {
                $params['ShipmentDetails']['Services'] = "";
            }
            try {
                $results = $soapClient->CalculateRate($params);
                if ($results->HasErrors) {
                    if (count($results->Notifications->Notification) > 1) {
                        $error = "";
                        foreach ($results->Notifications->Notification as $notify_error) {
                            $error .= ('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message) . ' ';
                        }
                        $response['error'] = $error;
                    } else {
                        $response['error'] = ('Aramex: ' . $results->Notifications->Notification->Code . ' - ' . $results->Notifications->Notification->Message) . ' ';
                    }
                    $response['type'] = 'error';
                } else {
                    $response['type'] = 'success';
                    $priceArr[$m_value] = array('label' => $m_title, 'amount' => $results->TotalAmount->Value, 'currency' => $results->TotalAmount->CurrencyCode);
                }
            } catch (Exception $e) {
                $response['type'] = 'error';
                $response['error'] = $e->getMessage();
            }
        }
        if (empty($priceArr)) {
            if($response['type'] == 'error'){
                print json_encode($response);
                die();
            }

        } else {
            if ($response['type'] == 'success') {
                print json_encode($priceArr);
                die();
            }else{
                print json_encode($response);
                die();
            }

        }
        die();
    }
}
