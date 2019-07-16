<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_Upsmethod
{
    public function toOptionArray()
    {
        /*$c = array(
            array('label' => 'UPS Next Day Air', 'value' => '01'),
            array('label' => 'UPS Second Day Air', 'value' => '02'),
            array('label' => 'UPS Ground', 'value' => '03'),
            array('label' => 'UPS Three-Day Select', 'value' => '12'),
            array('label' => 'UPS Next Day Air Saver', 'value' => '13'),
            array('label' => 'UPS Next Day Air Early A.M. SM', 'value' => '14'),
            array('label' => 'UPS Second Day Air A.M.', 'value' => '59'),
            array('label' => 'UPS Saver', 'value' => '65'),
            array('label' => 'UPS Worldwide ExpressSM', 'value' => '07'),
            array('label' => 'UPS Worldwide ExpeditedSM', 'value' => '08'),
            array('label' => 'UPS Standard', 'value' => '11'),
            array('label' => 'UPS Worldwide Express PlusSM', 'value' => '54'),
            array('label' => 'UPS Today StandardSM', 'value' => '82'),
            array('label' => 'UPS Today Dedicated CourrierSM', 'value' => '83'),
            array('label' => 'UPS Today Express', 'value' => '85'),
            array('label' => 'UPS Today Express Saver', 'value' => '86'),
           // array('label' => 'UPS Access Point™ Economy', 'value' => '70'),
        );*/
        $arr = $this->getUpsMethods();
        foreach ($arr as $k => $v) {
            $c[] = array('value' => $k, 'label' => Mage::helper('usa')->__($v));
        }
        return $c;
    }

    public function getUpsMethods()
    {
        /*$c = array(
            '01' => 'UPS Next Day Air',
            '02' => 'UPS Second Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Worldwide ExpressSM',
            '08' => 'UPS Worldwide ExpeditedSM',
            '11' => 'UPS Standard',
            '12' => 'UPS Three-Day Select',
            '13' => 'UPS Next Day Air Saver',
            '14' => 'UPS Next Day Air Early A.M. SM',
            '54' => 'UPS Worldwide Express PlusSM',
            '59' => 'UPS Second Day Air A.M.',
            '65' => 'UPS Saver',
            '82' => 'UPS Today StandardSM',
            '83' => 'UPS Today Dedicated CourrierSM',
            '85' => 'UPS Today Express',
            '86' => 'UPS Today Express Saver',
            //'70' => 'UPS Access Point™ Economy',
        );*/
        
            $originShipment = Mage::getStoreConfig('upslabel/shipping/origin_shipment');
        

        $c = array();
        foreach ($this->getCode('originShipment', $originShipment) as $k => $v) {
            /*$c[] = array('value'=>$k, 'label'=>Mage::helper('usa')->__($v));*/
            $c[$k] = Mage::helper('usa')->__($v);
        }

        return $c;
    }

    public function getUpsMethodName($code = '')
    {
        $c = $this->getUpsMethods();
        if (array_key_exists($code, $c)) {
            return $c[$code];
        } else {
            return false;
        }
    }

    public function getUpsMethodNumber($code = '')
    {
        $sercoD = array(
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
        );

        $sercoD2 = array(
            '14' => '14',
            '1' => '01',
            '13' => '13',
            '59' => '59',
            '2' => '02',
            '12' => '12',
            '3' => '03',
            '54' => '54',
            '65' => '65',
            '8' => '08',
            '11' => '11',
            '7' => '07',
        );
        $code = array_key_exists($code, $sercoD) ? $sercoD[$code] : $code;
        $code = array_key_exists($code, $sercoD2) ? $sercoD2[$code] : $code;

        return $code;
    }

    function getShippingMethods()
    {
        $option = array();
        $_methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        foreach ($_methods as $_carrierCode => $_carrier) {
            if ($_carrierCode !== "ups" && $_carrierCode !== "dhlint" && $_carrierCode !== "usps" && $_carrierCode !== 'upsap' && $_method = $_carrier->getAllowedMethods()) {
                if (!$_title = Mage::getStoreConfig('carriers/' . $_carrierCode . '/title')) {
                    $_title = $_carrierCode;
                }
                foreach ($_method as $_mcode => $_m) {
                    $_code = $_carrierCode . '_' . $_mcode;
                    $option[] = array('label' => "(" . $_title . ")  " . $_m, 'value' => $_code);
                }
            }
        }
        return $option;
    }

    function getShippingMethodsSimple()
    {
        $option = array();
        $_methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        foreach ($_methods as $_carrierCode => $_carrier) {
            if ($_carrierCode !== "ups" && $_carrierCode !== "dhlint" && $_carrierCode !== "usps" && $_carrierCode !== 'upsap' && $_method = $_carrier->getAllowedMethods()) {
                if (!$_title = Mage::getStoreConfig('carriers/' . $_carrierCode . '/title')) {
                    $_title = $_carrierCode;
                }
                foreach ($_method as $_mcode => $_m) {
                    $_code = $_carrierCode . '_' . $_mcode;
                    $option[$_code] = "(" . $_title . ")  " . $_m;
                }
            }
        }
        return $option;
    }

    public function getCode($type, $code = '')
    {
        $codes = array(
            'action' => array(
                'single' => '3',
                'all' => '4',
            ),

            'originShipment' => array(
                // United States Domestic Shipments
                'United States Domestic Shipments' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS 2nd Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    /*'07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),*/
                    '12' => Mage::helper('usa')->__('UPS 3 Day Select'),
                    '13' => Mage::helper('usa')->__('UPS Next Day Air Saver'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early'),
                    /*'54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),*/
                    '59' => Mage::helper('usa')->__('UPS 2nd Day Air A.M.'),
                    /*'65' => Mage::helper('usa')->__('UPS Saver'),*/
                ),
                // Shipments Originating in United States
                'Shipments Originating in United States' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS 2nd Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS 3 Day Select'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '59' => Mage::helper('usa')->__('UPS 2nd Day Air A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Worldwide Saver'),
                ),
                // Shipments Originating in Canada
                'Shipments Originating in Canada' => array(
                    '01' => Mage::helper('usa')->__('UPS Express'),
                    '02' => Mage::helper('usa')->__('UPS 2nd Day Air'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS 3 Day Select'),
                    '13' => Mage::helper('usa')->__('UPS Next Day Air Saver'),
                    '14' => Mage::helper('usa')->__('UPS Express Early'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Express Saver'),
                    '70' => Mage::helper('usa')->__('UPS Access Point Economy'),
                ),
                // Shipments Originating in the European Union
                'Shipments Originating in the European Union' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Worldwide Saver'),
                    '70' => Mage::helper('usa')->__('UPS Access Point Economy'),
                ),
                // Polish Domestic Shipments
                'Polish Domestic Shipments' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Express Saver'),
                    '70' => Mage::helper('usa')->__('UPS Access Point Economy'),
                    '82' => Mage::helper('usa')->__('UPS Today Standard'),
                    '83' => Mage::helper('usa')->__('UPS Today Dedicated Courier'),
                    '85' => Mage::helper('usa')->__('UPS Today Express'),
                    '86' => Mage::helper('usa')->__('UPS Today Express Saver'),
                ),
                // Puerto Rico Origin
                'Puerto Rico Origin' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS 2nd Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Worldwide Saver'),
                ),
                // Shipments Originating in Mexico
                'Shipments Originating in Mexico' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Worldwide Saver'),
                    '70' => Mage::helper('usa')->__('UPS Access Point Economy'),
                ),
                // Shipments Originating in Other Countries
                'Shipments Originating in Other Countries' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Worldwide Saver')
                )
            ),
        );

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }
}