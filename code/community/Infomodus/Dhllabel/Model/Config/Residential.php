<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Residential
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('dhllabel')->__('Choose'), 'value' => ""),
            array('label' => Mage::helper('dhllabel')->__('Residential address'), 'value' => 'Y'),
            array('label' => Mage::helper('dhllabel')->__('Non-residental (Commercial) address'), 'value' => 'N'),
        );
        return $c;
    }
}