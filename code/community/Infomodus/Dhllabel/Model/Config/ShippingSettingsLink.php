<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 01.10.14
 * Time: 11:55
 */
class Infomodus_Dhllabel_Model_Config_ShippingSettingsLink
{
    public function getCommentText()
    {
        return '<a href="'.Mage::helper("adminhtml")->getUrl("adminhtml/dhllabel_conformity/index").'" target="_blank">'.Mage::helper('adminhtml')->__("Settings").'</a>';
    }
}