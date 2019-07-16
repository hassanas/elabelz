<?php

/**
 * @category       Progos
 * @package        Progos_Emapi
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           27-04-2018 12:02
 */
class Progos_Emapi_Block_Adminhtml_Appfilters extends Mage_Adminhtml_Block_Template
{
    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/appfilters/warmup',array('form_key' => Mage::getSingleton('core/session')->getFormKey()));
    }

}