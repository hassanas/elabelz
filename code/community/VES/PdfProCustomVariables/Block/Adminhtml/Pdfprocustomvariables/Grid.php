<?php

class VES_PdfProCustomVariables_Block_Adminhtml_Pdfprocustomvariables_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('pdfprocustomvariablesGrid');
      $this->setDefaultSort('custom_variable_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('custom_variable_id', array(
          'header'    => Mage::helper('pdfprocustomvariables')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'custom_variable_id',
      ));
	  $this->addColumn('variable_type', array(
			'header'    => Mage::helper('pdfprocustomvariables')->__('Type'),
			'width'     => '150px',
			'index'     => 'variable_type',
	  		'type'		=> 'options',
	  		'options'   => array(
                'attribute'    			=> Mage::helper('catalogrule')->__('Product Attribute'),
      			'customer'    			=> Mage::helper('catalogrule')->__('Customer Attribute'),
                /*'static'     			=> Mage::helper('catalogrule')->__('Static'),*/
            ),
      ));
      $this->addColumn('name', array(
          'header'    => Mage::helper('pdfprocustomvariables')->__('Variable Name'),
          'align'     =>'left',
          'index'     => 'name',
      ));
	  
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('pdfprocustomvariables')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('pdfprocustomvariables')->__('Edit'),
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

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('custom_variable_id');
        $this->getMassactionBlock()->setFormFieldName('pdfprocustomvariables');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('pdfprocustomvariables')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('pdfprocustomvariables')->__('Are you sure?')
        ));
        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}