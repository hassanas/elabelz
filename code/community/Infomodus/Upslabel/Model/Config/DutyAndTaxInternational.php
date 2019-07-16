<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_DutyAndTaxInternational
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => Mage::helper('upslabel')->__('shipper pays transportation fees and receiver pays duties and taxes'), 'value' => 'customer'),
            array('label' => Mage::helper('upslabel')->__('shipper pays both transportation fees and duties and taxes'), 'value' => 'shipper'),
        );
        return $c;
    }
}