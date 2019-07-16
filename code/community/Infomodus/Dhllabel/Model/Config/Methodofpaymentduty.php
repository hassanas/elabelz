<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Methodofpaymentduty
{
    public function toOptionArray()
    {
        /*return array(
          array('value' => 0, 'label' => 'First item'),
        );*/
        $c = array(
            array('label' => 'Shipper', 'value' => 'S'),
            array('label' => 'Recipient', 'value' => 'R'),
        );
        $upsAcctModel = Mage::getModel('dhllabel/account')->getCollection();
        if (count($upsAcctModel) > 0) {
            foreach ($upsAcctModel AS $u1) {
                $c[] = array('label' => $u1->getCompanyname(), 'value' => $u1->getId());
            }
        }
        return $c;
    }
}