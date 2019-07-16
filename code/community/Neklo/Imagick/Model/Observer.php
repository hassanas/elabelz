<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


class Neklo_Imagick_Model_Observer
{
    public function checkImagickExtension($observer)
    {
        $request = $observer->getControllerAction()->getRequest();
        $currentSection = $request->getParam('section', null);
        if ($currentSection === 'neklo_imagick' && !Mage::helper('neklo_imagick')->isImagickExtensionLoaded()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('neklo_imagick')->__('PHP <a href="%s" target="_blank">Imagick</a> extension is required.', 'https://php.net/manual/book.imagick.php')
            );
        }
    }
}