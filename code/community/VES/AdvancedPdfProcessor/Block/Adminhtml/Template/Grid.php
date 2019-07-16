<?php

class VES_AdvancedPdfProcessor_Block_Adminhtml_Template_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('templateGrid');
      $this->setSaveParametersInSession(true);
      $this->setDefaultSort('id');
      $this->setDefaultDir('ASC');
      $vesCoreHelper = Mage::helper('ves_core');
  }

  protected function _prepareCollection()
  {
	  $collection = Mage::getModel('advancedpdfprocessor/template')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
      $vesCoreHelper = Mage::helper('ves_core');
  }

  protected function _prepareColumns()
  {
      $this->addColumn('id', array(
          'header'    => Mage::helper('advancedpdfprocessor')->__('ID'),
          'align'     =>'left',
          'index'     => 'id',
      	  'width'	  => '50px',
      ));
  	  $this->addColumn('name', array(
          'header'    => Mage::helper('advancedpdfprocessor')->__('Name'),
          'align'     =>'left',
          'index'     => 'name',
      ));

      $this->addColumn('sku', array(
          'header'    => Mage::helper('advancedpdfprocessor')->__('SKU'),
          'align'     =>'left',
          'index'     => 'sku',
      	  'width'	  => '400px',
      ));
      
      $this->addColumn('css_path', array(
      		'header'    => Mage::helper('advancedpdfprocessor')->__('CSS Path'),
      		'align'     =>'left',
      		'index'     => 'css_path',
      		'width'	  => '400px',
      ));
      
      $this->addColumn('action',
      		array(
      				'header'    =>  Mage::helper('advancedpdfprocessor')->__('Action'),
      				'width'     => '100',
      				'type'      => 'action',
      				'getter'    => 'getId',
      				'actions'   => array(
      						array(
      								'caption'   => Mage::helper('advancedpdfprocessor')->__('Edit'),
      								'url'       => array('base'=> '*/*/edit'),
      								'field'     => 'id'
      						)
      				),
      				'filter'    => false,
      				'sortable'  => false,
      				'index'     => 'stores',
      				'is_system' => true,
      		));
	
      return parent::_prepareColumns();
  }
  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }
	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('advancedpdfprocessor');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('advancedpdfprocessor')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('advancedpdfprocessor')->__('Are you sure?')
        ));
        $vesCoreHelper = Mage::helper('ves_core');

        return $this;
    }
}