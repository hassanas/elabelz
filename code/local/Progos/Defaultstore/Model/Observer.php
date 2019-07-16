<?php
require_once(Mage::getBaseDir('lib') . '/Net/dbip/dbip.class.php');

class Progos_Defaultstore_Model_Observer
{
    protected $_countryToStoreCode = array(
        'AE' => 'ar_ae',
        'SA' => 'ar_sa',
        'KW' => 'ar_kw',
        'QA' => 'ar_qa',
        'BH' => 'ar_bh',
        'OM' => 'ar_om',
        'IQ' => 'ar_iq',
        'US' => 'en_us',
        'GB' => 'en_uk'
    );
    public function getStoreCodeByCountry($country)
    {
        if (isset($this->_countryToStoreCode[$country])) {
            return $this->_countryToStoreCode[$country];
        }
        if (Mage::getStoreConfig('general/country/defaultstore')) {
            return Mage::getStoreConfig('general/country/defaultstore');
        } else {
            return 'en_ae';
        }
    }

    public function getCountryCode($ip)
    {
        $config = Mage::getConfig()->getResourceConnectionConfig('default_setup');
        $hostname = $config->host;
        $user = $config->username;
        $password = $config->password;
        $dbname = $config->dbname;
        $db = new PDO("mysql:host=" . $hostname . ";dbname=" . $dbname, $user, $password);
        $dbip = new DBIP($db);
        $info = $dbip->Lookup($ip);
        return $info->country;
    }


    public function controllerActionPredispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('general/country/enable')) {
            $current_url = $_SERVER['REQUEST_URI'];
            $exceptions = explode(",", Mage::getStoreConfig("marketplace/exceptions/path"));
            $noStore = true;
            $storeCode='';
            foreach ($exceptions as $exception) {
                if (!is_null($exception) OR !empty($exception)) {
                    if (preg_match('/' . $exception . '/', $current_url)) {
                        $noStore = false;
                        $storeCode = $exception;
                        break;
                    }
                }
            }
            if (Mage::getStoreConfig('general/country/logIp')) {
                Mage::log('For '.$this->getClientIP().' Found Store Code: ' . $storeCode, null, 'geoipDefaultStore.log');
            }
            if ($noStore) {
                $ip = $this->getClientIP();
                if (Mage::app()->getRequest()->getParam('ip')){
                    $ip = Mage::app()->getRequest()->getParam('ip');
                }
                $countryCode = '';
                if (Mage::getStoreConfig('general/country/defaultlang') == 2) {
                    $this->_countryToStoreCode = [
                        'AE' => 'en_ae',
                        'SA' => 'en_sa',
                        'KW' => 'en_kw',
                        'QA' => 'en_qa',
                        'BH' => 'en_bh',
                        'OM' => 'en_om',
                        'IQ' => 'en_iq',
                        'US' => 'en_us',
                        'GB' => 'en_uk'
                    ];
                }
                try {
                    $countryCode = $this->getCountryCode($ip);
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), 2,'geoipDefaultStore.log');
                }
                $storeCode = $this->getStoreCodeByCountry($countryCode);
                if (Mage::getStoreConfig('general/country/logIp')){
                    Mage::log('IP '.$ip.' CountryCode: '.$countryCode.' got StoreCode: '.$storeCode,null,'geoipDefaultStore.log');
                    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
                        Mage::log('HTTP_X_FORWARDED_FOR '.$_SERVER["HTTP_X_FORWARDED_FOR"],null,'geoipDefaultStore.log');
                    }
                    if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                        Mage::log('REMOTE_ADDR '.$_SERVER["REMOTE_ADDR"],null,'geoipDefaultStore.log');
                    }
                    if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
                        Mage::log('HTTP_CLIENT_IP '.$_SERVER["HTTP_CLIENT_IP"],null,'geoipDefaultStore.log');
                    }

                }
                if ($storeCode) {
                    $store = Mage::getModel('core/store')->load($storeCode, 'code');
                    Mage::app()->setCurrentStore($storeCode);
                    $locale = Mage::app()->getLocale()->getLocaleCode();
                    Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
                    Mage::app()->getLocale()->setLocale($locale);
                    Mage::app()->getTranslator()->init('frontend', true);
                    $currentUrl = Mage::helper('core/url')->getCurrentUrl();
                    $url = Mage::getSingleton('core/url')->parseUrl($currentUrl);
                    $path = $url->getPath();
                    Mage::helper("page/switch")->setPreviousUrl($current_url);
                    $response = Mage::app()->getFrontController()->getResponse();
                    $response->setRedirect($store->getCurrentUrl(false));
                    $response->sendResponse();
                    exit;
                }
            }
        }
    }
    public function getClientIP(){
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
            return  $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER["REMOTE_ADDR"];
        }else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        return Mage::helper('core/http')->getRemoteAddr();
    }
}
