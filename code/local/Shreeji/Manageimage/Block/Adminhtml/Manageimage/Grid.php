<?php

class Shreeji_Manageimage_Block_Adminhtml_Manageimage_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('manageimageGrid');
        $this->setDefaultSort('manageimage_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('manageimage/manageimage')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('manageimage_id', array(
        'header'    => Mage::helper('manageimage')->__('ID'),
        'align'     =>'right',
        'width'     => '50px',
        'index'     => 'manageimage_id',
        ));

        $this->addColumn('productname', array(
        'header'    => Mage::helper('manageimage')->__('Product Name'),
        'align'     =>'left',
        'index'     => 'productname',
        ));

        $this->addColumn('filename', array(
        'header'    => Mage::helper('manageimage')->__('Product Image'),
        'align'     =>'left',
        'index'     => 'filename',
        'renderer'     =>'Shreeji_Manageimage_Block_Adminhtml_Manageimage_Image',
        ));
        $this->addColumn('sku', array(
        'header'    => Mage::helper('manageimage')->__('Product SKU'),
        'align'     =>'left',
        'index'     => 'sku',
        ));

        $this->addColumn('action',
        array(
        'header'    =>  Mage::helper('manageimage')->__('Action'),
        'width'     => '100',
        'type'      => 'action',
        'getter'    => 'getId',
        'actions'   => array(
        array(
        'caption'   => Mage::helper('manageimage')->__('Delete'),
        'url'       => array('base'=> '*/*/delete'),
        'field'     => 'id'
        )
        ),
        'filter'    => false,
        'sortable'  => false,
        'index'     => 'stores',
        'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('manageimage')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('manageimage')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('manageimage_id');
        $this->getMassactionBlock()->setFormFieldName('manageimage');

        $this->getMassactionBlock()->addItem('delete', array(
        'label'    => Mage::helper('manageimage')->__('Delete'),
        'url'      => $this->getUrl('*/*/massDelete'),
        'confirm'  => Mage::helper('manageimage')->__('Are you sure you want to delete duplicate product images?')
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return "";
    }

}