<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Block_Adminhtml_System_Extension extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected function _getHeaderHtml($element)
    {
        return parent::_getHeaderHtml($element) . $this->_getContentHtml();
    }

    protected function _getContentHtml()
    {
        return $this->getLayout()
            ->createBlock('safemage_extensions/adminhtml_system_extension_list', 'safemage_extensions_extension_list')
            ->setTemplate('safemage/extensions/system/extension/list.phtml')
            ->toHtml();
    }
}
