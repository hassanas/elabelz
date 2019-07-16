<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Model_System_Config_Source_Mode
{
    const REMOVE_ALL_REDIRECTS = 1;
    const REMOVE_ALL_BUT_LASTEST = 2;
    const REMOVE_ALL_BUT_VISITED = 3;

    public function toOptionArray()
    {
        $helper = Mage::helper('safemage_urloptimization');
        return array(
            array('value' => self::REMOVE_ALL_REDIRECTS, 'label' => $helper->__('Remove All Excess Redirects')),
            array(
                'value' => self::REMOVE_ALL_BUT_VISITED,
                'label' => $helper->__('Remove All but Visited from "log_url_info" Table')
            ),
            array('value' => self::REMOVE_ALL_BUT_LASTEST, 'label' => $helper->__('Remove All but Latest'))
        );
    }
}
