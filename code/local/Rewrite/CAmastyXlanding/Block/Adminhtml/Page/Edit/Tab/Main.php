<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Shopby
 */
class Rewrite_CAmastyXlanding_Block_Adminhtml_Page_Edit_Tab_Main
    extends Amasty_Xlanding_Block_Adminhtml_Page_Edit_Tab_Main
{
    /**
     * Rewriting for optimization purposes of amlanding
     */
    protected function _prepareForm()
    {
        /* @var $model Amasty_Xlanding_Model_Page */
        $model = Mage::registry('amlanding_page');

        /* @var $helper Amasty_Xlanding_Helper_Data */
        $helper = Mage::helper('amlanding');
        $attributeSets = $helper->getAvailableAttributeSets();
        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => $helper->__('Page Information')));

        if ($model->getPageId()) {
            $fieldset->addField('page_id', 'hidden', array(
                'name' => 'page_id',
            ));
        }

        $fieldset->addField('title', 'text', array(
            'name' => 'title',
            'label' => $helper->__('Page Name'),
            'title' => $helper->__('Page Name'),
            'required' => true,
        ));

        $fieldset->addField('identifier', 'text', array(
            'name' => 'identifier',
            'label' => $helper->__('URL Key'),
            'title' => $helper->__('URL Key'),
            'required' => true,
            'class' => 'validate-identifier',
            'note' => $helper->__('Relative to Website Base URL'),
        ));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {

            $fieldset->addField('store_id', 'multiselect', array(
                'label' => $helper->__('Stores'),
                'name' => 'stores[]',
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm()
            ));
        } else {
            $fieldset->addField('store_id', 'hidden', array(
                'name' => 'stores[]',
                'value' => Mage::app()->getStore(true)->getId()
            ));
            $model->setStoreId(Mage::app()->getStore(true)->getId());
        }

        $fieldset->addField('is_active', 'select', array(
            'label' => $helper->__('Status'),
            'title' => $helper->__('Page Status'),
            'name' => 'is_active',
            'required' => true,
            'options' => $helper->getAvailableStatuses()
        ));

        /**
         * Adding field in the form to select the default attribute store used for optimization purposes
         */
        $fieldset->addField('default_attribute_set', 'select', array(
            'label' => $helper->__('Default Attribute Set'),
            'title' => $helper->__('Default Attribute Set'),
            'name' => 'default_attribute_set',
            'options' => $attributeSets,
            'required' => true
        ));
        $form->setValues($model->getData());
        $this->setForm($form);

    }
}
