<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Defaultdimensionsset
{
    public function toOptionArray()
    {
        /*multistore*/
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore()) > 0) {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        }
        else if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $storeId = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }
        else {
            $storeId = 0;
        }

        /*multistore*/

        $c = array();
        for ($i = 1; $i <= 10; $i++) {
            if (Mage::getStoreConfig('dhllabel/dimansion_' . $i . '/enable', $storeId) == 1) {
                $c[] = array('label' => Mage::helper('adminhtml')
                    ->__(Mage::getStoreConfig('dhllabel/dimansion_' . $i . '/dimansionname'), $storeId), 'value' => $i);
            }
        }

        return $c;
    }
}