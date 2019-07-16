<?php

/**
 * This Module will update Products Attribute values against Arabic and english values
 * Attributes  will be provided in CSV file as per pre defined Format
 *
 * @category       Progos
 * @package        Progos_ProductsUpdater
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           15-08-2017 12:04
 */
class Progos_ProductsUpdater_Block_Adminhtml_ProductsUpdaterbackend extends Mage_Adminhtml_Block_Template
{

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/productsupdaterbackend/save',array('form_key' => Mage::getSingleton('core/session')->getFormKey()));
    }


}