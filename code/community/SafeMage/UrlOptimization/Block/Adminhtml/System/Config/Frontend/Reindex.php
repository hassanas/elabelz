<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Block_Adminhtml_System_Config_Frontend_Reindex extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $element->setScope(false);
        return $this->_getAddClearButtonHtml();
    }

    protected function _getAddClearButtonHtml() {
        $reindexUrl = Mage::helper('adminhtml')->getUrl('adminhtml/urloptimization_index/reindex');
        return $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setLabel($this->__('Reindex'))
                ->setOnClick('setLocation(\'' . $reindexUrl . '\')')
                ->toHtml();
    }
}
