<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


class Neklo_Imagick_Model_System_Config_Source_Resize_Mode
{
    public function toOptionArray()
    {
        $helper = Mage::helper('neklo_imagick');
        return array(
            array('value' => Neklo_Imagick_Helper_Data::RESIZE_MODE_THUMBNAIL, 'label' => $helper->__('Thumbnail')),
            array('value' => Neklo_Imagick_Helper_Data::RESIZE_MODE_ADAPTIVE_RESIZE, 'label' => $helper->__('Adaptive Resize')),
        );
    }
}