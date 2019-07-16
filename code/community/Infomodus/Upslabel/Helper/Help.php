<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.02.12
 * Time: 16:07
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Helper_Help extends Mage_Core_Helper_Abstract
{
    public $error = true;
    private $ch;
    public $testing = false;

    static public function escapeXML($string)
    {
        $string = preg_replace('/&/is', '&amp;', $string);
        $string = preg_replace('/</is', '&lt;', $string);
        $string = preg_replace('/>/is', '&gt;', $string);
        $string = preg_replace('/\'/is', '&#39;', $string);
        $string = preg_replace('/"/is', '&quot;', $string);
        $string = str_replace(
            array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż',
                'ü', 'ò', 'è', 'à', 'ì', 'é', 'ô', 'Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', 'ß',
                'À', 'Á', 'Â', 'Ã', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò',
                'Ô', 'Õ', 'Ù', 'Ú', 'Û', 'Ý', 'Þ', 'á', 'â', 'ã', 'å', 'æ', 'ç', 'ê',
                'ë', 'í', 'î', 'ï', 'ð', 'ñ', 'õ', 'ù', 'ú', 'û', 'ý', 'þ', 'ÿ', 'Œ', 'œ', 'Š', 'š', 'Ÿ',
                'Ø', 'ø',
            ),
            array('&#261;', '&#263;', '&#281;', '&#322;', '&#324;', '&#243;', '&#347;', '&#378;', '&#380;', '&#260;', '&#262;', '&#280;', '&#321;', '&#323;', '&#211;', '&#346;', '&#377;', '&#379;',
                '&#252;', '&#242;', '&#232;', '&#224;', '&#236;', '&#233;', '&#244;', '&#196;', '&#228;', '&#220;', '&#252;', '&#214;', '&#246;', '&#223;',
                '&#192;', '&#193;', '&#194;', '&#195;', '&#197;', '&#198;', '&#199;', '&#200;', '&#201;', '&#202;', '&#203;', '&#204;', '&#205;', '&#206;', '&#207;', '&#208;', '&#209;', '&#210;',
                '&#212;', '&#213;', '&#217;', '&#218;', '&#219;', '&#221;', '&#222;', '&#225;', '&#226;', '&#227;', '&#229;', '&#230;', '&#231;', '&#234;',
                '&#235;', '&#237;', '&#238;', '&#239;', '&#240;', '&#241;', '&#245;', '&#249;', '&#250;', '&#251;', '&#253;', '&#254;', '&#255;', '&#338;', '&#339;', '&#352;', '&#353;', '&#376;',
                '&#216;', '&#248;',
            ),
            $string
        );
        return $string/*mb_encode_numericentity($string, array(0x80, 0xff, 0, 0xff))*/
            ;
    }

    static public function escapePhone($phone)
    {
        return str_replace(array(" ", "+", "-"), array("", "", ""), $phone);
    }


    public function curlSend($url, $data = NULL)
    {
        $this->error = true;
        $result = $this->curlSetOption($url, $data);
        $ch = $this->ch;
        if ($result) {
            $result1 = $result;
            $result = strstr($result, '<?xml');
            if ($result === FALSE) {
                $result = $result1;
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
            Mage::log('Curl error: ' . curl_errno($ch) . ' - ' . curl_error($ch));
            curl_close($ch);
            $this->error = true;
            return array('errordesc' => 'Server Error (cUrl)', 'error' => $error);
        }
    }

    public function curlSetOption($url, $data = NULL)
    {
        /*$sslV = curl_version();*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        /*if ($data != NULL) {
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
        if ($data !== NULL) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Cookie: WEMEnabled=Y'
            ));
        }
        $this->ch = $ch;
        return curl_exec($ch);
    }

    public function sendPrint($data, $storeId = NULL)
    {
        $ip = trim(Mage::getStoreConfig('upslabel/printing/automatic_printing_ip'));
        $port = trim(Mage::getStoreConfig('upslabel/printing/automatic_printing_port'));
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

    

    public function createMediaFolders()
    {
        $baseMediaDir = Mage::getBaseDir('media');

        $path_upsdir = $baseMediaDir . '/upslabel';
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
        }
        $path_upsdir = $baseMediaDir . '/upslabel/label';
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
        }
        $path_upsdir = $baseMediaDir . '/upslabel/test_xml';
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
        }

        if (is_dir($path_upsdir)) {
            if (!file_exists($path_upsdir . "/.htaccess")) {
                file_put_contents($path_upsdir . "/.htaccess", "deny from all");
            }
        }
    }

    public function getUpsCode($code)
    {
        $codes = array(
            '1DM' => '14',
            '1DA' => '01',
            '1DP' => '13',
            '2DM' => '59',
            '2DA' => '02',
            '3DS' => '12',
            'GND' => '03',
            'EP' => '54',
            'XDM' => '54',
            'XPD' => '08',
            'XPR' => '07',
            'ES' => '07',
            'SV' => '65',
            'EX' => '08',
            'ST' => '11',
            'ND' => '07',
            'WXS' => '65',
            '21' => '54',
            '01' => '07',
            '05' => '08',
            '08' => '11',
            '18' => array('86', '65'),
            '10' => array('85', '07'),
            '22' => '85',
        );
        return isset($codes[$code]) ? $codes[$code] : null;
    }

    public function getWeightUnitByCountry($countryCode)
    {
        $c = array(
            'AD' => 'KGS',
            'AE' => 'KGS',
            'AF' => 'LBS',
            'AG' => 'KGS',
            'AI' => 'KGS',
            'AL' => 'KGS',
            'AM' => 'KGS',
            'AN' => 'KGS',
            'AO' => 'LBS',
            'AR' => 'KGS',
            'AS' => 'KGS',
            'AT' => 'LBS',
            'AU' => 'KGS',
            'AW' => 'KGS',
            'AZ' => 'LBS',
            'BA' => 'KGS',
            'BB' => 'KGS',
            'BD' => 'KGS',
            'BE' => 'KGS',
            'BF' => 'KGS',
            'BG' => 'KGS',
            'BH' => 'KGS',
            'BI' => 'LBS',
            'BJ' => 'KGS',
            'BM' => 'KGS',
            'BN' => 'LBS',
            'BO' => 'KGS',
            'BR' => 'KGS',
            'BS' => 'KGS',
            'BT' => 'LBS',
            'BW' => 'KGS',
            'BY' => 'KGS',
            'BZ' => 'KGS',
            'CA' => 'KGS',
            'CD' => 'KGS',
            'CF' => 'KGS',
            'CG' => 'KGS',
            'CH' => 'KGS',
            'CI' => 'KGS',
            'CK' => 'KGS',
            'CL' => 'KGS',
            'CM' => 'KGS',
            'CN' => 'KGS',
            'CO' => 'KGS',
            'CR' => 'LBS',
            'CU' => 'LBS',
            'CV' => 'KGS',
            'CY' => 'KGS',
            'CZ' => 'KGS',
            'DE' => 'KGS',
            'DJ' => 'KGS',
            'DK' => 'KGS',
            'DM' => 'LBS',
            'DO' => 'KGS',
            'DZ' => 'KGS',
            'EC' => 'KGS',
            'EE' => 'LBS',
            'EG' => 'KGS',
            'ER' => 'KGS',
            'ES' => 'KGS',
            'ET' => 'KGS',
            'FI' => 'KGS',
            'FJ' => 'KGS',
            'FK' => 'KGS',
            'FM' => 'KGS',
            'FO' => 'KGS',
            'FR' => 'KGS',
            'GA' => 'LBS',
            'GB' => 'KGS',
            'GD' => 'LBS',
            'GE' => 'KGS',
            'GF' => 'KGS',
            'GG' => 'KGS',
            'GH' => 'LBS',
            'GI' => 'KGS',
            'GL' => 'KGS',
            'GM' => 'KGS',
            'GN' => 'KGS',
            'GP' => 'KGS',
            'GQ' => 'KGS',
            'GR' => 'KGS',
            'GT' => 'KGS',
            'GU' => 'KGS',
            'GW' => 'KGS',
            'GY' => 'KGS',
            'HK' => 'KGS',
            'HN' => 'KGS',
            'HR' => 'LBS',
            'HT' => 'KGS',
            'HU' => 'KGS',
            'IC' => 'LBS',
            'ID' => 'KGS',
            'IE' => 'KGS',
            'IL' => 'LBS',
            'IN' => 'KGS',
            'IQ' => 'KGS',
            'IR' => 'KGS',
            'IS' => 'KGS',
            'IT' => 'KGS',
            'JE' => 'KGS',
            'JM' => 'KGS',
            'JO' => 'KGS',
            'JP' => 'KGS',
            'KE' => 'LBS',
            'KGS' => 'KGS',
            'KH' => 'KGS',
            'KI' => 'KGS',
            'KM' => 'KGS',
            'KN' => 'KGS',
            'KP' => 'KGS',
            'KR' => 'LBS',
            'KV' => 'KGS',
            'KW' => 'KGS',
            'KY' => 'KGS',
            'KZ' => 'KGS',
            'LA' => 'KGS',
            'LBS' => 'KGS',
            'LC' => 'KGS',
            'LI' => 'KGS',
            'LK' => 'KGS',
            'LR' => 'KGS',
            'LS' => 'KGS',
            'LT' => 'KGS',
            'LU' => 'KGS',
            'LV' => 'KGS',
            'LY' => 'KGS',
            'MA' => 'KGS',
            'MC' => 'KGS',
            'MD' => 'KGS',
            'ME' => 'KGS',
            'MG' => 'KGS',
            'MH' => 'KGS',
            'MK' => 'KGS',
            'ML' => 'KGS',
            'MM' => 'KGS',
            'MN' => 'LBS',
            'MO' => 'KGS',
            'MP' => 'KGS',
            'MQ' => 'KGS',
            'MR' => 'KGS',
            'MS' => 'KGS',
            'MT' => 'KGS',
            'MU' => 'KGS',
            'MV' => 'KGS',
            'MW' => 'KGS',
            'MX' => 'KGS',
            'MY' => 'KGS',
            'MZ' => 'KGS',
            'NA' => 'KGS',
            'NC' => 'KGS',
            'NE' => 'KGS',
            'NG' => 'KGS',
            'NI' => 'KGS',
            'NL' => 'KGS',
            'NO' => 'KGS',
            'NP' => 'KGS',
            'NR' => 'KGS',
            'NU' => 'KGS',
            'NZ' => 'KGS',
            'OM' => 'KGS',
            'PA' => 'KGS',
            'PE' => 'KGS',
            'PF' => 'LBS',
            'PG' => 'KGS',
            'PH' => 'KGS',
            'PK' => 'KGS',
            'PL' => 'KGS',
            'PR' => 'KGS',
            'PT' => 'KGS',
            'PW' => 'KGS',
            'PY' => 'LBS',
            'QA' => 'KGS',
            'RE' => 'KGS',
            'RO' => 'KGS',
            'RS' => 'KGS',
            'RU' => 'KGS',
            'RW' => 'LBS',
            'SA' => 'KGS',
            'SB' => 'KGS',
            'SC' => 'LBS',
            'SD' => 'KGS',
            'SE' => 'LBS',
            'SG' => 'LBS',
            'SH' => 'KGS',
            'SI' => 'KGS',
            'SK' => 'KGS',
            'SL' => 'LBS',
            'SM' => 'LBS',
            'SN' => 'LBS',
            'SO' => 'LBS',
            'SR' => 'LBS',
            'SS' => 'KGS',
            'ST' => 'LBS',
            'SV' => 'KGS',
            'SY' => 'KGS',
            'SZ' => 'KGS',
            'TC' => 'KGS',
            'TD' => 'KGS',
            'TG' => 'KGS',
            'TH' => 'KGS',
            'TJ' => 'KGS',
            'TL' => 'KGS',
            'TN' => 'KGS',
            'TO' => 'KGS',
            'TR' => 'KGS',
            'TT' => 'KGS',
            'TV' => 'KGS',
            'TW' => 'KGS',
            'TZ' => 'KGS',
            'UA' => 'KGS',
            'UG' => 'KGS',
            'US' => 'KGS',
            'UY' => 'KGS',
            'UZ' => 'KGS',
            'VC' => 'KGS',
            'VE' => 'KGS',
            'VG' => 'KGS',
            'VI' => 'KGS',
            'VN' => 'KGS',
            'VU' => 'KGS',
            'WS' => 'KGS',
            'XB' => 'KGS',
            'XC' => 'KGS',
            'XE' => 'KGS',
            'XM' => 'KGS',
            'XN' => 'KGS',
            'XS' => 'KGS',
            'XY' => 'KGS',
            'YE' => 'KGS',
            'YT' => 'KGS',
            'ZA' => 'KGS',
            'ZM' => 'KGS',
            'ZW' => 'KGS',
        );
        $response = isset($c[$countryCode]) ? $c[$countryCode] : 'KG';
        return $response;
    }

    public function getDimensionUnitByCountry($countryCode)
    {
        $c = array(
            'AD' => 'CM',
            'AE' => 'CM',
            'AF' => 'IN',
            'AG' => 'CM',
            'AI' => 'CM',
            'AL' => 'CM',
            'AM' => 'CM',
            'AN' => 'CM',
            'AO' => 'IN',
            'AR' => 'CM',
            'AS' => 'CM',
            'AT' => 'IN',
            'AU' => 'CM',
            'AW' => 'CM',
            'AZ' => 'IN',
            'BA' => 'CM',
            'BB' => 'CM',
            'BD' => 'CM',
            'BE' => 'CM',
            'BF' => 'CM',
            'BG' => 'CM',
            'BH' => 'CM',
            'BI' => 'IN',
            'BJ' => 'CM',
            'BM' => 'CM',
            'BN' => 'IN',
            'BO' => 'CM',
            'BR' => 'CM',
            'BS' => 'CM',
            'BT' => 'IN',
            'BW' => 'CM',
            'BY' => 'CM',
            'BZ' => 'CM',
            'CA' => 'CM',
            'CD' => 'CM',
            'CF' => 'CM',
            'CG' => 'CM',
            'CH' => 'CM',
            'CI' => 'CM',
            'CK' => 'CM',
            'CL' => 'CM',
            'CM' => 'CM',
            'CN' => 'CM',
            'CO' => 'CM',
            'CR' => 'IN',
            'CU' => 'IN',
            'CV' => 'CM',
            'CY' => 'CM',
            'CZ' => 'CM',
            'DE' => 'CM',
            'DJ' => 'CM',
            'DK' => 'CM',
            'DM' => 'IN',
            'DO' => 'CM',
            'DZ' => 'CM',
            'EC' => 'CM',
            'EE' => 'IN',
            'EG' => 'CM',
            'ER' => 'CM',
            'ES' => 'CM',
            'ET' => 'CM',
            'FI' => 'CM',
            'FJ' => 'CM',
            'FK' => 'CM',
            'FM' => 'CM',
            'FO' => 'CM',
            'FR' => 'CM',
            'GA' => 'IN',
            'GB' => 'CM',
            'GD' => 'IN',
            'GE' => 'CM',
            'GF' => 'CM',
            'GG' => 'CM',
            'GH' => 'IN',
            'GI' => 'CM',
            'GL' => 'CM',
            'GM' => 'CM',
            'GN' => 'CM',
            'GP' => 'CM',
            'GQ' => 'CM',
            'GR' => 'CM',
            'GT' => 'CM',
            'GU' => 'CM',
            'GW' => 'CM',
            'GY' => 'CM',
            'HK' => 'CM',
            'HN' => 'CM',
            'HR' => 'IN',
            'HT' => 'CM',
            'HU' => 'CM',
            'IC' => 'IN',
            'ID' => 'CM',
            'IE' => 'CM',
            'IL' => 'IN',
            'IN' => 'CM',
            'IQ' => 'CM',
            'IR' => 'CM',
            'IS' => 'CM',
            'IT' => 'CM',
            'JE' => 'CM',
            'JM' => 'CM',
            'JO' => 'CM',
            'JP' => 'CM',
            'KE' => 'IN',
            'KG' => 'CM',
            'KH' => 'CM',
            'KI' => 'CM',
            'KM' => 'CM',
            'KN' => 'CM',
            'KP' => 'CM',
            'KR' => 'IN',
            'KV' => 'CM',
            'KW' => 'CM',
            'KY' => 'CM',
            'KZ' => 'CM',
            'LA' => 'CM',
            'LB' => 'CM',
            'LC' => 'CM',
            'LI' => 'CM',
            'LK' => 'CM',
            'LR' => 'CM',
            'LS' => 'CM',
            'LT' => 'CM',
            'LU' => 'CM',
            'LV' => 'CM',
            'LY' => 'CM',
            'MA' => 'CM',
            'MC' => 'CM',
            'MD' => 'CM',
            'ME' => 'CM',
            'MG' => 'CM',
            'MH' => 'CM',
            'MK' => 'CM',
            'ML' => 'CM',
            'MM' => 'CM',
            'MN' => 'IN',
            'MO' => 'CM',
            'MP' => 'CM',
            'MQ' => 'CM',
            'MR' => 'CM',
            'MS' => 'CM',
            'MT' => 'CM',
            'MU' => 'CM',
            'MV' => 'CM',
            'MW' => 'CM',
            'MX' => 'CM',
            'MY' => 'CM',
            'MZ' => 'CM',
            'NA' => 'CM',
            'NC' => 'CM',
            'NE' => 'CM',
            'NG' => 'CM',
            'NI' => 'CM',
            'NL' => 'CM',
            'NO' => 'CM',
            'NP' => 'CM',
            'NR' => 'CM',
            'NU' => 'CM',
            'NZ' => 'CM',
            'OM' => 'CM',
            'PA' => 'CM',
            'PE' => 'CM',
            'PF' => 'IN',
            'PG' => 'CM',
            'PH' => 'CM',
            'PK' => 'CM',
            'PL' => 'CM',
            'PR' => 'CM',
            'PT' => 'CM',
            'PW' => 'CM',
            'PY' => 'IN',
            'QA' => 'CM',
            'RE' => 'CM',
            'RO' => 'CM',
            'RS' => 'CM',
            'RU' => 'CM',
            'RW' => 'IN',
            'SA' => 'CM',
            'SB' => 'CM',
            'SC' => 'IN',
            'SD' => 'CM',
            'SE' => 'IN',
            'SG' => 'IN',
            'SH' => 'CM',
            'SI' => 'CM',
            'SK' => 'CM',
            'SL' => 'IN',
            'SM' => 'IN',
            'SN' => 'IN',
            'SO' => 'IN',
            'SR' => 'IN',
            'SS' => 'CM',
            'ST' => 'IN',
            'SV' => 'CM',
            'SY' => 'CM',
            'SZ' => 'CM',
            'TC' => 'CM',
            'TD' => 'CM',
            'TG' => 'CM',
            'TH' => 'CM',
            'TJ' => 'CM',
            'TL' => 'CM',
            'TN' => 'CM',
            'TO' => 'CM',
            'TR' => 'CM',
            'TT' => 'CM',
            'TV' => 'CM',
            'TW' => 'CM',
            'TZ' => 'CM',
            'UA' => 'CM',
            'UG' => 'CM',
            'US' => 'CM',
            'UY' => 'CM',
            'UZ' => 'CM',
            'VC' => 'CM',
            'VE' => 'CM',
            'VG' => 'CM',
            'VI' => 'CM',
            'VN' => 'CM',
            'VU' => 'CM',
            'WS' => 'CM',
            'XB' => 'CM',
            'XC' => 'CM',
            'XE' => 'CM',
            'XM' => 'CM',
            'XN' => 'CM',
            'XS' => 'CM',
            'XY' => 'CM',
            'YE' => 'CM',
            'YT' => 'CM',
            'ZA' => 'CM',
            'ZM' => 'CM',
            'ZW' => 'CM',
        );
        $response = isset($c[$countryCode]) ? $c[$countryCode] : 'KG';
        return $response;
    }
}
