<?php

class Aramex_ApiLocationValidator_Model_Api extends Mage_Core_Model_Abstract {

    protected $apiConnection;

    private function _getClientInfo() {
        return array(
            'AccountCountryCode' => 'JO',
            'AccountEntity' => 'AMM',
            'AccountNumber' => '20016',
            'AccountPin' => '331421',
            'UserName' => 'testingapi@aramex.com',
            'Password' => 'R123456789$r',
            'Version' => 'v1.0',
            'Source' => NULL
        );
    }

    public function resetStoreGeneralOptions() {
        $coreConfig = Mage::getModel('core/config');
        $collection = Mage::getModel("apilocationvalidator/country")->getCollection();
        $requiredStates = array();
        $postalCodeOptional = array();
        foreach ($collection as $col) {
            if ($col->getStateRequired() == 1) {
                if ($col->getCode()) {
                    $requiredStates[] = $col->getCode();
                }
            }
            if ($col->getPostCodeRequired() == 0) {
                if ($col->getCode()) {
                    $postalCodeOptional[] = $col->getCode();
                }
            }
        }
        $requiredStates = array_unique($requiredStates);
        $postalCodeOptional = array_unique($postalCodeOptional);


        if (count($requiredStates) > 0 && false) {
            $requiredStatesString = implode(',', $requiredStates);
            $coreConfig->saveConfig('general/region/state_required', $requiredStates);
        }

        if (count($postalCodeOptional) > 0) {
            $postalCodeOptionalString = implode(',', $postalCodeOptional);
            $coreConfig->saveConfig('general/country/optional_zip_countries', $postalCodeOptionalString);
        }
    }

    public function fetchCities($CountryCode, $NameStartsWith = NULL) {
        /* $clientInfo = Mage::helper('aramexshipment')->getClientInfo();		
          default user account to working with location api so we  used static account */
        $clientInfo = $this->_getClientInfo();

        $params = array(
            'ClientInfo' => $clientInfo,
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ),
            'CountryCode' => $CountryCode,
            'State' => NULL,
            'NameStartsWith' => $NameStartsWith,
        );
        $baseUrl = Mage::helper('aramexshipment')->getWsdlPath();
        
        $soapClient = new SoapClient($baseUrl . 'Location-API-WSDL.wsdl');

        try {
            $results = $soapClient->FetchCities($params);


            if (is_object($results)) {
                if (!$results->HasErrors) {
                    $cities = $results->Cities->string;
                    return $cities;
                }
            }
        } catch (SoapFault $fault) {
            Mage::log('Error : ' . $fault->faultstring);
        }
    }

    public function validateAddress($address) {
        $clientInfo = $this->_getClientInfo();
        $params = array(
            'ClientInfo' => $clientInfo,
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ),
            'Address' => array(
                'Line1' => '001',
                'Line2' => '',
                'Line3' => '',
                'City' => $address['city'],
                'StateOrProvinceCode' => '',
                'PostCode' => $address['post_code'],
                'CountryCode' => $address['country_code']
            )
        );


        $baseUrl = Mage::helper('aramexshipment')->getWsdlPath();
        $soapClient = new SoapClient($baseUrl . 'Location-API-WSDL.wsdl');
        $reponse = array();
        try {
            $results = $soapClient->ValidateAddress($params);
            if (is_object($results)) {
                if ($results->HasErrors) {
                    $suggestedAddresses = $results->SuggestedAddresses->Address;
                    $message = $results->Notifications->Notification->Message;
                    $reponse = array('is_valid' => false, 'suggestedAddresses' => $suggestedAddresses, 'message' => $message);
                } else {
                    $reponse = array('is_valid' => true);
                }
            }
        } catch (SoapFault $fault) {
            Mage::log('Error : ' . $fault->faultstring);
        }
        return $reponse;
    }

    public function fetchJsonCities($CountryCode, $NameStartsWith = NULL) {
        $clientInfo = Mage::helper('aramexshipment')->getClientInfo();
        /* default user account to working with location api so we  used static account */
        $clientInfo = $this->_getClientInfo();

        $params = array(
            'ClientInfo' => $clientInfo,
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ),
            'CountryCode' => $CountryCode,
            'State' => NULL,
            'NameStartsWith' => $NameStartsWith,
        );
        $reponse = array();

        $baseUrl = Mage::helper('aramexshipment')->getWsdlPath();
        $soapClient = new SoapClient($baseUrl . 'Location-API-WSDL.wsdl');

        try {
            $results = $soapClient->FetchCities($params);


            if (is_object($results)) {
                if (!$results->HasErrors) {
                    $cities = $results->Cities->string;
                }
            }
            if (count($cities)) {
                $temp = array();
                foreach ($cities as $city) {
                    if (!in_array($city, $temp)) {
                        $temp[] = $city;
                        //$reponse[] =array('title'=>$city);
                        $reponse[] = ucfirst(strtolower($city));
                    }
                }
                sort($reponse);
                return $reponse;
            }
        } catch (SoapFault $fault) {
            Mage::log('Error : ' . $fault->faultstring);
        }
        return $reponse;
    }

    private function _getWsdlPath() {
        $wsdlBasePath = Mage::getModuleDir('etc', 'Aramex_Shipment') . DS . 'wsdl' . DS . 'Aramex' . DS;
        $storeId = Mage::app()->getStore()->getStoreId();
        if (Mage::getStoreConfig('aramexsettings/config/sandbox_flag', $storeId) == 1) {
            $wsdlBasePath .='TestMode' . DS;
        }
        return $wsdlBasePath;
    }

    public function fetchCountriesList() {
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $storeId = Mage::app()->getStore()->getStoreId();
        $clientInfo = Mage::helper('aramexshipment')->getClientInfo($storeId);

        /* default user account to working with location api so we  used static account */
        $clientInfo = $this->_getClientInfo();

        $params = array(
            'ClientInfo' => $clientInfo,
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ),
        );


        $baseUrl = $this->_getWsdlPath();

        $soapClient = new SoapClient($baseUrl . 'Location-API-WSDL.wsdl');

        try {
            $results = $soapClient->FetchCountries($params);
            if (is_object($results)) {
                if (!$results->HasErrors) {
                    /* remove all old entries */
                    $write->query('TRUNCATE TABLE `' . $resource->getTableName('apilocationvalidator/country') . '`');

                    $countries = $results->Countries->Country;
                    foreach ($countries as $country) {
                        $model = Mage::getModel('apilocationvalidator/country');
                        $model->setCode($country->Code);
                        $model->setName($country->Name);
                        $model->setIsoCode($country->IsoCode);
                        $model->setStateRequired($country->StateRequired);
                        $model->setPostCodeRequired($country->PostCodeRequired);
                        $model->setPostCodeRegex($country->PostCodeRegex);
                        $model->setInternationalCallingNumber($country->InternationalCallingNumber);

                        $model->save();
                    }
                    $this->resetStoreGeneralOptions();
                }
            }
        } catch (SoapFault $fault) {
            Mage::log('Error : ' . $fault->faultstring);
        }
    }

}
