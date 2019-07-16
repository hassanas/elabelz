<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Saroop Chand <saroop.chand@progos.org> 22-02-2018
 * @copyright   Copyright (c) 2018 Progos, Ltd (http://progos.org)
 */
    class Progos_CustomOrderFlags_Block_Adminhtml_System_Config_Form_Customer extends Mage_Adminhtml_Block_System_Config_Form_Field
    {
        /*
         * Set template
         */
        protected function _construct(){
            parent::_construct();
            $this->setTemplate('customorderflags/system/config/button_customer.phtml');
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
            return Mage::helper("adminhtml")->getUrl('*/sales_order/syncCustomerData');
        }

        /**
         * Generate button html
         *
         * @return string
         */
        public function getButtonHtml(){
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'id'        => 'customer_flag_button',
                'label'     => $this->helper('adminhtml')->__('Sync Customer Flag'),
                'onclick'   => 'javascript:customerSyncButton(); return false;'
            ));
            return $button->toHtml();
        }
    }