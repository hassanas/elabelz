<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Block_Adminhtml_Redirect_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    protected function _prepareForm()
    {
        $model  = Mage::registry('current_redirect_model');

        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getData('action'),
            'method'    => 'post',
            'enctype'   => 'multipart/form-data',
        ));

        $fieldset = $form->addFieldset('general_fieldset', array(
            'legend'    => Mage::helper('seo')->__('General'),
            'class'     => 'fieldset-wide'
        ));

        if ($model->getId()) {
            $fieldset->addField('redirect_id', 'hidden', array(
                'name'      => 'redirect_id',
                'value'     => $model->getId(),
            ));
        }

        $fieldset->addField('url_from', 'text', array(
            'name'      => 'url_from',
            'label'     => Mage::helper('seo')->__('Request Url'),
            'required'  => true,
            'value'     => $model->getUrlFrom(),
            'note'      => 'Redirect if user opens this URL. E.g. \'/some/old/page\'.<br/>
                            You can use wildcards. E.g. \'/some/old/category/*\'.',
        ));

        $fieldset->addField('url_to', 'text', array(
            'name'      => 'url_to',
            'label'     => Mage::helper('seo')->__('Target Url'),
            'required'  => true,
            'value'     => $model->getUrlTo(),
            'note'      => 'Redirect to this URL. E.g. \'/some/new/page/\'.',
        ));

        $fieldset->addField('is_redirect_only_error_page', 'checkbox', array(
            'label'     => Mage::helper('seo')->__('Redirect only if request URL can\'t be found (404)'),
            'name'      => 'is_redirect_only_error_page',
            'onclick'   => 'this.value = this.checked ? 1 : 0;',
            'value'     => $model->getIsRedirectOnlyErrorPage(),
            'checked'   => $model->getIsRedirectOnlyErrorPage(),
        ));

        $fieldset->addField('redirect_type', 'select', array(
            'name'      => 'redirect_type',
            'label'     => Mage::helper('seo')->__('Redirect Status Code'),
            'options'   => array('301' => Mage::helper('seo')->__('301 Moved Permanently'),
                                 '302' => Mage::helper('seo')->__('302 Moved Temporarily'),
                                 '307' => Mage::helper('seo')->__('307 Temporary Redirect')
                        ),
            'value'     => $model->getRedirectType(),
        ));

        $fieldset->addField('comments', 'textarea', array(
            'label'     => Mage::helper('seo')->__('Comments'),
            'name'      => 'comments',
            'value'     => $model->getComments()
        ));

        $fieldset->addField('is_active', 'select', array(
            'name'      => 'is_active',
            'label'     => Mage::helper('seo')->__('Status'),
            'options'   => array('1' => Mage::helper('seo')->__('Active'), '0' => Mage::helper('seo')->__('Inactive')),
            'value'     => $model->getIsActive(),
        ));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $field = $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'store_ids[]',
                'label'     => Mage::helper('seo')->__('Visible in Store View'),
                'title'     => Mage::helper('seo')->__('Visible in Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'value'     => $model->getStoreIds()
            ));
        }
        else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'store_ids[]',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
            $model->setStoreId(Mage::app()->getStore(true)->getId());
        }


        $form->setAction($this->getUrl('*/*/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}