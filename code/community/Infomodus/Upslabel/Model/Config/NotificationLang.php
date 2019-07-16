<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_NotificationLang
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('upslabel')->__('Default for country'), 'value' => ""),
            array('label' => Mage::helper('upslabel')->__('DAN'), 'value' => "DAN:97"),
            array('label' => Mage::helper('upslabel')->__('DEU'), 'value' => "DEU:97"),
            array('label' => Mage::helper('upslabel')->__('ENG (dialect GB)'), 'value' => "ENG:GB"),
            array('label' => Mage::helper('upslabel')->__('ENG (dialect US)'), 'value' => "ENG:US"),
            array('label' => Mage::helper('upslabel')->__('ENG (dialect CA)'), 'value' => "ENG:CA"),
            array('label' => Mage::helper('upslabel')->__('FIN'), 'value' => "FIN:97"),
            array('label' => Mage::helper('upslabel')->__('FRA'), 'value' => "FRA:97"),
            array('label' => Mage::helper('upslabel')->__('FRA (dialect CA)'), 'value' => "FRA:CA"),
            array('label' => Mage::helper('upslabel')->__('ITA'), 'value' => "ITA:97"),
            array('label' => Mage::helper('upslabel')->__('NLD'), 'value' => "NLD:97"),
            array('label' => Mage::helper('upslabel')->__('POR'), 'value' => "POR:97"),
            array('label' => Mage::helper('upslabel')->__('SPA'), 'value' => "SPA:97"),
            array('label' => Mage::helper('upslabel')->__('SPA (dialect PR)'), 'value' => "SPA:PR"),
            array('label' => Mage::helper('upslabel')->__('SWE'), 'value' => "SWE:97"),
            array('label' => Mage::helper('upslabel')->__('NOR'), 'value' => "NOR:97"),
            array('label' => Mage::helper('upslabel')->__('POL'), 'value' => "POL:97"),
            array('label' => Mage::helper('upslabel')->__('CES'), 'value' => "CES:97"),
            array('label' => Mage::helper('upslabel')->__('ELL'), 'value' => "ELL:97"),
            array('label' => Mage::helper('upslabel')->__('HEB'), 'value' => "HEB:97"),
            array('label' => Mage::helper('upslabel')->__('HUN'), 'value' => "HUN:97"),
            array('label' => Mage::helper('upslabel')->__('RUS'), 'value' => "RUS:97"),
            array('label' => Mage::helper('upslabel')->__('SLK'), 'value' => "SLK:97"),
            array('label' => Mage::helper('upslabel')->__('TUR'), 'value' => "TUR:97"),
            array('label' => Mage::helper('upslabel')->__('VIE'), 'value' => "VIE:97"),
            array('label' => Mage::helper('upslabel')->__('ZHO'), 'value' => "ZHO:97"),
            array('label' => Mage::helper('upslabel')->__('RON'), 'value' => "RON:97"),
        );
        return $c;
    }
}