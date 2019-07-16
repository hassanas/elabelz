<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Account_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('accountGrid');
        $this->setDefaultSort('account_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('dhllabel/account')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('account_id', array(
            'header' => Mage::helper('dhllabel')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'account_id',
        ));

        $this->addColumn('companyname', array(
            'header' => Mage::helper('dhllabel')->__('Company name'),
            'align' => 'left',
            'index' => 'companyname',
        ));

        $this->addColumn('accountnumber', array(
            'header' => Mage::helper('dhllabel')->__('DHL Acct #'),
            'align' => 'left',
            'index' => 'accountnumber',
        ));

        $this->addColumn('action',
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
            ));

        $this->addExportType('*/*/exportCsv', Mage::helper('dhllabel')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('dhllabel')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('account_id');
        $this->getMassactionBlock()->setFormFieldName('account');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('dhllabel')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('dhllabel')->__('Are you sure?')
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}