<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 */

/**
 * View order information
 */
class Apptha_Marketplace_Block_Adminhtml_Orderitemsall_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Construct the inital display of grid information
     * Set the default sort for collection
     * Set the sort order as "DESC"
     *
     * Return array of data to view order information
     *
     * @return array
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('orderitemsallGrid');
        $this->setDefaultSort('main_table.created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Function to get order collection
     *
     * Return the seller product's order information
     * return array
     */
    protected function _prepareCollection()
    {
        /** Commission Get Collection */
        $orders = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('main_table.created_at', array('neq' => 'NULL'));
        $orders->getSelect()->join(array('orders' => 'sales_flat_order'),
            'orders.entity_id=main_table.order_id', array('orders.*'));
        $orders->addFilterToMap('created_at', 'main_table.created_at');
        $orders->addFilterToMap('increment_id', 'main_table.increment_id');
        $filter = Mage::app()->getRequest()->getParam('filter');
        $requestData = Mage::helper('adminhtml')->prepareFilterString($filter);
        if (empty($requestData) && (Mage::app()->getRequest()->getActionName() == 'exportCsv' || Mage::app()->getRequest()->getActionName() == 'exportExcel')) {  // This will work for export
            $noOfDays = Mage::getStoreConfig('marketplace/marketplace/all_export'); /*getting value from system configuation*/
            if (!$noOfDays): // if no value set then use this
                $noOfDays = 40;
            endif;
            $today = Mage::getModel('core/date')->date('Y-m-d') . ' 23:59:59';
            $past = date('Y-m-d', strtotime('-' . $noOfDays . ' days')) . ' 00:00:00';

            $orders->addFieldToFilter('main_table.created_at', array(
                'from' => $past,
                'to' => $today,
                'date' => true,
            ));
        }

        $this->addExportType('*/*/exportCsv', Mage::helper('marketplace')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('marketplace')->__('EXCEL'));

        /** Set Collection */
        $orders->setOrder('main_table.id', 'DESC');
        $this->setCollection($orders);
        return parent::_prepareCollection();
    }

    /**
     * Function to create custom column
     *
     * @param string $id
     * @return string colunm value
     */
    public function createCustomColumn($id, $store)
    {
        switch ($id) {
            case 'Seller detail' :
                $value = $this->getSellerDetail();
                break;
            case 'Product details' :
                $value = $this->getProductDetail();
                break;
            case 'Product Price' :
                $value = $this->getProductPrice($store);
                break;
            default :
                $value = '';
        }

        return $value;
    }

    /**
     * Function to display fields with data
     *
     * Display information about orders
     *
     * @return void
     */
    protected function _prepareColumns()
    {
        /** Get Store */
        $store = Mage::app()->getStore();
        $id = array(
            'header' => Mage::helper('sales')->__('ID'),
            'width' => '100px',
            'index' => 'id',
        );
        $this->addColumn('ID', $id);
        $this->createCustomColumn('Seller detail', $store);

        $buyer_details = array(
            'header' => Mage::helper('sales')->__('Buyer detail'),
            'width' => '350',
            // 'index'    => 'customer_id',
            'index' => 'order_id',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerDetails',
        );
        $this->addColumn('buyer_details', $buyer_details);

        $sms_verify_status = array(
            'header' => Mage::helper('marketplace')->__('Verify by SMS'),
            'width' => '80',
            'type' => 'options',
            'align' => 'center',
            'index' => 'sms_verify_status',
            'options' => array(
                "yes" => "Yes",
                "no" => "No",
            ),
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_SmsVerifyStatus',
        );
        $this->addColumn('sms_verify_status', $sms_verify_status);

        $incrementId = array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width' => '100px',
            'align' => 'center',
            'index' => 'increment_id',
        );
        $this->addColumn('increment_id', $incrementId);

        $incrementId = array(
            'header' => Mage::helper('sales')->__('Product ID'),
            'width' => '100px',
            'align' => 'center',
            'index' => 'product_id',
        );
        $this->addColumn('product_id', $incrementId);

        /** Create Custom Column */
        $this->createCustomColumn('Product details', $store);

        // Add column
        $qty = array(
            'header' => Mage::helper('marketplace')->__('Quantity'),
            'width' => '20',
            'align' => 'center',
            'index' => 'product_qty',
        );
        $this->addColumn('product_qty', $qty);


        /** Create Product Price */
        $this->createCustomColumn('Product Price', $store);

        $this->addColumn('special_price', array(
            'header' => Mage::helper('catalog')->__('Special Price'),
            'width' => '50px',
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'currency' => 'order_currency_code',
            'productid' => 'product_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_SpecialPrice',
        ));

        $sellerAmount = array(
            'header' => Mage::helper('sales')->__('Seller\'s Earned Amount'),
            'align' => 'right',
            'index' => 'seller_amount',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision'
        );
        $this->addColumn('seller_amount', $sellerAmount);
        $commissionFee = array(
            'header' => Mage::helper('sales')->__('Commission Fee'),
            'align' => 'right',
            'index' => 'commission_fee',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecision'
        );
        $this->addColumn('commission_fee', $commissionFee);
        // Add column
        $order_amount = array(
            'header' => Mage::helper('sales')->__('Order total'),
            'align' => 'right',
            'index' => 'id',
            'width' => '80',
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'currency' => 'order_currency_code',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_getordertotal',
        );
        $this->addColumn('order_amount', $order_amount);


        $this->getFields($store);

        /**
         * View Action
         */
        $actions = array(
            'caption' => Mage::helper('marketplace')->__('View'),
            'url' => array(
                'base' => 'adminhtml/sales_order/view/',
            ),
            'field' => 'order_id',
        );
        $this->addColumn('view', array(
            'header' => Mage::helper('marketplace')->__('View'),
            'type' => 'action',
            'getter' => 'getOrderId',
            'actions' => array(
                $actions,
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));

        /**
         * Edit Action
         */
        $actions = array(
            'caption' => Mage::helper('marketplace')->__('Edit'),
            'url' => array(
                'base' => 'marketplaceadmin/adminhtml_orderitemsall/edit/',
            ),
            'field' => 'id',
        );
        $this->addColumn('edit', array(
            'header' => Mage::helper('marketplace')->__('Edit'),
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                $actions,
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));

        return parent::_prepareColumns();
    }

    /**
     * Function for Mass action(credit payment to seller)
     *
     * Will change the credit order status of the seller
     * return void
     */
    // protected function _prepareMassaction() {
    //  /** set mass action id */
    //     $this->setMassactionIdField ( 'id' );
    //     $formFieldName = 'marketplace';
    //     /** get Mass action block */
    //     $this->getMassactionBlock ()->setFormFieldName ( $formFieldName );
    //     $lable = Mage::helper ( 'marketplace' )->__ ( 'Credit' );
    //     $url = $this->getUrl ( '*/*/masscredit' );
    //     $this->getMassactionBlock ()->addItem ( 'credit', array (
    //             'label' => $lable,
    //             'url' => $url 
    //     ) );
    //     return $this;
    // }

    /**
     * Function for link url
     *
     * Not redirected to any page
     * return void
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/sales_order/view/', array(
            'order_id' => $row->getOrderId(),
        ));
    }

    /**
     * Function for adding seller detail column
     *
     * Not redirected to any page
     * return void
     */
    public function getSellerDetail()
    {
        $sellerEmail = array(
            'header' => Mage::helper('sales')->__('Seller detail'),
            'width' => '150px',
            'index' => 'seller_id',
            'sortable' => false,
            'filter_condition_callback' => array($this, '_sellerEmailFilterCallBack'),
            // 'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordersellerdetails'
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemsellerdetails',
        );

        return $this->addColumn('selleremail', $sellerEmail);

    }

    /**
     *Function for adding product detail column
     *
     *
     * Not redirected to any page
     * return void
     */
    public function getProductDetail()
    {
        $productDetails = array(
            'header' => Mage::helper('marketplace')->__('Product details'),
            'width' => '150px',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            // 'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderProductdetails'
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemdetails',
        );

        return $this->addColumn('productdetail', $productDetails);


    }

    /**
     *Function for getting product price
     *
     *
     * Not redirected to any page
     * return void
     */
    public function getProductPrice($store)
    {
        $productAmt = array(
            'header' => Mage::helper('sales')->__('Product Price'),
            'align' => 'right',
            'index' => 'product_amt',
            'width' => '80px',
            'type' => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'currency' => 'order_currency_code',
            'renderer'  => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
        );

        return $this->addColumn('product_amt', $productAmt);
    }

    public function getFields($store)
    {
        // Add column
        $orderStatus = array(
            'header' => Mage::helper('customer')->__('Order Status'),
            'width' => '80',
            'type' => 'options',
            'index' => 'order_status',
            'options' => array(
                "pending" => "Pending Confirmation",
                "pending_payment" => "Pending Payment",
                "confirmed" => "Confirmed",
                "pending_customer_confirmation" => "Pending Customer Confirmation",
                "pending_seller_confirmation" => "Pending Seller Confirmation",
                "processing" => "Processing",
                "shipped_from_elabelz" => "Shipped from Elabelz",
                "successful_delivery_partially" => "Successful Delivery Partially",
                "failed_delivery" => "Failed Delivery",
                "successful_delivery" => "Successful Delivery",
                "complete" => "Completed Non Refundable",
                "refunded" => "Refunded",
                "closed" => "Closed",
                "canceled" => "Canceled",
                Progos_OrderStatuses_Helper_Data::STATUS_CANCELED_AUTOMATIC => "Canceled-Automatic",
            ),
        );
        $this->addColumn('order_status', $orderStatus);

        $failedDelivery = array(
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderRejected',
            'header' => Mage::helper('customer')->__('Failed Delivery'),
            'width' => '50px',
            'align' => 'center',
            'type' => 'options',
            'options' => array(
                1 => "Red",
                2 => "Green",
                3 => "Orange",
            ),
            'index' => 'increment_id',
            'filter_condition_callback' => array($this, '_failedDeliveryFilterCallBack'),
        );
        $this->addColumn('failed_delivery', $failedDelivery);

        // Add column
        $orderItemStatus = array(
            'header' => Mage::helper('customer')->__('Item Status'),
            'width' => '80',
            'type' => 'options',
            'index' => 'item_order_status',
            'options' => array(
                "pending" => "Pending Customer Confirmation",
                "pending_seller" => "Pending Seller Confirmation",
                "rejected_customer" => "Customer Rejected",
                "rejected_seller" => "Seller Rejected",
                "ready" => "Ready for Processing",
                "shipped_from_elabelz" => "Shipped from Elabelz",
                "failed_delivery" => "Failed Delivery",
                "successful_delivery" => "Successful Delivery",
                "complete" => "Completed Non Refundable",
                "refunded" => "Refunded",
                "canceled" => "Canceled",
            ),
        );
        $this->addColumn('item_order_status', $orderItemStatus);


        // Add column
        $seller_confirmation = array(
            'header' => Mage::helper('sales')->__('Seller<br>Confirmation'),
            'align' => 'center',
            'width' => '100',
            'path' => 'all_orders',
            'filter' => false,
            'index' => 'id',
            'is_system' => true,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemSellerConfirm',
        );
        $this->addColumn('is_seller_confirmation', $seller_confirmation);

        // Add column
        $buyer_confirmation = array(
            'header' => Mage::helper('sales')->__('Buyer<br>Confirmation'),
            'width' => '100',
            'path' => 'all_orders',
            'align' => 'center',
            'filter' => false,
            'index' => 'id',
            'is_system' => true,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerConfirm',
        );
        $this->addColumn('is_buyer_confirmation', $buyer_confirmation);


        $this->addColumn('changestatus', array(
            'header' => Mage::helper('marketplace')->__('Change status'),
            'type' => 'action',
            'width' => '200px',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('marketplace')->__('Cancel'),
                    'url' => array(
                        'base' => "*/adminhtml_orderitems/status/status/canceled",
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'confirm' => Mage::helper('marketplace')->__('Cancel this Order Item?'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Processing'),
                    'url' => array(
                        'base' => "*/adminhtml_orderitems/status/status/processing",
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'title' => Mage::helper('marketplace')->__('Processing'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Shipped From Elabelz'),
                    'url' => array(
                        'base' => '*/adminhtml_orderitems/status/status/shipped_from_elabelz',
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'title' => Mage::helper('marketplace')->__('Shipped From Elabelz'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Completed Non Refundable'),
                    'url' => array(
                        'base' => '*/adminhtml_orderitems/status/status/complete',
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'title' => Mage::helper('marketplace')->__('Completed Non Refundable'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Failed Delivery'),
                    'url' => array(
                        'base' => '*/adminhtml_orderitems/status/status/failed_delivery',
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'title' => Mage::helper('marketplace')->__('Failed Delivery'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Successful Delivery'),
                    'url' => array(
                        'base' => '*/adminhtml_orderitems/status/status/successful_delivery',
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'title' => Mage::helper('marketplace')->__('Successful Delivery'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Refunded'),
                    'url' => array(
                        'base' => '*/adminhtml_orderitems/status/status/refunded',
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'title' => Mage::helper('marketplace')->__('Refunded'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Pending Payment'),
                    'url' => array(
                        'base' => "*/adminhtml_orderitems/status/status/pending_payment",
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'confirm' => Mage::helper('marketplace')->__('Set this Order item as Pending Payment ?'),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Revert Buyer Confirmation'),
                    'url' => array(
                        'base' => "*/adminhtml_orderitems/status/status/rbc",
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'confirm' => Mage::helper('marketplace')
                        ->__("Are you sure you want to revert the Buyer Confirmation status to Pending ?\n\nREMEMBER: It will also affect Seller Confirmation status (reverting to pending) and Order Item status. \n\nProceed .... ?"),
                ),
                array(
                    'caption' => Mage::helper('marketplace')->__('Revert Seller Confirmation'),
                    'url' => array(
                        'base' => "*/adminhtml_orderitems/status/status/rsc",
                        'params' => array('path' => 'all_orders'),
                    ),
                    'field' => 'id',
                    'confirm' => Mage::helper('marketplace')
                        ->__('Are you sure you want to revert the Seller Confirmation status to Pending ?'),
                ),
            ),
            'sortable' => false,
            'is_system' => true,
            'filter' => false,
        ));

        $orderCreatedAt = array(
            'header' => Mage::helper('marketplace')->__('Order At'),
            'index' => 'created_at',
            'type' => 'datetime',
            'align' => 'left',
            'width' => '100px',
        );
        $this->addColumn('created_at', $orderCreatedAt);
    }

    /**
     * Function for Mass edit action(approve,disapprove or delete)
     *
     * Will change the status of the seller
     * return void
     */
    protected function _prepareMassaction()
    {
        /**
         * set Entity Id
         */
        $this->setMassactionIdField('id');
        /**
         * Set Form Field
         */
        $this->getMassactionBlock()->setFormFieldName('marketplace');
        /**
         * Add custom column delete
         */
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('marketplace')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('marketplace')->__('Are you sure?'),
        ));

        return $this;
    }

    public function _sellerEmailFilterCallBack($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $customer = Mage::getResourceModel('customer/customer_collection');
        $customer->addFieldToFilter('email', array('like' => '%' . $value . '%'));


        if ($customer):
            $sellerId = array();
            $counter = 0;
            foreach ($customer as $custom):
                $sellerId[$counter] = $custom->getId();
                $counter = $counter + 1;
            endforeach;
            $this->getCollection()->addFieldToFilter('seller_id', array('in' => $sellerId)
            );

            return $this;
        endif;

    }

    public function _failedDeliveryFilterCallBack($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $value = $column->getFilter()->getValue();
        $failedDeliveryArray = array();
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('failed_delivery', $value);

        if (count($orders) > 0):
            foreach ($orders as $collection):
                array_push($failedDeliveryArray, $collection->getIncrementId());
            endforeach;
            $failedDeliveryArray = array_unique($failedDeliveryArray);
            $this->getCollection()->addFieldToFilter('main_table.increment_id', array(
                'in' => array($failedDeliveryArray),
            ));

            return $this;
        endif;
    }

}

