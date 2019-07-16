<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_CodFundsCode
{
    public function toOptionArray()
    {
        $c = array(
            array('value' => '0', 'label' => Mage::helper('upslabel')->__('Check, Cash Cashier\'s Check Money Order')),
            array('value' => '1', 'label' => Mage::helper('upslabel')->__('Cash')),
            array('value' => '8', 'label' => Mage::helper('upslabel')->__('Cashier’s Check Money Order')),
            array('value' => '9', 'label' => Mage::helper('upslabel')->__('Personal Check')),
        );
        return $c;
    }
}