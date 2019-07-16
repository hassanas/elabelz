<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Model_System_Config_Source_Enabled
{
    public function toOptionArray()
    {
        $helper = Mage::helper('safemage_extensions');
        return array(
            array('value' => 0, 'label' => $helper->__('Disabled')),
            array('value' => 1, 'label' => $helper->__('Enabled'))
        );
    }
}
