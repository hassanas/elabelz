<?php

    class Progos_Skuvault_Block_Adminhtml_System_Config_Form_ButtonqtysyncSkuBased extends Mage_Adminhtml_Block_System_Config_Form_Field
    {
        /*
         * Set template
         */
        protected function _construct(){
            parent::_construct();
            $this->setTemplate('progos/Skuvault/system/config/buttonqtysyncskubased.phtml');
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
        public function getAjaxCheckUrlSkuBased(){
            return Mage::helper('adminhtml')->getUrl('admin_skuvault/adminhtml_process/updateQuantitySkuBased');
        }

        /**
         * Generate button html
         *
         * @return string
         */
        public function getButtonHtml(){
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'id'        => 'progos_skuvault_buttonqtysyncskubased',
                'label'     => $this->helper('adminhtml')->__('Sync Quantity Sku Based'),
                'onclick'   => 'javascript:syncQtySkuBased(); return false;'
            ));
            return $button->toHtml();
        }
    }