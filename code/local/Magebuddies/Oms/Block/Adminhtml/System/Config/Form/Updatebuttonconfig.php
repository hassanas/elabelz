<?php

    class Magebuddies_Oms_Block_Adminhtml_System_Config_Form_Updatebuttonconfig extends Mage_Adminhtml_Block_System_Config_Form_Field
    {
        /*
         * Set template
         */
        protected function _construct(){
            parent::_construct();
            $this->setTemplate('progos/Oos/system/config/updatebuttonconfig.phtml');
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
            return Mage::helper('adminhtml')->getUrl('admin_oos/adminhtml_process/removeconfig');
        }

        /**
         * Generate button html
         *
         * @return string
         */
        public function getButtonHtml(){
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'id'        => 'progos_removeconfig_button',
                'label'     => $this->helper('adminhtml')->__('Run'),
                'onclick'   => 'javascript:removeconfig(); return false;'
            ));
            return $button->toHtml();
        }
    }