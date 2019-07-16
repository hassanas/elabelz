<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Pickup_Containercode
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Choose', 'value' => ""),
            array('label' => Mage::helper('adminhtml')->__('PACKAGE'), 'value' => '01'),
            array('label' => Mage::helper('adminhtml')->__('DHL LETTER'), 'value' => '02'),
            array('label' => Mage::helper('adminhtml')->__('PALLET'), 'value' => '03'),
        );
        return $c;
    }
}