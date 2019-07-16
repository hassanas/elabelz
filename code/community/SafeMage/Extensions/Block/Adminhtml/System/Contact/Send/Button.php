<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Block_Adminhtml_System_Contact_Send_Button extends Mage_Adminhtml_Block_Template
{
    /**
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    public function getButton()
    {
        return $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__('Send'))
            ->setStyle("width:280px")
            ->setId('safemage_contact_send');
    }

    public function getButtonHtml()
    {
        return $this->getButton()->toHtml();
    }

    public function getContainerId()
    {
        return parent::getContainerId();
    }
}
