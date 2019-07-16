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


class Mirasvit_Seo_Block_Adminhtml_Template_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('templateGrid');
        $this->setDefaultSort('template_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('seo/template')
            ->getCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('template_id', array(
                'header'    => Mage::helper('seo')->__('ID'),
                'align'     => 'right',
                'width'     => '50px',
                'index'     => 'template_id',
            )
        );

        $this->addColumn('name', array(
                'header'    => Mage::helper('seo')->__('Internal rule name'),
                'align'     => 'left',
                'width'     => '150px',
                'index'     => 'name',
            )
        );

        $this->addColumn('template_settings', array(
            'header'=> Mage::helper('catalog')->__('Template Settings'),
            'renderer'  => 'Mirasvit_Seo_Block_Adminhtml_System_TemplateRenderer',
            'filter_condition_callback' => array($this, '_templateSettingsFilter'),
        ));

        $this->addColumn('rule_type', array(
            'header'    => Mage::helper('seo')->__('Rule type'),
            'align'     => 'left',
            'width'     => '100px',
            'index'     => 'rule_type',
            'type'      => 'options',
            'options'   => array(
                Mirasvit_Seo_Model_Config::PRODUCTS_RULE                   => Mage::helper('seo')->__('Products'),
                Mirasvit_Seo_Model_Config::CATEGORIES_RULE                 => Mage::helper('seo')->__('Categories'),
                Mirasvit_Seo_Model_Config::RESULTS_LAYERED_NAVIGATION_RULE => Mage::helper('seo')->__('Layered navigation'),
            ),
        ));

        $this->addColumn('sort_order', array(
                'header'    => Mage::helper('seo')->__('Sort Order'),
                'align'     => 'left',
                'width'     => '30px',
                'index'     => 'sort_order',
            )
        );

        $this->addColumn('stop_rules_processing', array(
            'header'    => Mage::helper('seo')->__('Stop Further Rules Processing'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'stop_rules_processing',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('seo')->__('Yes'),
                0 => Mage::helper('seo')->__('No'),
            ),
        ));

        $this->addColumn('apply_for_child_categories', array(
            'header'    => Mage::helper('seo')->__('Apply for child categories'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'apply_for_child_categories',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('seo')->__('Yes'),
                0 => Mage::helper('seo')->__('No'),
            ),
        ));

        $this->addColumn('is_active', array(
            'header'    => Mage::helper('seo')->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('seo')->__('Enabled'),
                0 => Mage::helper('seo')->__('Disabled'),
            ),
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('template_id');
        $this->getMassactionBlock()->setFormFieldName('template_id');

        $this->getMassactionBlock()->addItem('enable', array(
            'label'    => Mage::helper('seo')->__('Enable'),
            'url'      => $this->getUrl('*/*/massEnable')
        ));

        $this->getMassactionBlock()->addItem('disable', array(
            'label'    => Mage::helper('seo')->__('Disable'),
            'url'      => $this->getUrl('*/*/massDisable')
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('seo')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('seo')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _templateSettingsFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $this->getCollection()->getSelect()->where(
           "meta_title like ?
            OR meta_keywords like ?
            OR meta_description like ?
            OR title like ?
            OR description like ?
            OR short_description like ?
            OR full_description like ?"
        , "%$value%");

        return $this;
    }

}