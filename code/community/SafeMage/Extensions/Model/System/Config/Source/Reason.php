<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Model_System_Config_Source_Reason
{
    public function toOptionArray()
    {
        $helper = Mage::helper('safemage_extensions');
        $reasonList = array();
        $reasonList[''] = $helper->__('Please select');
        $reasonList['Magento v' . Mage::getVersion()] = $helper->__('Magento Related Support');
        $reasonList['New Extension'] = $helper->__('Request New Extension Development');

        $moduleList = $helper->getModuleList();
        foreach ($moduleList as $code => $data) {
            $version = ($data['version'] ? ' v' . $data['version'] : '');
            $reasonList[$code . $version] =  $helper->__('%s Support', $data['name'] . $version);
        }

        $reasonList['other'] = $helper->__('Other Reason');
        return $reasonList;
    }
}
