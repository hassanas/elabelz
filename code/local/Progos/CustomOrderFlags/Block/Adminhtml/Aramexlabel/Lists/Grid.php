<?php
class Progos_CustomOrderFlags_Block_Adminhtml_Aramexlabel_Lists_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('aramexlabelGrid');
        $this->setDefaultSort('created_time');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('customorderflags/aramexlabel')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'label_id',
            array(
                'header' => Mage::helper('customorderflags')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'label_id',)
        );

        $this->addColumn(
            'trackingnumber',
            array(
                'header' => Mage::helper('customorderflags')->__('Tracking Number'),
                'align' => 'left',
                'width' => '250px',
                'index' => 'trackingnumber',
            )
        );

        $this->addColumn(
            'order_id',
            array(
                'header' => Mage::helper('customorderflags')->__('Order ID'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'order_id',)
        );

        $this->addColumn(
            'created_time', array(
                'header' => Mage::helper('customorderflags')->__('Created date'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'created_time',)
        );

        $this->addColumnAfter('aramexstatus', array(
            'header' => Mage::helper('customorderflags')->__('Aramex Status'),
            'index' => 'aramexstatus',
            'type' => 'options',
            'width' => '70px',
            'options' => Mage::getModel('customorderflags/source_aramexstatus')->toOptionArray(true),
        ), 'created_time');

        return parent::_prepareColumns();
    }
}