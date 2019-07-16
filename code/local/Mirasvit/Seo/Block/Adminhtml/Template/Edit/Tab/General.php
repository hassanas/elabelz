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


class Mirasvit_Seo_Block_Adminhtml_Template_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model  = Mage::registry('current_template_model');
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('template_form', array(
            'legend'    => Mage::helper('seo')->__('General Information'),
        ));

        $fieldset->addField('rule_type', 'select', array(
          'label'     => Mage::helper('seo')->__('Rule type'),
          'required'  => true,
          'name'      => 'rule_type',
          'onchange' => "getSelectedValue(this)",
          'value'  => $model->getRuleType(),
          'values' => array( Mirasvit_Seo_Model_Config::PRODUCTS_RULE                   => 'Products',
                             Mirasvit_Seo_Model_Config::CATEGORIES_RULE                 => 'Categories',
                             Mirasvit_Seo_Model_Config::RESULTS_LAYERED_NAVIGATION_RULE => 'Results of layered navigation' ),
          'disabled' => $model->getId() ? true : false,
        ));

        if ($model->getId()) {
            $fieldset->addField('template_id', 'hidden', array(
                'name'      => 'template_id',
                'value'     => $model->getId(),
            ));
        }

        switch ($model->getRuleType()) {
            case Mirasvit_Seo_Model_Config::PRODUCTS_RULE:
                $note = '<b>Template variables</b><br>
                        [product_&lt;product field or attribute&gt;] (e.g. [product_name], [product_price], [product_color]) <br>
                        [category_name], [category_description], [category_url], [category_parent_name], [category_parent_url], <br>
                        [store_name], [store_url], [store_address], [store_phone], [store_email]';
                break;
            case Mirasvit_Seo_Model_Config::CATEGORIES_RULE:
                $note = '<b>Template variables</b><br>
                        [category_name], [category_description], [category_url], [category_parent_name],
                        [category_parent_url], [category_parent_parent_name], [category_page_title],
                        [store_name], [store_url], [store_address], [store_phone], [store_email]';
                break;
            case Mirasvit_Seo_Model_Config::RESULTS_LAYERED_NAVIGATION_RULE:
                $note = '<b>Template variables</b><br>
                        [category_name],  [category_description], [category_url], [category_parent_name], [category_parent_url], <br>
                        [filter_selected_options], [filter_named_selected_options]<br>
                        [store_name], [store_url], [store_address], [store_phone], [store_email]';
                break;
            default:
                $note = '';
                break;
        }

        $descriptionNote = 'Will be added in the bottom of the page.';
        if ($model->getRuleType() != Mirasvit_Seo_Model_Config::PRODUCTS_RULE) {
            $descriptionNote .= '<br/>' . $note;
        }

        if ($model && $model->getId()) {
            $fieldset->addField('name', 'text', array(
                'name'      => 'name',
                'label'     => Mage::helper('seo')->__('Internal rule name'),
                'required'  => true,
                'value'     => $model->getName(),
            ));

            $fieldset->addField('meta_title', 'text', array(
                'name'      => 'meta_title',
                'label'     => Mage::helper('seo')->__('Meta title'),
                'value'     => $model->getMetaTitle(),
            ));

            $fieldset->addField('meta_keywords', 'textarea', array(
                'name'      => 'meta_keywords',
                'label'     => Mage::helper('seo')->__('Meta keywords'),
                'value'     => $model->getMetaKeywords()
            ));

            $fieldset->addField('meta_description', 'textarea', array(
                'name'      => 'meta_description',
                'label'     => Mage::helper('seo')->__('Meta description'),
                'value'     => $model->getMetaDescription()
            ));

            $fieldset->addField('title', 'text', array(
                'name'      => 'title',
                'label'     => Mage::helper('seo')->__('Title (H1)'),
                'value'     => $model->getTitle(),
            ));

            $fieldset->addField('description', 'textarea', array(
                'name'      => 'description',
                'label'     => Mage::helper('seo')->__('SEO description'),
                'value'     => $model->getDescription(),
                'note'      => $descriptionNote,
            ));

            if ($model->getRuleType() == Mirasvit_Seo_Model_Config::PRODUCTS_RULE) {
                $fieldset->addField('short_description', 'textarea', array(
                    'name'      => 'short_description',
                    'label'     => Mage::helper('seo')->__('Product short description'),
                    'value'     => $model->getShortDescription(),
                ));

                $fieldset->addField('full_description', 'textarea', array(
                    'name'      => 'full_description',
                    'label'     => Mage::helper('seo')->__('Product description'),
                    'value'     => $model->getFullDescription(),
                    'note'      => $note,
                ));
            }

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
                    'label'     => Mage::helper('seo')->__('Apply for Store View'),
                    'title'     => Mage::helper('seo')->__('Apply for Store View'),
                    'required'  => true,
                    'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                    'value'     => $model->getStoreIds()
                ));
            } else {
                $fieldset->addField('store_id', 'hidden', array(
                    'name'      => 'store_ids[]',
                    'value'     => Mage::app()->getStore(true)->getId()
                ));
                $model->setStoreId(Mage::app()->getStore(true)->getId());
            }

            $fieldset->addField('sort_order', 'text', array(
                'name'      => 'sort_order',
                'label'     => Mage::helper('seo')->__('Sort Order'),
                'value'     => $model->getSortOrder(),
            ));
        }

        return parent::_prepareForm();
    }
}
