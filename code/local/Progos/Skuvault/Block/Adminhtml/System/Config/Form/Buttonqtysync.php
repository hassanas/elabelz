<?php

    class Progos_Skuvault_Block_Adminhtml_System_Config_Form_Buttonqtysync extends Mage_Adminhtml_Block_System_Config_Form_Field
    {
        /*
         * Set template
         */
        protected function _construct(){
            parent::_construct();
            $this->setTemplate('progos/Skuvault/system/config/buttonqtysync.phtml');
        }

        /**
         * Return element html
         *
         * @param  Varien_Data_Form_Element_Abstract $element
         * @return string
         */
        protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
            return $this->_toHtml();
        }

        /**
         * Return ajax url for button
         *
         * @return string
         */ 
        public function getAjaxCheckUrl(){
            return Mage::helper('adminhtml')->getUrl('admin_skuvault/adminhtml_process/updateQuantity');
        }

        /**
         * Generate button html
         *
         * @return string
         */
        public function getButtonHtml(){
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'id'        => 'progos_skuvault_buttonqtysync',
                'label'     => $this->helper('adminhtml')->__('Sync Quantity'),
                'onclick'   => 'javascript:syncQty(); return false;'
            ));
            return $button->toHtml();
        }
    }