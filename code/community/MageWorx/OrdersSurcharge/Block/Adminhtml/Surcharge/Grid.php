<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('orderssurcharge_surcharge_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'mageworx_orderssurcharge/surcharge_collection';
    }

    /**
     * @return MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid
     */
    protected function _prepareCollection()
    {
        /** @var MageWorx_OrdersSurcharge_Model_Resource_Surcharge_Collection $collection */
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $helper = $this->getHelper();

        $this->addColumn('entity_id', array(
            'header'=> $helper->__('Surcharge #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'entity_id',
        ));

        $this->addColumn('customer_id', array(
            'header' => $helper->__('Customer ID'),
            'width' => '80px',
            'index' => 'customer_id',
        ));

        $this->addColumn('customer_email', array(
            'header' => $helper->__('Customer Email'),
            'index' => 'customer_email',
        ));

//        $this->addColumn('order_id', array(
//            'header' => $helper->__('Surcharge Order #'),
//            'width' => '80px',
//            'index' => 'order_id',
//            'renderer' => 'MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_OrderLink',
//            'align' => 'center'
//        ));
//
//        $this->addColumn('parent_order_id', array(
//            'header' => $helper->__('Order #'),
//            'width' => '80px',
//            'index' => 'parent_order_id',
//            'renderer' => 'MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_OrderLink',
//            'align' => 'center'
//        ));

        $this->addColumn('order_increment_id', array(
            'header' => $helper->__('Surcharge Order #'),
            'width' => '80px',
            'index' => 'order_increment_id',
            'renderer' => 'MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_OrderLink',
            'align' => 'center'
        ));

        $this->addColumn('parent_order_increment_id', array(
            'header' => $helper->__('Order #'),
            'width' => '80px',
            'index' => 'parent_order_increment_id',
            'renderer' => 'MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_OrderLink',
            'align' => 'center'
        ));

        $this->addColumn('base_total', array(
            'header' => $helper->__('Total (Base)'),
            'index' => 'base_total',
            'type'  => 'currency',
            'currency_code' => Mage::app()->getStore()->getBaseCurrencyCode(),
        ));

        $statusOptions = Mage::getModel('mageworx_orderssurcharge/system_config_source_surcharge_status')->toArray();
        $this->addColumn('status', array(
            'header' => $helper->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => $statusOptions,
            'align' => 'center',
            'width' => '80px',
            'renderer' => 'MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_Status',
        ));

        $this->addColumn('created_at', array(
            'header' => $helper->__('Created At'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('updated_at', array(
            'header' => $helper->__('Updated At'),
            'index' => 'updated_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => $helper->__('Created From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $helper = $this->getHelper();
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('surcharge_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        if ($helper->isAllowed('sales/mageworx_orderssurcharge/actions/restore')) {
            $this->getMassactionBlock()->addItem('restore_surcharge', array(
                'label'=> $helper->__('Restore Surcharge(s)'),
                'url'  => $this->getUrl('*/*/massRestore'),
            ));
        }

        if ($helper->isAllowed('sales/mageworx_orderssurcharge/actions/delete')) {
            $this->getMassactionBlock()->addItem('delete_surcharge', array(
                'label'=> $helper->__('Delete Surcharge(s)'),
                'url'  => $this->getUrl('*/*/massDelete'),
            ));
        }

        return $this;
    }

    /**
     * Disabled. Always return false.
     *
     * @param $row
     * @return bool|string
     */
    public function getRowUrl($row)
    {
        $helper = $this->getHelper();
        if (false && $helper->isAllowed('sales/mageworx_orderssurcharge/view')) {
            return $this->getUrl('*/*/view', array('surcharge_id' => $row->getId()));
        }
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    /**
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}
