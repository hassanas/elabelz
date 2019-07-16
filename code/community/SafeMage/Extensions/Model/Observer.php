<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Model_Observer
{
    public function systemConfigSaveAfter(Varien_Event_Observer $observer)
    {
        $groups = Mage::app()->getRequest()->getPost('groups');
        if (!isset($groups['extension']['fields']['enabled'])) {
            return $this;
        }

        $extensions = $groups['extension']['fields']['enabled'];

        if (count($extensions) == 0) {
            return $this;
        }

        Mage::getModel('safemage_extensions/etc_module')->enable($extensions);
    }

    public function predispatchSystemConfigEdit(Varien_Event_Observer $observer)
    {
        $session = Mage::getSingleton('adminhtml/session');

        if ($newDispatchEvents = $session->getNewDispatchEvents()) {
            foreach($newDispatchEvents as $newDispatchEvent) {
                Mage::dispatchEvent($newDispatchEvent);
            }
            $session->setNewDispatchEvents(null);
        }
    }
}
