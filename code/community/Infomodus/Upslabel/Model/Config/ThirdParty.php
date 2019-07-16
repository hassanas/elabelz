<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_ThirdParty
{
    public function toOptionArray()
    {
        $c = array(array('value'=>0, 'label'=>"Shipper"));
        $upsAcctModel = Mage::getModel('upslabel/account')->getCollection();
        if(count($upsAcctModel) > 0) {
            foreach ($upsAcctModel AS $u1) {
                $c[] = array('value' => $u1->getId(), 'label' => $u1->getCompanyname());
            }
        }
        return $c;
    }
}