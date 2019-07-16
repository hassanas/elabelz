<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


class Neklo_Imagick_Model_System_Config_Backend_Cleanimages extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        Mage::helper('neklo_imagick')->cleanImagesCache();
    }
}