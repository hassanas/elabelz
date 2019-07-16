<?php

class Rewrite_Adminhtml_Block_Customer_Edit_Tab_View_Orders extends Mage_Adminhtml_Block_Customer_Edit_Tab_View_Orders
{
    /**
     * Retrieve quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _prepareColumns()
    {

        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('customer')->__('Order #'),
            'align'     => 'center',
            'index'     => 'increment_id',
            'width'     => '100px',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('customer')->__('Purchased On'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('billing_name', array(
            'header'    => Mage::helper('customer')->__('Bill to Name'),
            'index'     => 'billing_name',
        ));

        $this->addColumn('shipping_name', array(
            'header'    => Mage::helper('customer')->__('Shipped to Name'),
            'index'     => 'shipping_name',
        ));

        $this->addColumn('grand_total', array(
            'header'    => Mage::helper('customer')->__('Order Total'),
            'index'     => 'grand_total',
            'type'      => 'currency',
            'currency'  => 'order_currency_code',
            'renderer' => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('customer')->__('Bought From'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view' => true,
            ));
        }
        $this->addColumn('status', array(
            'header'    => Mage::helper('customer')->__('Status'),
            'index'     => 'status',
            'type'      => 'options',
            'width'     => '100px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses()
        ));

        $this->addColumn('action', array(
            'header'    =>  ' ',
            'filter'    =>  false,
            'sortable'  =>  false,
            'width'     => '100px',
            'renderer'  =>  'adminhtml/sales_reorder_renderer_action'
        ));

    }
}