<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Conformity_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('conformityGrid');
        $this->setDefaultSort('dhllabelconformity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('dhllabel/conformity')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'dhllabelconformity_id', array(
                'header' => Mage::helper('dhllabel')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'dhllabelconformity_id',
            )
        );

        $this->addColumn(
            'method_id', array(
                'header' => Mage::helper('dhllabel')->__('Shipping Method from'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'method_id',
                'type' => 'options',
                'options' => Mage::getModel('dhllabel/config_dhlmethod')->getShippingMethodsSimple(),
            )
        );

        $this->addColumn(
            'dhlmethod_id', array(
                'header' => Mage::helper('dhllabel')->__('Shipping Method to'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'dhlmethod_id',
                'type' => 'options',
                'options' => Mage::getModel('dhllabel/config_dhlmethod')->getUpsMethods(),
            )
        );

        /*multistore*/
        $this->addColumn(
            'store_id', array(
                'header' => Mage::helper('dhllabel')->__('Store'),
                'align' => 'left',
                'width' => '100px',
                'index' => 'store_id',
                'type' => 'options',
                'options' => Mage::helper('dhllabel/help')->getStores(),
            )
        );
        /*multistore*/

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('dhllabel')->__('Action'),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('dhllabel')->__('Edit'),
                        'url' => array('base' => '*/*/edit'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
            )
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('dhllabel')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('dhllabel')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('dhllabelconformity_id');
        $this->getMassactionBlock()->setFormFieldName('conformity');

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label' => Mage::helper('dhllabel')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('dhllabel')->__('Are you sure?')
            )
        );
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}