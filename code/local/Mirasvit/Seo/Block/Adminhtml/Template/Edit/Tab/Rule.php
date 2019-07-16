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


class Mirasvit_Seo_Block_Adminhtml_Template_Edit_Tab_Rule
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return Mage::helper('seo')->__('Conditions');
    }

    public function getTabTitle()
    {
        return Mage::helper('seo')->__('Conditions');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('current_template_model');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('rules_processing', array(
            'legend'    => Mage::helper('seo')->__('Rules Processing'),
        ));

        $fieldset->addField('stop_rules_processing', 'select', array(
            'name'      => 'stop_rules_processing',
            'label'     => Mage::helper('seo')->__('Stop Further Rules Processing'),
            'options'   => array('1' => Mage::helper('seo')->__('Yes'), '0' => Mage::helper('seo')->__('No')),
            'value'     => $model->getStopRulesProcessing(),
        ));

        $fieldset->addField('apply_for_child_categories', 'select', array(
            'name'      => 'apply_for_child_categories',
            'label'     => Mage::helper('seo')->__('Apply for child categories'),
            'options'   => array('1' => Mage::helper('seo')->__('Yes'), '0' => Mage::helper('seo')->__('No')),
            'value'     => $model->getApplyForChildCategories(),
            'note'     => 'If set category in Conditions it will also applied for all child categories.',
        ));

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl(Mage::getModel('adminhtml/url')->getUrl('*/*/newConditionHtml/form/rule_conditions_fieldset'));

        $fieldset = $form->addFieldset('conditions_fieldset', array(
            'legend' => Mage::helper('seo')->__('Conditions (leave blank for all elements, depending from rule type)'))
        )->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', array(
            'name'     => 'conditions',
            'label'    => Mage::helper('seo')->__('Conditions'),
            'title'    => Mage::helper('seo')->__('Conditions'),
            'required' => true,
        ))->setRule($model->getRule())->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
