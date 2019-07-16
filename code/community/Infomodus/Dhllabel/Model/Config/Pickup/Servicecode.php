<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Pickup_Servicecode
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('dhllabel')->__('Choose'), 'value' => ""),
            array('label' => Mage::helper('dhllabel')->__('DHL Next Day Air'), 'value' => '001'),
            array('label' => Mage::helper('dhllabel')->__('DHL 2nd Day Air'), 'value' => '002'),
            array('label' => Mage::helper('dhllabel')->__('DHL Ground'), 'value' => '003'),
            array('label' => Mage::helper('dhllabel')->__('DHL Ground, DHL Standard'), 'value' => '004'),
            array('label' => Mage::helper('dhllabel')->__('DHL Worldwide Express'), 'value' => '007'),
            array('label' => Mage::helper('dhllabel')->__('DHL Worldwide Expedited'), 'value' => '008'),
            array('label' => Mage::helper('dhllabel')->__('DHL Standard'), 'value' => '011'),
            array('label' => Mage::helper('dhllabel')->__('DHL Three Day Select'), 'value' => '012'),
            array('label' => Mage::helper('dhllabel')->__('DHL Next Day Air Saver'), 'value' => '013'),
            array('label' => Mage::helper('dhllabel')->__('DHL Next Day Air Early A.M.'), 'value' => '014'),
            array('label' => Mage::helper('dhllabel')->__('DHL Economy'), 'value' => '021'),
            array('label' => Mage::helper('dhllabel')->__('DHL Basic'), 'value' => '031'),
            array('label' => Mage::helper('dhllabel')->__('DHL Worldwide Express Plus'), 'value' => '054'),
            array('label' => Mage::helper('dhllabel')->__('DHL Second Day Air A.M.'), 'value' => '059'),
            array('label' => Mage::helper('dhllabel')->__('DHL Express NA1'), 'value' => '064'),
            array('label' => Mage::helper('dhllabel')->__('DHL Saver'), 'value' => '065'),
            array('label' => Mage::helper('dhllabel')->__('DHL Standard Today'), 'value' => '082'),
            array('label' => Mage::helper('dhllabel')->__('DHL Today Dedicated Courier'), 'value' => '083'),
            array('label' => Mage::helper('dhllabel')->__('DHL Intercity Today'), 'value' => '084'),
            array('label' => Mage::helper('dhllabel')->__('DHL Today Express'), 'value' => '085'),
            array('label' => Mage::helper('dhllabel')->__('DHL Today Express Saver'), 'value' => '086'),
            array('label' => Mage::helper('dhllabel')->__('DHL Worldwide Express Freight'), 'value' => '096'),
        );
        return $c;
    }
}