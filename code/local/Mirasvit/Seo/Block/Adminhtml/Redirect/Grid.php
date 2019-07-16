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


class Mirasvit_Seo_Block_Adminhtml_Redirect_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('redirectGrid');
        $this->setDefaultSort('redirect_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('seo/redirect')
            ->getCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('redirect_id', array(
                'header'    => Mage::helper('seo')->__('ID'),
                'align'     => 'right',
                'width'     => '50px',
                'index'     => 'redirect_id',
            )
        );

        $this->addColumn('url_from', array(
                'header'    => Mage::helper('seo')->__('Request Url'),
                'align'     => 'left',
                'index'     => 'url_from',
            )
        );

        $this->addColumn('url_to', array(
                'header'    => Mage::helper('seo')->__('Target Url'),
                'align'     => 'left',
                'index'     => 'url_to',
            )
        );

        $this->addColumn('is_redirect_only_error_page', array(
                'header'    => Mage::helper('seo')->__('Redirect only if request URL can\'t be found (404)'),
                'align'     => 'left',
                'index'     => 'is_redirect_only_error_page',
                'type'      => 'options',
                'options'   => array( 0 => 'No', 1 => 'Yes'),
                'width'     => '280px',
            )
        );
        
        $this->addColumn('redirect_type', array(
            'header'    => Mage::helper('seo')->__('Redirect Status Code'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'redirect_type',
            'type'      => 'options',
            'options'   => array(
                            301 => Mage::helper('seo')->__('301 Moved Permanently'),
                            302 => Mage::helper('seo')->__('302 Moved Temporary'),
                            307 => Mage::helper('seo')->__('307 Temporary Redirect')
                        ),
        ));

         $this->addColumn('comments', array(
                'header'    => Mage::helper('seo')->__('Comments'),
                'align'     => 'left',
                'index'     => 'comments',
            )
        );

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
        $this->setMassactionIdField('redirect_id');
        $this->getMassactionBlock()->setFormFieldName('redirect_id');

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

}