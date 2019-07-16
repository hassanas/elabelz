<?php

/**
 * Progos_OrdersGrid
 * Rewrite Extension
 *
 * @category   Progos
 * @package    Progos_OrdersGrid
 * @copyright  Copyright (c) 2017 Elabelz (https://www.elabelz.com)
 * @author     Humaira Batool (humaira.batool@progos.org)
 * @created_at 24/03/2017
 * @reason     For adding new column in ordersgrid (failed delivery, sms verify check) 
 */
class Progos_OrdersGrid_Model_Observer extends MageWorx_OrdersGrid_Model_Observer
{

    /*
     * Reverted code as per azhar request which is creating syncing issue from mobile orders and stop update request on further calls which we required.
     *
     * */
    public function updateOrdersGridData($observer)
    {

        if(!Mage::registry('mageworx_ordersgrid')){
            Mage::register('mageworx_ordersgrid',true);
            $object = $observer->getDataObject();
            Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrderById($object->getId());
        }
        return;
    }

    protected function addGridColumns($block, $gridColumns, $type)
    {
        $helper = Mage::helper('mageworx_ordersgrid');
        $columnProvider = Mage::getSingleton('mageworx_ordersgrid/grid');
        $allColumns = $helper->getAllGridColumns();
        if (!empty($gridColumns)) {
            asort($gridColumns);
            $allColumns = array_flip($gridColumns);
        }

        $listColumns = $helper->getGridColumns($type);
        
        foreach ($allColumns as $position => $column) {
            switch ($column) {

                case 'increment_id':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('real_order_id', array(
                            'header'=> Mage::helper('sales')->__('Order #'),
                            'width' => '80px',
                            'type'  => 'text',
                            'index' => 'increment_id',
                            'filter_index' => 'main_table.increment_id',
                        ));
                    }
                    break;

                case 'store_id':
                    if (!Mage::app()->isSingleStoreMode()) {
                        if (in_array($column, $listColumns)) {
                            $block->addColumn('store_id', array(
                                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                                'index'     => 'store_id',
                                'filter_index' => 'main_table.store_id',
                                'type'      => 'store',
                                'store_view'=> true,
                                'display_deleted' => true,
                            ));
                        }
                    }
                    break;

                case 'created_at':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('created_at', array(
                            'header' => Mage::helper('sales')->__('Purchased On'),
                            'index' => 'created_at',
                            'filter_index' => 'main_table.created_at',
                            'type' => 'datetime',
                            'width' => '100px',
                        ));
                    }
                    break;

                case 'billing_name':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_name', array(
                            'header' => Mage::helper('sales')->__('Bill to Name'),
                            'index' => 'billing_name',
                            'filter_index' => 'main_table.billing_name',
                            'escape' => true
                        ));
                    }
                    break;

                case 'shipping_name':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_name', array(
                            'header' => Mage::helper('sales')->__('Ship to Name'),
                            'index' => 'shipping_name',
                            'filter_index' => 'main_table.shipping_name',
                            'escape' => true
                        ));
                    }
                    break;

                case 'base_grand_total':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_grand_total', array(
                            'type' => 'text',
                            'header' => Mage::helper('sales')->__('G.T. (Base)'),
                            'index' => 'base_grand_total',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision',
                            'filter' => false,
                            'currency' => 'base_currency_code',
                            'width' => '120px'
                        ));
                    }
                    break;

                case 'grand_total':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('grand_total', array(
                            'type' => 'text',
                            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
                            'index' => 'grand_total',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision',
                            'filter' => false,
                            'currency' => 'order_currency_code',
                            'width' => '120px'
                        ));
                    }
                    break;

                case 'status':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('status', array(
                            'header' => Mage::helper('sales')->__('Status'),
                            'index' => 'status',
                            'filter_index' => 'main_table.status',
                            'type'  => 'options',
                            'width' => '70px',
                            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
                        ));
                    }
                    break;

                case 'action':
                    if (in_array($column, $listColumns)) {
                        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                            $block->addColumn('action',
                                array(
                                    'header'    => Mage::helper('sales')->__('Action'),
                                    'width'     => '50px',
                                    'type'      => 'action',
                                    'getter'     => 'getId',
                                    'actions'   => array(
                                        array(
                                            'caption' => Mage::helper('sales')->__('View'),
                                            'url'     => array('base'=>'*/sales_order/view'),
                                            'field'   => 'order_id',
                                            'data-column' => 'action',
                                        )
                                    ),
                                    'filter'    => false,
                                    'sortable'  => false,
                                    'index'     => 'stores',
                                    'is_system' => true,
                                ));
                        }
                    }
                    break;

                case 'product_names':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('product_names', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_products',
                            'header' => $helper->__('Product Name(s)') . (!strpos(Mage::app()->getRequest()->getRequestString(), '/exportCsv/') ? '' : ''),
                            'index' => 'product_names',
                            'filter_index' => 'main_table.product_names',
                            'column_css_class' => 'mw-orders-grid-product_names'
                        ));
                    }
                    break;

                case 'product_skus':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('product_skus', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_products',
                            'header' => $helper->__('SKU(s)'),
                            'index' => 'skus',
                            'filter_index' => 'main_table.skus',
                        ));
                    }
                    break;

                case 'product_options':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('product_options', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_products',
                            'header' => $helper->__('Product Option(s)'),
                            'index' => 'product_options',
                            'filter' => false,
                            'sortable' => false
                        ));
                    }
                    break;

                case 'customer_email':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('customer_email', array(
                            'type' => 'text',
                            'header' => $helper->__('Customer Email'),
                            'index' => 'customer_email',
                            'filter_index' => 'main_table.customer_email'
                        ));
                    }
                    break;

                case 'customer_group':
                    if (in_array($column, $listColumns)) {
                        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Customer_Group $sourceCustomerGroup */
                        $sourceCustomerGroup = Mage::getSingleton('mageworx_ordersgrid/system_config_source_customer_group');
                        $block->addColumn('customer_group', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_customer_group',
                            'type' => 'options',
                            'options' => $sourceCustomerGroup->toArray(),
                            'header' => $helper->__('Customer Group'),
                            'index' => 'customer_group_id',
                            'filter_index' => 'main_table.customer_group_id',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'payment_method':
                    if (in_array($column, $listColumns)) {
                        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Payment_Methods $sourcePaymentMethods */
                        $sourcePaymentMethods = Mage::getSingleton('mageworx_ordersgrid/system_config_source_payment_methods');
                        $block->addColumn('payment_method', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_payment',
                            'type' => 'options',
                            'options' => $sourcePaymentMethods->toArray(),
                            'header' => $helper->__('Payment Method'),
                            'index' => 'payment_method',
                            'filter_index' => 'main_table.payment_method',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'base_total_refunded':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_total_refunded', array(
                            'type' => 'currency',
                            'currency' => 'base_currency_code',
                            'header' => $helper->__('Total Refunded (Base)'),
                            'index' => 'base_total_refunded',
                            'filter_index' => 'main_table.base_total_refunded',
                            'total' => 'sum',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'total_refunded':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('total_refunded', array(
                            'type' => 'currency',
                            'currency' => 'order_currency_code',
                            'header' => $helper->__('Total Refunded (Purchased)'),
                            'index' => 'total_refunded',
                            'filter_index' => 'main_table.total_refunded',
                            'total' => 'sum',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'shipping_method':
                    if (in_array($column, $listColumns)) {
                        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Shipping_Methods $sourceShippingMethods */
                        $sourceShippingMethods = Mage::getModel('mageworx_ordersgrid/system_config_source_shipping_methods');
                        $block->addColumn('shipping_method', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_shipping',
                            'type' => 'options',
                            'options' => $sourceShippingMethods->toArray(),
                            'header' => $helper->__('Shipping Method'),
                            'index' => 'shipping_method',
                            'filter_index' => 'main_table.shipping_method',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'tracking_number':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('tracking_number', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_street',
                            'type' => 'text',
                            'header' => $helper->__('Tracking Number'),
                            'index' => 'tracking_number',
                            'filter_index' => 'main_table.tracking_number',
                        ));
                    }
                    break;

                case 'shipped':
                    if (in_array($column, $listColumns)) {
                        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Shipping_Status $sourceShippingStatus */
                        $sourceShippingStatus = Mage::getModel('mageworx_ordersgrid/system_config_source_shipping_status');
                        $block->addColumn('shipped', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_shipped',
                            'type' => 'options',
                            'options' => $sourceShippingStatus->toArray(),
                            'header' => $helper->__('Shipped'),
                            'index' => 'shipped',
                            'filter_index' => 'main_table.shipped',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'order_group':
                    if (in_array($column, $listColumns)) {
                        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Orders_Group $sourceOrdersGroup */
                        $sourceOrdersGroup = Mage::getModel('mageworx_ordersgrid/system_config_source_orders_group');
                        $block->addColumn('order_group', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_order_group',
                            'type' => 'options',
                            'options' => $sourceOrdersGroup->toArray(),
                            'header' => $helper->__('Group'),
                            'index' => 'order_group_id',
                            'filter_index' => 'main_table.order_group_id',
                            'align' => 'center',
                        ));
                    }
                    break;

                case 'qnty':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('qnty', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_qnty',
                            'filter' => false,
                            'sortable' => false,
                            'header' => $helper->__('Qnty'),
                            'index' => 'total_qty',
                            'filter_index' => 'main_table.total_qty',
                        ));
                    }
                    break;

                // case 'weight':
                //     if (in_array($column, $listColumns)) {
                //         $block->addColumn('weight', array(
                //             'type' => 'number',
                //             'header' => $helper->__('Weight'),
                //             'index' => 'weight',
                //             'filter_index' => 'main_table.weight',
                //         ));
                //     }
                //     break;

                case 'base_tax_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_tax_amount', array(
                            'type' => 'currency',
                            'currency' => 'base_currency_code',
                            'header' => $helper->__('Tax Amount (Base)'),
                            'index' => 'base_tax_amount',
                            'filter_index' => 'main_table.base_tax_amount',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'tax_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('tax_amount', array(
                            'type' => 'currency',
                            'currency' => 'order_currency_code',
                            'header' => $helper->__('Tax Amount (Purchased)'),
                            'index' => 'tax_amount',
                            'filter_index' => 'main_table.tax_amount',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'shipping_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_amount', array(
                            'type' => 'currency',
                            'currency' => 'order_currency_code',
                            'header' => $helper->__('Shipping Amount (Purchased)'),
                            'index' => 'shipping_amount',
                            'filter_index' => 'main_table.shipping_amount',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision'
                        ));
                    }
                    break;

                case 'base_shipping_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_shipping_amount', array(
                            'type' => 'currency',
                            'currency' => 'base_currency_code',
                            'header' => $helper->__('Shipping Amount (Base)'),
                            'index' => 'base_shipping_amount',
                            'filter_index' => 'main_table.base_shipping_amount',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'subtotal':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('subtotal', array(
                            'type' => 'currency',
                            'currency' => 'order_currency_code',
                            'header' => $helper->__('Subtotal (Purchased)'),
                            'index' => 'subtotal',
                            'filter_index' => 'main_table.subtotal',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision'
                        ));
                    }
                    break;

                case 'base_subtotal':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_subtotal', array(
                            'type' => 'currency',
                            'currency' => 'base_currency_code',
                            'header' => $helper->__('Subtotal (Base)'),
                            'index' => 'base_subtotal',
                            'filter_index' => 'main_table.base_subtotal',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision'
                        ));
                    }
                    break;

                case 'base_discount_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_discount_amount', array(
                            'type' => 'currency',
                            'currency' => 'base_currency_code',
                            'header' => $helper->__('Discount (Base)'),
                            'index' => 'base_discount_amount',
                            'filter_index' => 'main_table.base_discount_amount',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'discount_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('discount_amount', array(
                            'type' => 'currency',
                            'currency' => 'order_currency_code',
                            'header' => $helper->__('Discount (Purchased)'),
                            'index' => 'discount_amount',
                            'filter_index' => 'main_table.discount_amount',
                            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                        ));
                    }
                    break;

                case 'base_internal_credit':
                    if (in_array($column, $listColumns)) {
                        if (Mage::getConfig()->getModuleConfig('MageWorx_CustomerCredit')->is('active', true)) {
                            $block->addColumn('base_internal_credit', array(
                                'type' => 'currency',
                                'currency' => 'base_currency_code',
                                'header' => $helper->__('Internal Credit (Base)'),
                                'index' => 'base_customer_credit_amount',
                                'filter_index' => 'main_table.base_customer_credit_amount',
                                'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                            ));
                        }
                    }
                    break;
                case 'internal_credit':
                    if (in_array($column, $listColumns)) {
                        if (Mage::getConfig()->getModuleConfig('MageWorx_CustomerCredit')->is('active', true)) {
                            $block->addColumn('internal_credit', array(
                                'type' => 'currency',
                                'currency' => 'order_currency_code',
                                'header' => $helper->__('Internal Credit (Purchased)'),
                                'index' => 'customer_credit_amount',
                                'filter_index' => 'main_table.customer_credit_amount',
                                'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
                            ));
                        }
                    }
                    break;

                case 'billing_company':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_company', array(
                            'type' => 'text',
                            'header' => $helper->__('Bill to Company'),
                            'index' => 'billing_company',
                            'filter_index' => 'main_table.billing_company',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'shipping_company':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_company', array(
                            'type' => 'text',
                            'header' => $helper->__('Ship to Company'),
                            'index' => 'shipping_company',
                            'filter_index' => 'main_table.shipping_company',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'billing_street':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_street', array(
                            'type' => 'text',
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_street',
                            'header' => $helper->__('Bill to Street'),
                            'index' => 'billing_street',
                            'filter_index' => 'main_table.billing_street',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'shipping_street':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_street', array(
                            'type' => 'text',
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_street',
                            'header' => $helper->__('Ship to Street'),
                            'index' => 'shipping_street',
                            'filter_index' => 'main_table.shipping_street',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'billing_city':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_city', array(
                            'type' => 'text',
                            'header' => $helper->__('Bill to City'),
                            'index' => 'billing_city',
                            'filter_index' => 'main_table.billing_city',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'shipping_city':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_city', array(
                            'type' => 'text',
                            'header' => $helper->__('Ship to City'),
                            'index' => 'shipping_city',
                            'filter_index' => 'main_table.shipping_city',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'billing_region':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_region', array(
                            'type' => 'text',
                            'header' => $helper->__('Bill to State'),
                            'index' => 'billing_region',
                            'filter_index' => 'main_table.billing_region',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'shipping_region':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_region', array(
                            'type' => 'text',
                            'header' => $helper->__('Ship to State'),
                            'index' => 'shipping_region',
                            'filter_index' => 'main_table.shipping_region',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'billing_country':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_country', array(
                            'type' => 'options',
                            'options' => $helper->getCountryNames(),
                            'header' => $helper->__('Bill to Country'),
                            'index' => 'billing_country',
                            'filter_index' => 'main_table.billing_country',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'shipping_country':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_country', array(
                            'type' => 'options',
                            'header' => $helper->__('Ship to Country'),
                            'options' => $helper->getCountryNames(),
                            'index' => 'shipping_country',
                            'filter_index' => 'main_table.shipping_country',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'billing_postcode':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_postcode', array(
                            'type' => 'text',
                            'header' => $helper->__('Billing Postcode'),
                            'index' => 'billing_postcode',
                            'filter_index' => 'main_table.billing_postcode',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'shipping_postcode':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_postcode', array(
                            'type' => 'text',
                            'header' => $helper->__('Shipping Postcode'),
                            'index' => 'shipping_postcode',
                            'filter_index' => 'main_table.shipping_postcode',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'billing_telephone':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('billing_telephone', array(
                            'type' => 'text',
                            'header' => $helper->__('Billing Telephone'),
                            'index' => 'billing_telephone',
                            'filter_index' => 'main_table.billing_telephone',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'shipping_telephone':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('shipping_telephone', array(
                            'type' => 'text',
                            'header' => $helper->__('Shipping Telephone'),
                            'index' => 'shipping_telephone',
                            'filter_index' => 'main_table.shipping_telephone',
                            'align' => 'center',
                            'escape' => true
                        ));
                    }
                    break;

                case 'coupon_code':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('coupon_code', array(
                            'type' => 'text',
                            'header' => $helper->__('Coupon Code'),
                            'align' => 'center',
                            'index' => 'coupon_code',
                            'filter_index' => 'main_table.coupon_code',
                        ));
                    }
                    break;

                case 'is_edited':
                    if (in_array($column, $listColumns)) {
                        /** @var $sourceYesNo Mage_Adminhtml_Model_System_Config_Source_Yesno */
                        $sourceYesNo = Mage::getSingleton('adminhtml/system_config_source_yesno');
                        $block->addColumn('is_edited', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_edited',
                            'type' => 'options',
                            'options' => $sourceYesNo->toArray(),
                            'header' => $helper->__('Edited'),
                            'index' => 'is_edited',
                            'filter_index' => 'main_table.is_edited',
                            'align' => 'center'
                        ));
                    }
                    break;

                case 'order_comment':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('order_comment', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_comments',
                            'header' => $helper->__('Order Comment(s)'),
                            'index' => 'order_comment',
                            'filter_index' => 'main_table.order_comment',
                        ));
                    }
                    break;

                case 'invoice_increment_id':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('invoice_increment_id', array(
                            'renderer' => 'mageworx_ordersgrid/adminhtml_sales_order_grid_renderer_invoice',
                            'header' => $helper->__('Invoice(s)'),
                            'index' => 'invoice_increment_id',
                            'filter_index' => 'main_table.invoice_increment_id',
                        ));
                    }
                    break;

                case 'is_order_rejected':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('is_order_rejected', array(
                            'renderer' => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_orderrejected',
                            'header' => $helper->__('Failed Delivery'),
                            'width' => '50px',
                            'align' => 'center',
                            'type'    => 'options',
                            'options' => array(
                                1 => "Red",
                                2 => "Green",
                                3 => "Orange"
                              ),
                            'index' => 'increment_id',
                            'filter' => false
                        ));
                    }
                    break;

                    case 'weight':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('weight', array(
                            'renderer' => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_smsverify',
                            'header' => $helper->__('Verify By SMS'),
                            'width' => '50px',
                            'align' => 'center',
                            'type'    => 'options',
                            'options' => array(
                                "yes" => "Yes",
                                 "no" => "No",
                                ),
                            'index' => 'increment_id',
                            'filter' => false
                        ));
                    }
                    break;

                default:
                    $connectorColumns = $columnProvider->getThirdPartyColumns();
                    if (isset($connectorColumns[$column])) {
                        $block->addColumn($column, $connectorColumns[$column]->getData());
                    }
                    break;
            }
        }

        // Add edit order column
        $block->addColumn('is_edited_date', array(
            'type' => 'text',
            'renderer' => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_date',
            'header' => $helper->__('Edit Date'),
            'format' => 'date',
            'index' => 'updated_at',
            'filter_index' => 'main_table.updated_at'
        ));

    }

}