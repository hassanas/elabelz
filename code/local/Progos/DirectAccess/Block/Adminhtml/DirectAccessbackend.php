<?php
class Progos_DirectAccess_Block_Adminhtml_DirectAccessbackend extends Mage_Adminhtml_Block_Template
{

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/directaccessbackend/save',array('form_key' => Mage::getSingleton('core/session')->getFormKey()));
    }


}