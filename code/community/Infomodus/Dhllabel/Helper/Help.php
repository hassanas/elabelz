<?php

class Infomodus_Dhllabel_Helper_Help extends Mage_Core_Helper_Abstract
{
    private $ch;
    static public function escapeXML($string = "", $addSlash = null)
    {
        $string = preg_replace('/&/is', '&amp;', $string);
        $string = preg_replace('/</is', '&lt;', $string);
        $string = preg_replace('/>/is', '&gt;', $string);
        if ($addSlash !== null) {
            $string = preg_replace('/\'/is', '&#39;', $string);
            $string = preg_replace('/"/is', '&quot;', $string);
        }

        if (Mage::getStoreConfig('dhllabel/additional_settings/umlaut') == 1) {
            $string = str_replace(
                array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź',
                    'Ż', 'ü', 'ò', 'è', 'à', 'ì', 'é', 'ô', 'Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', 'ß',
                    'À', 'Á', 'Â', 'Ã', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ',
                    'Ò', 'Ô', 'Õ', 'Ù', 'Ú', 'Û', 'Ý', 'Þ', 'á', 'â', 'ã', 'å', 'æ', 'ç', 'ê', 'ë', 'í', 'î',
                    'ï', 'ð', 'ñ', 'õ', 'ù', 'ú', 'û', 'ý', 'þ', 'ÿ', 'Œ', 'œ', 'Š', 'š', 'Ÿ', 'ø', 'Ø'
                ),
                array('a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'A', 'C', 'E', 'L', 'N', 'O', 'S', 'Z', 'Z', 'u',
                    'o', 'e', 'a', 'i', 'e', 'o', 'A', 'a', 'U', 'u', 'O', 'o', 'ss',
                    'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O',
                    'O', 'U', 'U', 'U', 'Y', 'Th', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'i', 'i', 'i', 'o', 'n',
                    'o', 'u', 'u', 'u', 'y', 'th', 'y', 'Oe', 'oe', 'S', 's', 'Y', 'o', 'O'
                ),
                $string
            );
        }

