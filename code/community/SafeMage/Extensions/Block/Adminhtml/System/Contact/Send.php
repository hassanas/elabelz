<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Block_Adminhtml_System_Contact_Send extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setScope(false);
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getLayout()
            ->createBlock(
                'safemage_extensions/adminhtml_system_contact_send_button',
                'safemage_extensions_contact_send'
            )
            ->setTemplate('safemage/extensions/system/contact/button.phtml')
            ->setContainerId($element->getContainer()->getHtmlId())
            ->toHtml();
    }
}
