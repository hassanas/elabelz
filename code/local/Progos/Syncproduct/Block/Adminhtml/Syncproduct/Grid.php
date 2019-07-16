<?php

class Progos_Syncproduct_Block_Adminhtml_Syncproduct_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('syncproductGrid');
      $this->setDefaultSort('syncproduct_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('progos_syncproduct/syncproduct')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('syncproduct_id', array(
          'header'    => Mage::helper('progos_syncproduct')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'syncproduct_id',
      ));

      $this->addColumn('sku', array(
          'header'    => Mage::helper('progos_syncproduct')->__('SKU'),
          'align'     =>'left',
          'index'     => 'sku',
      ));

      $this->addColumn('status', array(
          'header'    => Mage::helper('progos_syncproduct')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => Mage::getModel('progos_syncproduct/status')->getOptionArray(),
      ));
	  
    $this->addColumn('created_time', array(
            'header'    => Mage::helper('progos_syncproduct')->__('Date Created'),
            'index'     => 'created_time',
            'type'      => 'datetime',
    ));

    $this->addColumn('update_time', array(
        'header'    => Mage::helper('progos_syncproduct')->__('Last Modified'),
        'index'     => 'update_time',
        'type'      => 'datetime',
    ));
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('progos_syncproduct')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('progos_syncproduct')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		$this->addExportType('*/*/exportCsv', Mage::helper('progos_syncproduct')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('progos_syncproduct')->__('XML'));
	  
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('syncproduct_id');
        $this->getMassactionBlock()->setFormFieldName('syncproduct');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('progos_syncproduct')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('progos_syncproduct')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('progos_syncproduct/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('progos_syncproduct')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('progos_syncproduct')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}