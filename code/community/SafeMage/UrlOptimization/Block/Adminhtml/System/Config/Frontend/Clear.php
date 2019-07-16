<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_UrlOptimization_Block_Adminhtml_System_Config_Frontend_Clear extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $element->setScope(false);
        $clearUrl = Mage::helper('adminhtml')->getUrl('adminhtml/urloptimization_index/clear');
        $actionUrl = Mage::helper('adminhtml')
            ->getUrl('adminhtml/urloptimization_index/save', array('section' => 'safemage_urloptimization'));
        $process = $this->getRequest()->getParam('process', 0);

        return $this->_getAddClearButtonHtml($process) .
            '<script type="text/javascript">
                function urloptimizationClear(button) {
                    setClearButtonText(button, "' . $this->__('Wait...') . '");' .
                    ($process == SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_WORKING ?
                        'window.location.href="' . $clearUrl . '";' :
                        '$("config_edit_form").action = "' . $actionUrl . '";
                        if (!configForm.submit()) {
                            setClearButtonText(button, "' . $this->__('Clear') . '");
                        };'
                    ) .
                '}' .
                'function setClearButtonText(button, text) {
                    var elSpan = button.down("span");
                    if (elSpan.down("span")) {
                        elSpan = elSpan.down("span");
                    }
                    elSpan.innerHTML = text;
                }' .
                ($process == SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_WORKING ?
                    '$("urloptimization_clear").click();' :
                    ''
                ) .
                ($process == SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_DONE ?
                    'setTimeout(
                        "setClearButtonText($(\"urloptimization_clear\"), \"' . $this->__('Clear') . '\");",
                        15000
                    );' :
                    ''
                ) .
            '</script>';
    }

    protected function _getAddClearButtonHtml($process) {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setId('urloptimization_clear')
                ->setLabel(
                    $process == SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_DONE ?
                    $this->__('Done') :
                    $this->__('Clear')
                )
                ->setOnClick('urloptimizationClear(this)')
                ->toHtml();
    }
}