        return $string;
    }

    static public function escapePhone($phone)
    {
        return str_replace(array(" ", "+", "-"), array("", "", ""), $phone);
    }

    public function curlSend($url, $data = null)
    {
        $this->error = true;
        $result = $this->curlSetOption($url, $data);
        $ch = $this->ch;
        if ($result) {
            $resultTemp = $result;
            $result = strstr($result, '<?xml');
            if ($result === FALSE) {
                $result = $resultTemp;
            }

            curl_close($ch);
            $this->error = false;
            return $result;
        } else {
            $error = '<h1>Error</h1> <ul>';
            $error .= '<li>Error Severity : Hard</li>';
            $error .= '<li>Error Description : ' . curl_errno($ch) . ' - ' . curl_error($ch) . '</li>';
            $error .= '</ul>';
            $error .= '<textarea>' . curl_errno($ch) . ' - ' . curl_error($ch) . '</textarea>';
            $error .= '<textarea>' . $data . '</textarea>';
            curl_close($ch);
            $this->error = true;
            return array('errordesc' => 'Server Error (cUrl)', 'error' => $error);
        }
    }

    public function curlSetOption($url, $data = null)
    {
        /*$sslV = curl_version();*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        /*if ($data != null) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        } else {*/
        curl_setopt($ch, CURLOPT_HEADER, 0);
        /*}*/

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->testing);
        /*curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);*/
        /*if (strpos($sslV['ssl_version'], 'NSS/') === FALSE) {
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1.2');
        }*/
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 GTB6");
        $this->ch = $ch;
        return curl_exec($ch);
    }

    public function sendPrint($data, $storeId = null)
    {
        $ip = trim(Mage::getStoreConfig('dhllabel/printing/automatic_printing_ip', $storeId));
        $port = trim(Mage::getStoreConfig('dhllabel/printing/automatic_printing_port', $storeId));
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            Mage::log("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
            Mage::getSingleton('adminhtml/session')->addError("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
            return;
        } else {
            $result = socket_connect($socket, $ip, $port);
            if ($result === false) {
                Mage::log("socket_connect() failed.\nReason: (" . $ip . ":" . $port . ") " . socket_strerror(socket_last_error($socket)));
                Mage::getSingleton('adminhtml/session')->addError("socket_connect() failed.\nReason: (" . $ip . ":" . $port . ") " . socket_strerror(socket_last_error($socket)));
                return;
            } else {
                socket_write($socket, $data, strlen($data));
            }

            socket_close($socket);
        }
    }

    /*multistore*/
    public function getStoreByCode($storeCode)
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $id) {
            $store = Mage::app()->getStore($id);
            if ($store->getCode() == $storeCode) {
                return $store;
            }
        }

        return null;
    }

    /*multistore*/

    public function getStores()
    {
        $c = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $c[$store->getId()] = $store->getName() . " (" . $website->getName() . " \\ " . $group->getName() . ")";
                }
            }
        }

        return $c;
    }

    public function createMediaFolders()
    {
        $baseMediaDir = Mage::getBaseDir('media');

        $pathUpsdir = $baseMediaDir . '/dhllabel';
        if (!is_dir($pathUpsdir)) {
            mkdir($pathUpsdir, 0777);
        }

        $pathUpsdir = $baseMediaDir . '/dhllabel/label';
        if (!is_dir($pathUpsdir)) {
            mkdir($pathUpsdir, 0777);
        }

        $pathUpsdir = $baseMediaDir . '/dhllabel/test_xml';
        if (!is_dir($pathUpsdir)) {
            mkdir($pathUpsdir, 0777);
        }

        if (is_dir($pathUpsdir)) {
            if (!file_exists($pathUpsdir . "/.htaccess")) {
                file_put_contents($pathUpsdir . "/.htaccess", "deny from all");
            }
        }
    }

    public function getWeightUnitByCountry($countryCode)
    {
        $c = array(
            "AD" => "KG",
            "AE" => "KG",
            "AF" => "KG",
            "AG" => "LB",
            "AI" => "LB",
            "AL" => "KG",
            "AM" => "KG",
            "AN" => "KG",
            "AO" => "KG",
            "AR" => "KG",
            "AS" => "LB",
            "AT" => "KG",
            "AU" => "KG",
            "AW" => "LB",
            "AZ" => "KG",
            "BA" => "KG",
            "BB" => "LB",
            "BD" => "KG",
            "BE" => "KG",
            "BF" => "KG",
            "BG" => "KG",
            "BH" => "KG",
            "BI" => "KG",
            "BJ" => "KG",
            "BM" => "LB",
            "BN" => "KG",
            "BO" => "KG",
            "BR" => "KG",
            "BS" => "LB",
            "BT" => "KG",
            "BW" => "KG",
            "BY" => "KG",
            "BZ" => "KG",
            "CA" => "LB",
            "CD" => "KG",
            "CF" => "KG",
            "CG" => "KG",
            "CH" => "KG",
            "CI" => "KG",
            "CK" => "KG",
            "CL" => "KG",
            "CM" => "KG",
            "CN" => "KG",
            "CO" => "KG",
            "CR" => "KG",
            "CU" => "KG",
            "CV" => "KG",
            "CY" => "KG",
            "CZ" => "KG",
            "DE" => "KG",
            "DJ" => "KG",
            "DK" => "KG",
            "DM" => "LB",
            "DO" => "LB",
            "DZ" => "KG",
            "EC" => "KG",
            "EE" => "KG",
            "EG" => "KG",
            "ER" => "KG",
            "ES" => "KG",
            "ET" => "KG",
            "FI" => "KG",
            "FJ" => "KG",
            "FK" => "KG",
            "FM" => "LB",
            "FO" => "KG",
            "FR" => "KG",
            "GA" => "KG",
            "GB" => "KG",
            "GD" => "LB",
            "GE" => "KG",
            "GF" => "KG",
            "GG" => "KG",
            "GH" => "KG",
            "GI" => "KG",
            "GL" => "KG",
            "GM" => "KG",
            "GN" => "KG",
            "GP" => "KG",
            "GQ" => "KG",
            "GR" => "KG",
            "GT" => "KG",
            "GU" => "LB",
            "GW" => "KG",
            "GY" => "LB",
            "HK" => "KG",
            "HN" => "KG",
            "HR" => "KG",
            "HT" => "LB",
            "HU" => "KG",
            "IC" => "KG",
            "ID" => "KG",
            "IE" => "KG",
            "IL" => "KG",
            "IN" => "KG",
            "IQ" => "KG",
            "IR" => "KG",
            "IS" => "KG",
            "IT" => "KG",
            "JE" => "KG",
            "JM" => "KG",
            "JO" => "KG",
            "JP" => "KG",
            "KE" => "KG",
            "KG" => "KG",
            "KH" => "KG",
            "KI" => "KG",
            "KM" => "KG",
            "KN" => "LB",
            "KP" => "KG",
            "KR" => "KG",
            "KV" => "KG",
            "KW" => "KG",
            "KY" => "LB",
            "KZ" => "KG",
            "LA" => "KG",
            "LB" => "KG",
            "LC" => "LB",
            "LI" => "KG",
            "LK" => "KG",
            "LR" => "KG",
            "LS" => "KG",
            "LT" => "KG",
            "LU" => "KG",
            "LV" => "KG",
            "LY" => "KG",
            "MA" => "KG",
            "MC" => "KG",
            "MD" => "KG",
            "ME" => "KG",
            "MG" => "KG",
            "MH" => "LB",
            "MK" => "KG",
            "ML" => "KG",
            "MM" => "KG",
            "MN" => "KG",
            "MO" => "KG",
            "MP" => "LB",
            "MQ" => "KG",
            "MR" => "KG",
            "MS" => "LB",
            "MT" => "KG",
            "MU" => "KG",
            "MV" => "KG",
            "MW" => "KG",
            "MX" => "KG",
            "MY" => "KG",
            "MZ" => "KG",
            "NA" => "KG",
            "NC" => "KG",
            "NE" => "KG",
            "NG" => "KG",
            "NI" => "KG",
            "NL" => "KG",
            "NO" => "KG",
            "NP" => "KG",
            "NR" => "KG",
            "NU" => "KG",
            "NZ" => "KG",
            "OM" => "KG",
            "PA" => "KG",
            "PE" => "KG",
            "PF" => "KG",
            "PG" => "KG",
            "PH" => "KG",
            "PK" => "KG",
            "PL" => "KG",
            "PR" => "LB",
            "PT" => "KG",
            "PW" => "KG",
            "PY" => "KG",
            "QA" => "KG",
            "RE" => "KG",
            "RO" => "KG",
            "RS" => "KG",
            "RU" => "KG",
            "RW" => "KG",
            "SA" => "KG",
            "SB" => "KG",
            "SC" => "KG",
            "SD" => "KG",
            "SE" => "KG",
            "SG" => "KG",
            "SH" => "KG",
            "SI" => "KG",
            "SK" => "KG",
            "SL" => "KG",
            "SM" => "KG",
            "SN" => "KG",
            "SO" => "KG",
            "SR" => "KG",
            "SS" => "KG",
            "ST" => "KG",
            "SV" => "KG",
            "SY" => "KG",
            "SZ" => "KG",
            "TC" => "LB",
            "TD" => "KG",
            "TG" => "KG",
            "TH" => "KG",
            "TJ" => "KG",
            "TL" => "KG",
            "TN" => "KG",
            "TO" => "KG",
            "TR" => "KG",
            "TT" => "LB",
            "TV" => "KG",
            "TW" => "KG",
            "TZ" => "KG",
            "UA" => "KG",
            "UG" => "KG",
            "US" => "LB",
            "UY" => "KG",
            "UZ" => "KG",
            "VC" => "LB",
            "VE" => "KG",
            "VG" => "LB",
            "VI" => "LB",
            "VN" => "KG",
            "VU" => "KG",
            "WS" => "KG",
            "XB" => "LB",
            "XC" => "LB",
            "XE" => "LB",
            "XM" => "LB",
            "XN" => "LB",
            "XS" => "KG",
            "XY" => "LB",
            "YE" => "KG",
            "YT" => "KG",
            "ZA" => "KG",
            "ZM" => "KG",
            "ZW" => "KG",
        );
        $response = isset($c[$countryCode]) ? $c[$countryCode] : 'KG';
        return $response;
    }

    public function getDimensionUnitByCountry($countryCode)
    {
        $c = array(
            "AD" => "CM",
            "AE" => "CM",
            "AF" => "CM",
            "AG" => "IN",
            "AI" => "IN",
            "AL" => "CM",
            "AM" => "CM",
            "AN" => "CM",
            "AO" => "CM",
            "AR" => "CM",
            "AS" => "IN",
            "AT" => "CM",
            "AU" => "CM",
            "AW" => "IN",
            "AZ" => "CM",
            "BA" => "CM",
            "BB" => "IN",
            "BD" => "CM",
            "BE" => "CM",
            "BF" => "CM",
            "BG" => "CM",
            "BH" => "CM",
            "BI" => "CM",
            "BJ" => "CM",
            "BM" => "IN",
            "BN" => "CM",
            "BO" => "CM",
            "BR" => "CM",
            "BS" => "IN",
            "BT" => "CM",
            "BW" => "CM",
            "BY" => "CM",
            "BZ" => "CM",
            "CA" => "IN",
            "CD" => "CM",
            "CF" => "CM",
            "CG" => "CM",
            "CH" => "CM",
            "CI" => "CM",
            "CK" => "CM",
            "CL" => "CM",
            "CM" => "CM",
            "CN" => "CM",
            "CO" => "CM",
            "CR" => "CM",
            "CU" => "CM",
            "CV" => "CM",
            "CY" => "CM",
            "CZ" => "CM",
            "DE" => "CM",
            "DJ" => "CM",
            "DK" => "CM",
            "DM" => "IN",
            "DO" => "IN",
            "DZ" => "CM",
            "EC" => "CM",
            "EE" => "CM",
            "EG" => "CM",
            "ER" => "CM",
            "ES" => "CM",
            "ET" => "CM",
            "FI" => "CM",
            "FJ" => "CM",
            "FK" => "CM",
            "FM" => "IN",
            "FO" => "CM",
            "FR" => "CM",
            "GA" => "CM",
            "GB" => "CM",
            "GD" => "IN",
            "GE" => "CM",
            "GF" => "CM",
            "GG" => "CM",
            "GH" => "CM",
            "GI" => "CM",
            "GL" => "CM",
            "GM" => "CM",
            "GN" => "CM",
            "GP" => "CM",
            "GQ" => "CM",
            "GR" => "CM",
            "GT" => "CM",
            "GU" => "IN",
            "GW" => "CM",
            "GY" => "IN",
            "HK" => "CM",
            "HN" => "CM",
            "HR" => "CM",
            "HT" => "IN",
            "HU" => "CM",
            "IC" => "CM",
            "ID" => "CM",
            "IE" => "CM",
            "IL" => "CM",
            "IN" => "CM",
            "IQ" => "CM",
            "IR" => "CM",
            "IS" => "CM",
            "IT" => "CM",
            "JE" => "CM",
            "JM" => "CM",
            "JO" => "CM",
            "JP" => "CM",
            "KE" => "CM",
            "KG" => "CM",
            "KH" => "CM",
            "KI" => "CM",
            "KM" => "CM",
            "KN" => "IN",
            "KP" => "CM",
            "KR" => "CM",
            "KV" => "CM",
            "KW" => "CM",
            "KY" => "IN",
            "KZ" => "CM",
            "LA" => "CM",
            "LB" => "CM",
            "LC" => "IN",
            "LI" => "CM",
            "LK" => "CM",
            "LR" => "CM",
            "LS" => "CM",
            "LT" => "CM",
            "LU" => "CM",
            "LV" => "CM",
            "LY" => "CM",
            "MA" => "CM",
            "MC" => "CM",
            "MD" => "CM",
            "ME" => "CM",
            "MG" => "CM",
            "MH" => "IN",
            "MK" => "CM",
            "ML" => "CM",
            "MM" => "CM",
            "MN" => "CM",
            "MO" => "CM",
            "MP" => "IN",
            "MQ" => "CM",
            "MR" => "CM",
            "MS" => "IN",
            "MT" => "CM",
            "MU" => "CM",
            "MV" => "CM",
            "MW" => "CM",
            "MX" => "CM",
            "MY" => "CM",
            "MZ" => "CM",
            "NA" => "CM",
            "NC" => "CM",
            "NE" => "CM",
            "NG" => "CM",
            "NI" => "CM",
            "NL" => "CM",
            "NO" => "CM",
            "NP" => "CM",
            "NR" => "CM",
            "NU" => "CM",
            "NZ" => "CM",
            "OM" => "CM",
            "PA" => "CM",
            "PE" => "CM",
            "PF" => "CM",
            "PG" => "CM",
            "PH" => "CM",
            "PK" => "CM",
            "PL" => "CM",
            "PR" => "IN",
            "PT" => "CM",
            "PW" => "CM",
            "PY" => "CM",
            "QA" => "CM",
            "RE" => "CM",
            "RO" => "CM",
            "RS" => "CM",
            "RU" => "CM",
            "RW" => "CM",
            "SA" => "CM",
            "SB" => "CM",
            "SC" => "CM",
            "SD" => "CM",
            "SE" => "CM",
            "SG" => "CM",
            "SH" => "CM",
            "SI" => "CM",
            "SK" => "CM",
            "SL" => "CM",
            "SM" => "CM",
            "SN" => "CM",
            "SO" => "CM",
            "SR" => "CM",
            "SS" => "CM",
            "ST" => "CM",
            "SV" => "CM",
            "SY" => "CM",
            "SZ" => "CM",
            "TC" => "IN",
            "TD" => "CM",
            "TG" => "CM",
            "TH" => "CM",
            "TJ" => "CM",
            "TL" => "CM",
            "TN" => "CM",
            "TO" => "CM",
            "TR" => "CM",
            "TT" => "IN",
            "TV" => "CM",
            "TW" => "CM",
            "TZ" => "CM",
            "UA" => "CM",
            "UG" => "CM",
            "US" => "IN",
            "UY" => "CM",
            "UZ" => "CM",
            "VC" => "IN",
            "VE" => "CM",
            "VG" => "IN",
            "VI" => "IN",
            "VN" => "CM",
            "VU" => "CM",
            "WS" => "CM",
            "XB" => "IN",
            "XC" => "IN",
            "XE" => "IN",
            "XM" => "IN",
            "XN" => "IN",
            "XS" => "CM",
            "XY" => "IN",
            "YE" => "CM",
            "YT" => "CM",
            "ZA" => "CM",
            "ZM" => "CM",
            "ZW" => "CM",
        );
        $response = isset($c[$countryCode]) ? $c[$countryCode] : 'KG';
        return $response;
    }

    /**
     * @ Hassan Ali Shahzad
     * This function will return KSA cities
     */
    public function getKsaCities(){
        $cities = array(
            "" => "",
            "Al Artaweeiyah" => "Al Artaweeiyah",
            "Al Bahah" => "Al Bahah",
            "Al Jafer" => "Al Jafer",
            "Al Jawf" => "Al Jawf",
            "Al Kharj" => "Al Kharj",
            "Al Lith" => "Al Lith",
            "Al Mawain" => "Al Mawain",
            "Al Mikhwah" => "Al Mikhwah",
            "Al Qunfudhah" => "Al Qunfudhah",
            "Al Wajh" => "Al Wajh",
            "Al-Abwa" => "Al-Abwa",
            "Al-Hareeq" => "Al-Hareeq",
            "Al-Khutt" => "Al-Khutt",
            "Al-Mubarraz" => "Al-Mubarraz",
            "Al-Namas" => "Al-Namas",
            "Al-Omran" => "Al-Omran",
            "Al-Oyoon" => "Al-Oyoon",
            "Ar Rass" => "Ar Rass",
            "As Sulayyil" => "As Sulayyil",
            "Az Zaimah" => "Az Zaimah",
            "Badr" => "Badr",
            "Baljurashi" => "Baljurashi",
            "Bareg" => "Bareg",
            "Bisha" => "Bisha",
            "Buq a" => "Buq a",
            "Buraydah" => "Buraydah",
            "Dahaban" => "Dahaban",
            "Dammam" => "Dammam",
            "Dawadmi" => "Dawadmi",
            "Dhahran" => "Dhahran",
            "Dhurma" => "Dhurma",
            "Diriyah" => "Diriyah",
            "Duba" => "Duba",
            "Dumat Al-Jandal" => "Dumat Al-Jandal",
            "Farasan city" => "Farasan city",
            "Gatgat" => "Gatgat",
            "Gerrha" => "Gerrha",
            "Gurayat" => "Gurayat",
            "Habala" => "Habala",
            "Hafr Al-Batin" => "Hafr Al-Batin",
            "Hajrah" => "Hajrah",
            "Haql" => "Haql",
            "Harmah" => "Harmah",
            "Hautat Sudair" => "Hautat Sudair",
            "Hofuf" => "Hofuf",
            "Hotat Bani Tamim" => "Hotat Bani Tamim",
            "Huraymila" => "Huraymila",
            "Jabal Umm al Ru'us" => "Jabal Umm al Ru'us",
            "Jalajil" => "Jalajil",
            "Jeddah" => "Jeddah",
            "Jizan" => "Jizan",
            "Jizan Economic City" => "Jizan Economic City",
            "Jubail" => "Jubail",
            "Khafji" => "Khafji",
            "Khamis Mushayt" => "Khamis Mushayt",
            "Khaybar" => "Khaybar",
            "Khobar" => "Khobar",
            "King Abdullah Economic City" => "King Abdullah Economic City",
            "Layla" => "Layla",
            "Lihyan" => "Lihyan",
            "Mastoorah" => "Mastoorah",
            "Mecca" => "Mecca",
            "Medina" => "Medina",
            "Muzahmiyya" => "Muzahmiyya",
            "Najran" => "Najran",
            "Omloj" => "Omloj",
            "Qadeimah" => "Qadeimah",
            "Qaisumah" => "Qaisumah",
            "Qatif" => "Qatif",
            "Rabigh" => "Rabigh",
            "Rafha" => "Rafha",
            "Ras Tanura" => "Ras Tanura",
            "Riyadh" => "Riyadh",
            "Riyadh Al-Khabra" => "Riyadh Al-Khabra",
            "Rumailah" => "Rumailah",
            "Sabt Al Alaya" => "Sabt Al Alaya",
            "Safwa city" => "Safwa city",
            "Saihat" => "Saihat",
            "Sakakah" => "Sakakah",
            "Shaqraa" => "Shaqraa",
            "Sharurah" => "Sharurah",
            "Shaybah" => "Shaybah",
            "Tabarjal" => "Tabarjal",
            "Tabuk" => "Tabuk",
            "Taif" => "Taif",
            "Tanomah" => "Tanomah",
            "Tarout" => "Tarout",
            "Tayma" => "Tayma",
            "Thadiq" => "Thadiq",
            "Thuqbah" => "Thuqbah",
            "Thuwal" => "Thuwal",
            "Turaif" => "Turaif",
            "Udhailiyah" => "Udhailiyah",
            "Um Al-Sahek" => "Um Al-Sahek",
            "Unaizah" => "Unaizah",
            "Uqair" => "Uqair",
            "Uyun AlJiwa" => "Uyun AlJiwa",
            "Wadi Al-Dawasir" => "Wadi Al-Dawasir",
            "Yanbu" => "Yanbu"
        );
        return $cities;
    }
}
