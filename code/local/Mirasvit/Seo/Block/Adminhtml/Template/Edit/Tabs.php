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


class Mirasvit_Seo_Block_Adminhtml_Template_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('template_edit_tabs')
            ->setDestElementId('edit_form')
            ->setTitle(Mage::helper('seo')->__('Template Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general_section', array(
            'label'   => Mage::helper('seo')->__('General Information'),
            'title'   => Mage::helper('seo')->__('General Information'),
            'content' => $this->getLayout()->createBlock('seo/adminhtml_template_edit_tab_general')->toHtml(),
        ));

        $model  = Mage::registry('current_template_model');
        if ($model && $model->getId()) {
            $this->addTab('rule_section', array(
                'label'   => Mage::helper('seo')->__('Conditions'),
                'title'   => Mage::helper('seo')->__('Conditions'),
                'content' => $this->getLayout()->createBlock('seo/adminhtml_template_edit_tab_rule')->toHtml(),
            ));
        }

        return parent::_beforeToHtml();
    }

}