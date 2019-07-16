<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


class Neklo_Imagick_Helper_Data extends Mage_Core_Helper_Abstract
{
    const RESIZE_MODE_THUMBNAIL = 1;
    const RESIZE_MODE_ADAPTIVE_RESIZE = 2;

    public function isEnabled()
    {
        if (Mage::getStoreConfigFlag('neklo_imagick/general/enabled') && $this->isImagickExtensionLoaded()) {
            return true;
        }
        return false;
    }

    public function isImagickExtensionLoaded()
    {
        return extension_loaded('imagick');
    }

    public function getResizeMode()
    {
        return Mage::getStoreConfig('neklo_imagick/general/resize_mode');
    }

    public function getQuality()
    {
        return intval(Mage::getStoreConfig('neklo_imagick/general/quality'));
    }

    public function cleanImagesCache()
    {
        try {
            Mage::getModel('catalog/product_image')->clearCache();
            Mage::dispatchEvent('clean_catalog_images_cache_after');
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}