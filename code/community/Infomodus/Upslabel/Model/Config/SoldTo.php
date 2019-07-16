<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_SoldTo
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('upslabel')->__('Shipper'), 'value' => 'shipper'),
            array('label' => Mage::helper('upslabel')->__('Customer'), 'value' => 'shipto'),
        );
        return $c;
    }
}