<?php

    class Progos_DuplicateOptions_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
    {
        /*
         * Set template
         */
        protected function _construct()
        {
            parent::_construct();
            $this->setTemplate('progos/system/config/button.phtml');
        }

        /**
         * Return element html
         *
         * @param  Varien_Data_Form_Element_Abstract $element
         * @return string
         */
        protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
        {
            return $this->_toHtml();
        }

        /**
         * Return ajax url for button
         *
         * @return string
         */ 
        public function getAjaxCheckUrl()
        {
            return Mage::helper('adminhtml')->getUrl('admin_duplicateoptions/adminhtml_process/remove');
        }

        /**
         * Generate button html
         *
         * @return string
         */
        public function getButtonHtml()
        {
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'id'        => 'progos_button',
                'label'     => $this->helper('adminhtml')->__('Run'),
                'onclick'   => 'javascript:check(); return false;'
            ));

            return $button->toHtml();
        }
    }