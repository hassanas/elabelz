<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Shipdesc
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('dhllabel')->__('Customer name + Order Id'), 'value' => '1'),
            array('label' => Mage::helper('dhllabel')->__('Only Customer name'), 'value' => '2'),
            array('label' => Mage::helper('dhllabel')->__('Only Order Id'), 'value' => '3'),
            array('label' => Mage::helper('dhllabel')->__('List of Products'), 'value' => '4'),
            array('label' => Mage::helper('dhllabel')->__('Custom value'), 'value' => '5'),
            array('label' => Mage::helper('dhllabel')->__('nothing'), 'value' => ''),
        );
        return $c;
    }
}