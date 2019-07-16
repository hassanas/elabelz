<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Dhlmethod
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('usa')->__('Easy shop') . " " . Mage::helper('usa')->__('(BTC, DOC)'), 'value' => '2'),
            array('label' => Mage::helper('usa')->__('Sprintline') . " " . Mage::helper('usa')->__('(SPL, DOC)'), 'value' => '5'),
            /*array('label' => Mage::helper('usa')->__('Secureline')." ".Mage::helper('usa')->__('(DOC)'), 'value' => '6'),*/
            array('label' => Mage::helper('usa')->__('Express easy') . " " . Mage::helper('usa')->__('(XED, DOC)'), 'value' => '7'),
            array('label' => Mage::helper('usa')->__('Europack') . " " . Mage::helper('usa')->__('(EPA, DOC)'), 'value' => '9'),
            array('label' => Mage::helper('usa')->__('Break bulk express') . " " . Mage::helper('usa')->__('(BBX, DOC)'), 'value' => 'B'),
            array('label' => Mage::helper('usa')->__('Medical express') . " " . Mage::helper('usa')->__('(CMX, DOC)'), 'value' => 'C'),
            array('label' => Mage::helper('usa')->__('Express worldwide') . " " . Mage::helper('usa')->__('(DOX, DOC)'), 'value' => 'D'),
            array('label' => Mage::helper('usa')->__('Express worldwide') . " " . Mage::helper('usa')->__('(ECX, DOC)'), 'value' => 'U'),
            array('label' => Mage::helper('usa')->__('Express 9:00') . " " . Mage::helper('usa')->__('(TDK, DOC)'), 'value' => 'K'),
            array('label' => Mage::helper('usa')->__('Express 10:30') . " " . Mage::helper('usa')->__('(TDL, DOC)'), 'value' => 'L'),
            array('label' => Mage::helper('usa')->__('Domestic economy select') . " " . Mage::helper('usa')->__('(DES, DOC)'), 'value' => 'G'),
            array('label' => Mage::helper('usa')->__('Economy select') . " " . Mage::helper('usa')->__('(ESU, DOC)'), 'value' => 'W'),
            array('label' => Mage::helper('usa')->__('Break bulk economy') . " " . Mage::helper('usa')->__('(DOK, DOC)'), 'value' => 'I'),
            array('label' => Mage::helper('usa')->__('Domestic express') . " " . Mage::helper('usa')->__('(DOM, DOC)'), 'value' => 'N'),
            array('label' => Mage::helper('usa')->__('Domestic express 10:30') . " " . Mage::helper('usa')->__('(DOL, DOC)'), 'value' => 'O'),
            array('label' => Mage::helper('usa')->__('Globalmail business') . " " . Mage::helper('usa')->__('(GMB, DOC)'), 'value' => 'R'),
            array('label' => Mage::helper('usa')->__('Same day') . " " . Mage::helper('usa')->__('(SDX, DOC)'), 'value' => 'S'),
            array('label' => Mage::helper('usa')->__('Express 12:00') . " " . Mage::helper('usa')->__('(TDT, DOC)'), 'value' => 'T'),
            array('label' => Mage::helper('usa')->__('Express envelope') . " " . Mage::helper('usa')->__('(XPD, DOC)'), 'value' => 'X'),

            array('value' => '1', 'label' => Mage::helper('usa')->__('Domestic express 12:00') . " " . Mage::helper('usa')->__('(DOT, NON DOC)')),
            array('value' => '3', 'label' => Mage::helper('usa')->__('Easy shop') . " " . Mage::helper('usa')->__('(B2C, NON DOC)')),
            array('value' => '4', 'label' => Mage::helper('usa')->__('Jetline') . " (" . Mage::helper('usa')->__('(NFO, NON DOC)')),
            array('value' => '8', 'label' => Mage::helper('usa')->__('Express easy') . " " . Mage::helper('usa')->__('(XEP, NON DOC)')),
            array('value' => 'P', 'label' => Mage::helper('usa')->__('Express worldwide') . " " . Mage::helper('usa')->__('(WPX, NON DOC)')),
            array('value' => 'Q', 'label' => Mage::helper('usa')->__('Medical express') . " " . Mage::helper('usa')->__('(WMX, NON DOC)')),
            array('value' => 'E', 'label' => Mage::helper('usa')->__('Express 9:00') . " " . Mage::helper('usa')->__('(TDE, NON DOC)')),
            array('value' => 'F', 'label' => Mage::helper('usa')->__('Freight worldwide') . " " . Mage::helper('usa')->__('(FRT, NON DOC)')),
            array('value' => 'H', 'label' => Mage::helper('usa')->__('Economy select') . " " . Mage::helper('usa')->__('(ESI, NON DOC)')),
            array('value' => 'J', 'label' => Mage::helper('usa')->__('Jumbo box') . " " . Mage::helper('usa')->__('(JBX, NON DOC)')),
            array('value' => 'M', 'label' => Mage::helper('usa')->__('Express 10:30') . " " . Mage::helper('usa')->__('(TDM, NON DOC)')),
            array('value' => 'V', 'label' => Mage::helper('usa')->__('Europack') . " " . Mage::helper('usa')->__('(EPP, NON DOC)')),
            array('value' => 'Y', 'label' => Mage::helper('usa')->__('Express 12:00') . " " . Mage::helper('usa')->__('(TDY, NON DOC)')),
        );
        return $c;
    }

    public function getUpsMethods()
    {
        $c = array();
        $arr = $this->toOptionArray();
        foreach ($arr AS $val) {
            $c[$val['value']] = $val['label'];
        }
        return $c;
    }

    public function getUpsMethodName($code = '')
    {
        $c = array();
        $arr = $this->toOptionArray();
        foreach ($arr AS $key => $val) {
            $c[$val['value']] = $val['label'];
        }
        if (array_key_exists($code, $c)) {
            return $c[$code];
        } else {
            return false;
        }
    }

    function getShippingMethods( /*multistore*/
        $store = null /*multistore*/)
    {
        $option = array();
        $_methods = Mage::getSingleton('shipping/config')->getActiveCarriers( /*multistore*/
            $store /*multistore*/);
        foreach ($_methods as $_carrierCode => $_carrier) {
            if ($_carrierCode !== "ups" && $_carrierCode !== "dhlint" && $_carrierCode !== "usps" && $_method = $_carrier->getAllowedMethods()) {
                if (!$_title = Mage::getStoreConfig('carriers/' . $_carrierCode . '/title', $store)) {
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

    function getShippingMethodsSimple( /*multistore*/
        $store = null /*multistore*/)
    {
        $option = array();
        $_methods = Mage::getSingleton('shipping/config')->getActiveCarriers( /*multistore*/
            $store /*multistore*/);
        foreach ($_methods as $_carrierCode => $_carrier) {
            if ($_carrierCode !== "ups" && $_carrierCode !== "dhlint" && $_carrierCode !== "usps" && $_method = $_carrier->getAllowedMethods()) {
                if (!$_title = Mage::getStoreConfig('carriers/' . $_carrierCode . '/title', $store)) {
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

    public function getContentTypeByMetod($method)
    {
        $docType = array(
            '2' => 1,
            '5' => 1,
            '6' => 1,
            '7' => 1,
            '9' => 1,
            'B' => 1,
            'C' => 1,
            'D' => 1,
            'U' => 1,
            'K' => 1,
            'L' => 1,
            'G' => 1,
            'W' => 1,
            'I' => 1,
            'N' => 1,
            'O' => 1,
            'R' => 1,
            'S' => 1,
            'T' => 1,
            'X' => 1,
        );

        $nonDocType = array(
            '1' => 1,
            '3' => 1,
            '4' => 1,
            '8' => 1,
            'P' => 1,
            'Q' => 1,
            'E' => 1,
            'F' => 1,
            'H' => 1,
            'J' => 1,
            'M' => 1,
            'V' => 1,
            'Y' => 1,
        );

        if(array_key_exists($method, $docType)){
            return "DOC";
        }
        elseif(array_key_exists($method, $nonDocType)){
            return "NONDOC";
        }
        else {
            return false;
        }
    }

}