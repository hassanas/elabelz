<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_Observer
{

    /**
     * Archive orders by cron
     *
     * Cron job: mageworx_ordersgrid_archive
     * Schedule: 0 0 * * *
     *
     * @param $schedule
     */
    public function scheduledArchiveOrders($schedule)
    {
        $helper = $this->getMwHelper();
        $days = $helper->getDaysBeforeOrderGetArchived();
        if (!$helper->isEnabled() || $days == 0) {
            return;
        }
        $archiveOrdersStatus = $helper->getArchiveOrderStatuses();
        /** @var MageWorx_OrdersGrid_Model_Resource_Order_Collection $orders */
        $orders = Mage::getResourceModel('mageworx_ordersgrid/order_collection')->setFilterOrdersNoGroup($days)
            ->addFieldToFilter('status', array('in' => $archiveOrdersStatus));
        $orderIds = array();
        foreach ($orders as $ord) {
            $orderIds[] = $ord->getEntityId();
        }

        // to archive
        $helper->addToOrderGroup($orderIds, 1);
    }

    /**
     * Update orders grid data after order save commit
     *
     * Event: sales_order_save_commit_after
     * Observer Name: mageworx_observe_data_change
     *
     * @param $observer
     * @return void
     */
    public function updateOrdersGridData($observer)
    {
        $object = $observer->getDataObject();
        Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrderById($object->getId());
        return;
    }

    /**
     * Extends mass actions at orders grid
     *
     * Event: core_block_abstract_to_html_before
     * Observer Name: mageworx_add_mass_actions
     *
     * @param $observer
     * @return void
     */
    public function addMassActionToSalesOrdersGrid($observer)
    {
        /** @var Mage_Core_Block_Abstract $block */
        $block = $observer->getBlock();
        if (Mage::app()->getRequest()->getControllerName() != 'sales_order') {
            return;
        }

        $availableBlockTypes = array(
            'adminhtml/widget_grid_massaction',
            'enterprise_salesarchive/adminhtml_sales_order_grid_massaction'
        );

        if (in_array($block->getType(), $availableBlockTypes) && ($block->getParentBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Grid)) {
            /** @var Mage_Adminhtml_Block_Widget_Grid_Massaction $block */
            if ($this->getMwHelper()->isEnabled()) {

                if ($this->getMwHelper()->isEnableInvoiceOrders() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/invoice')) {
                    $block->addItem('invoice_order', array(
                        'label' => $this->getMwHelper()->__('Invoice'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massInvoice'),
                    ));
                }

                if ($this->getMwHelper()->isEnableShipOrders() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/ship')) {
                    $block->addItem('ship_order', array(
                        'label' => $this->getMwHelper()->__('Ship'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massShip'),
                    ));
                }

                if ($this->getMwHelper()->isEnableInvoiceOrders() && $this->getMwHelper()->isEnableShipOrders() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/invoice_and_ship')) {
                    $block->addItem('invoice_and_ship_order', array(
                        'label' => $this->getMwHelper()->__('Invoice+Ship'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massInvoiceAndShip'),
                    ));
                }

                if ($this->getMwHelper()->isEnableInvoiceOrders() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/invoice')) {
                    $block->addItem('invoice_and_print', array(
                        'label' => $this->getMwHelper()->__('Invoice+Print'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massInvoiceAndPrint'),
                    ));
                }

                if ($this->getMwHelper()->isEnableArchiveOrders() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/archive')) {
                    $block->addItem('archive_order', array(
                        'label' => $this->getMwHelper()->__('Archive'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massArchive'),
                    ));
                }


                if ($this->getMwHelper()->isEnableDeleteOrders() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/delete')) {
                    $block->addItem('delete_order', array(
                        'label' => $this->getMwHelper()->__('Delete'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massDelete'),
                    ));
                }

                if ($this->getMwHelper()->isEnableDeleteOrdersCompletely() && Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/delete_completely')) {
                    $block->addItem('delete_order_completely', array(
                        'label' => $this->getMwHelper()->__('Delete Completely'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massDeleteCompletely'),
                    ));
                }


                if (($this->getMwHelper()->isEnableArchiveOrders() || $this->getMwHelper()->isEnableDeleteOrders()) && (Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/archive') || Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid/actions/delete'))) {
                    $block->addItem('restore_order', array(
                        'label' => $this->getMwHelper()->__('Restore'),
                        'url' => $this->getUrl('adminhtml/mageworx_ordersgrid/massRestore'),
                    ));
                }
            }
        }

        return;
    }

    /**
     * Add custom columns to sales order export
     *
     * Event: core_layout_block_create_after
     * Observer Name: mageworx_add_columns_to_export
     *
     * @param $observer
     * @return void
     */
    public function addColumnsToExportGrid($observer)
    {
        $helper = $this->getMwHelper();
        if (!$helper->isEnabled()) {
            return;
        }

        $request = Mage::app()->getRequest();
        if ($request->getControllerName() != 'sales_order') {
            return;
        }

        /** @var Mage_Core_Block_Abstract | Mage_Adminhtml_Block_Sales_Order_Grid $block */
        $block = $observer->getBlock();
        if (!isset($block)) {
            return;
        }
        if ($block->getType() == 'adminhtml/sales_order_grid' &&
            $request->getActionName() != 'index' &&
            $request->getActionName() != 'grid' &&
            $block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
        ) {
            $gridColumns = $this->getMwHelper()->getGridColumnsSortOrder(MageWorx_OrdersGrid_Helper_Data::XML_GRID_TYPE_ORDER);
            $this->addGridColumns($block, $gridColumns, MageWorx_OrdersGrid_Helper_Data::XML_GRID_TYPE_ORDER);
        }

        return;
    }

    /**
     * Add custom columns to sales order grid
     *
     * Event: core_block_abstract_prepare_layout_before
     * Observer Name: mageworx_add_custom_columns
     *
     * @param $observer
     * @return void
     */
    public function addCustomColumnsToSalesOrdersGrid($observer)
    {
        $helper = $this->getMwHelper();
        if (!$helper->isEnabled()) {
            return;
        }
        $type = $helper::XML_GRID_TYPE_ORDER;

        if (Mage::app()->getRequest()->getControllerName() != 'sales_order') {
            return;
        }

        $block = $observer->getBlock();
        if (!isset($block)) {
            return $this;
        }

        $availableBlockTypes = array(
            'adminhtml/widget_grid_massaction',
            'enterprise_salesarchive/adminhtml_sales_order_grid_massaction'
        );
        if (in_array($block->getType(), $availableBlockTypes)) {
            /** @var Mage_Adminhtml_Block_Sales_Order_Grid $block */
            $block = $block->getLayout()->getBlock('sales_order.grid');

            if ($block) {
                $gridColumns = $this->manageThirdPartyColumns($block, $type);
                foreach($gridColumns as $columnKey => $columnValue) {
                    $block->removeColumn($columnKey);
                }
                $block->removeColumn('real_order_id');
                $this->addGridColumns($block, $gridColumns, $type);
            }
        }

        return;
    }

    /**
     * Manage 3rd party extension columns
     *
     * @param object $block
     * @param string $type
     *
     * @return array
     */
    protected function manageThirdPartyColumns($block, $type)
    {
        /** @var MageWorx_OrdersGrid_Model_Column $columnProvider */
        $columnProvider = Mage::getSingleton('mageworx_ordersgrid/grid');
        $result = array();

        $columns = $block->getColumns();
        $allColumns = $this->getMwHelper()->getAllGridColumns();
        foreach ($columns as $key => $value) {
            if(!in_array($key, $allColumns)
                && $key != 'real_order_id'
                && $key != 'massaction')
            {
                $result[$key] = $value;
            }
        }

        $existingColumns = $this->getMwHelper()->getGridColumnsSortOrder($type);
        $selectedColumns = $this->getMwHelper()->getGridColumns($type);
        foreach ($result as $key => $value) {
            if (in_array($key, array_keys($existingColumns)) && !in_array($key, $selectedColumns)) {
                unset($result[$key]);
            }
        }

        $columnProvider->addColumn($result, $type);
        $gridColumns = $columnProvider->addColumnOrder($result, $type);
        $columnProvider->setThirdPartyColumns($result);

        return $gridColumns;
    }

    /**
     * Add columns according to settings of certain grid
     *
     * @param Mage_Adminhtml_Block_Sales_Order_Grid| $block
     * @param array $gridColumns
     * @param string $type
     */
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
                            'header' => Mage::helper('sales')->__('G.T. (Base)'),
                            'index' => 'base_grand_total',
                            'type'  => 'currency',
                            'currency' => 'base_currency_code',
                            'filter_index' => 'main_table.base_grand_total',
                        ));
                    }
                    break;

                case 'grand_total':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('grand_total', array(
                            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
                            'index' => 'grand_total',
                            'type'  => 'currency',
                            'currency' => 'order_currency_code',
                            'filter_index' => 'main_table.grand_total',
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
                            'total' => 'sum'
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
                            'total' => 'sum'
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

                case 'weight':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('weight', array(
                            'type' => 'number',
                            'header' => $helper->__('Weight'),
                            'index' => 'weight',
                            'filter_index' => 'main_table.weight',
                        ));
                    }
                    break;

                case 'base_tax_amount':
                    if (in_array($column, $listColumns)) {
                        $block->addColumn('base_tax_amount', array(
                            'type' => 'currency',
                            'currency' => 'base_currency_code',
                            'header' => $helper->__('Tax Amount (Base)'),
                            'index' => 'base_tax_amount',
                            'filter_index' => 'main_table.base_tax_amount',
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

                default:
                    $connectorColumns = $columnProvider->getThirdPartyColumns();
                    if (isset($connectorColumns[$column])) {
                        $block->addColumn($column, $connectorColumns[$column]->getData());
                    }
                    break;
            }
        }
    }

    /**
     * Change main table to 'mageworx_ordersgrid_order_grid' in sales order grid collection
     *
     * Event: sales_order_grid_collection_load_before
     * Observer Name: mageworx_add_custom_columns_select
     *
     * @param $observer
     * @return void
     */
    public function changeMainTable($observer)
    {
        $orderGridCollection = $observer->getOrderGridCollection();
        if (Mage::app()->getRequest()->getControllerName() == 'customer') {
            $orderGridCollection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('main_table.*');
        }
        $from = $orderGridCollection->getSelect()->getPart('from');
        $from['main_table']['tableName'] = Mage::getSingleton('core/resource')->getTableName('mageworx_ordersgrid/order_grid');
        $orderGridCollection->getSelect()->setPart('from', $from);

        $this->applyDefaultGroupFilter($orderGridCollection);

        $observer->setOrderGridCollection($orderGridCollection);

        return;
    }

    /**
     * Add default filter to collection
     * Do not show archived/deleted orders
     *
     * @param $collection
     */
    protected function applyDefaultGroupFilter($collection)
    {
        $setDefaultFilter = true;
        $where = $collection->getSelect()->getPart('where');

        if (!empty($where)) {
            foreach ($where as $part) {
                if (stripos($part, 'order_group_id') !== false) {
                    $setDefaultFilter = false;
                    break;
                }
            }
        }

        if ($setDefaultFilter) {
            /** @var Varien_Db_Select $select */
            $select = $collection->getSelect();
            $where = $select->getPart('where');
            $and = '';
            if (!empty($where)) {
                $and = 'AND ';
            }
            $where[] = $and . "(main_table.order_group_id = '0')";
            $select->setPart('where', $where);
        }
    }

    /**
     * Update columns of sales order grid for customer (customer tab: orders)
     *
     * Event: core_block_abstract_prepare_layout_before
     * Observer Name: mageworx_add_custom_columns_for_customer
     *
     * @param $observer
     */
    public function addCustomColumnsToCustomerOrdersGrid($observer)
    {
        $helper = $this->getMwHelper();

        if (!$helper->isEnabled()) {
            return;
        }
        if (Mage::app()->getRequest()->getControllerName() != 'customer') {
            return;
        }

        /** @var Mage_Core_Block_Abstract $block */
        $block = $observer->getBlock();
        $availableBlockTypes = array(
            'adminhtml/widget_grid_massaction',
            'enterprise_salesarchive/adminhtml_sales_order_grid_massaction'
        );

        if (in_array($block->getType(), $availableBlockTypes)) {
            /** @var Mage_Adminhtml_Block_Customer_Edit_Tab_Orders $block */
            $block = $block->getLayout()->getBlock('adminhtml.customer.edit.tab.orders');
            if (!$block) {
                return;
            }

            $gridColumns = $helper->getGridColumnsSortOrder($helper::XML_GRID_TYPE_CUSTOMER);
            foreach($gridColumns as $columnKey => $columnValue) {
                $block->removeColumn($columnKey);
            }
            $block->removeColumn('real_order_id');

            $this->addGridColumns($block, $gridColumns, $helper::XML_GRID_TYPE_CUSTOMER);
        }

        return;
    }

    /**
     * Hide deleted (group <> 2) orders on frontend
     *
     * Event: sales_order_collection_load_before
     * Observer Name: mageworx_hide_deleted_orders
     *
     * @param $observer
     */
    public function hideDeletedOrders($observer)
    {
        $helper = $this->getMwHelper();
        if ($helper->isEnabled() && $helper->isHideDeletedOrdersForCustomers()) {
            /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
            $orderCollection = $observer->getOrderCollection();
            $orderCollection->addFieldToFilter('order_group_id', array('neq' => '2'));
        }
    }

    /**
     * @param int $position
     * @param array $allColumns
     * @param Mage_Adminhtml_Block_Sales_Order_Grid $block
     * @param string $column
     *
     * @return void
     */
    protected function addColumnBySortPosition($position, $allColumns, $block, $column)
    {
        if ($position > 0 && isset($allColumns[$position - 1])) {
            if (!is_object($block->getColumn($column))) {
                return;
            }
            $block->getColumn($column)->setData('filter_index', 'main_table.' . $column);
            $thatColumn = $block->getColumn($column)->getData();
            $block->removeColumn($column);
            $columnBeforeThat = $allColumns[$position - 1];
            $block->addColumnAfter($column, $thatColumn, $columnBeforeThat);
        }
    }

    protected function getUrl($url)
    {
        return Mage::getUrl($url);
    }

    /**
     * @return MageWorx_OrdersGrid_Helper_Data
     */
    protected function getMwHelper()
    {
        return Mage::helper('mageworx_ordersgrid');
    }
}
