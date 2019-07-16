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
class Apptha_Marketplace_Block_Adminhtml_Orderitemsprocessing_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $this->setId('OrderitemsprocessingGrid');
        $this->setDefaultSort('created_at');
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

        $sellerIncrementIds = array();
        $sellerFinalIncrementIds = array();
        $sellerRejectedIncrementIds = array();


        $collections = Mage::getModel('marketplace/commission')->getCollection()->addFieldToSelect('*')
            ->addFieldToFilter('is_buyer_confirmation', array(
                'in' => array('Rejected', 'Yes')
            ))
            ->addFieldToFilter('seller_id', array(
                'neq' => 0
            ))
            ->addFieldToFilter('order_status', array(
                'eq' => 'pending'
            ));

        foreach ($collections as $collection) {
            array_push($sellerRejectedIncrementIds, $collection->getIncrementId());
        }
        $count = count($sellerRejectedIncrementIds);
        $sellerRejectedIncrementIds = array_unique($sellerRejectedIncrementIds);

        for ($i = 0; $i <= $count; $i++) {
            $orderConfirmed = $this->getOrderRejectedStatus($sellerRejectedIncrementIds[$i]);
            $countOrderConfirmed = count($orderConfirmed);
            $order = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('increment_id', $sellerRejectedIncrementIds[$i])
                ->getFirstItem();
            $items = count($order->getAllVisibleItems());
            $total = $items - $countOrderConfirmed;
            if ($total != 0) {
                $collections = Mage::getModel('marketplace/commission')->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('is_buyer_confirmation', array(
                        'eq' => 'Yes'
                    ))
                    ->addFieldToFilter('is_seller_confirmation', array(
                        'eq' => 'Yes'
                    ))
                    ->addFieldToFilter('seller_id', array(
                        'neq' => 0
                    ))
                    ->addFieldToFilter('increment_id', array(
                        'eq' => $sellerRejectedIncrementIds[$i]
                    ));
                $count_accepted_seller = count($collections);

                $collections = Mage::getModel('marketplace/commission')->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('is_buyer_confirmation', array(
                        'eq' => 'Yes'
                    ))
                    ->addFieldToFilter('is_seller_confirmation', array(
                        'eq' => 'Rejected'
                    ))
                    ->addFieldToFilter('seller_id', array(
                        'neq' => 0
                    ))
                    ->addFieldToFilter('increment_id', array(
                        'eq' => $sellerRejectedIncrementIds[$i]
                    ));

                $count_rejected_seller = count($collections);

                $total = $total - $count_rejected_seller;

                if ($total != 0) {
                    $total = $total - $count_accepted_seller;

                    if ($total == 0) {
                        array_push($sellerFinalIncrementIds, $sellerRejectedIncrementIds[$i]);
                    }
                }
            }

        }


        $sellerId = Mage::app()->getRequest()->getParam('id');
        $orders = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('item_order_status', array(
                'in' => array('ready', 'rejected_seller', 'rejected_customer', 'canceled')
            ))
            ->addFieldToFilter('is_buyer_confirmation', array(
                'in' => array('Yes', 'Rejected')
            ))
            ->addFieldToFilter('seller_id', array(
                'neq' => 0
            ))
            ->addFieldToFilter('order_status', array(
                'eq' => 'pending'
            ))
            ->addFieldToFilter('increment_id', array(
                'in' => $sellerFinalIncrementIds
            ))
            ->setOrder('created_at', 'DESC');
        $this->setCollection($orders);
        return parent::_prepareCollection();

    }

    public function getOrderStatus($orderIncrementId)
    {
        $orderAllowed = Mage::getModel('marketplace/commission')->getCollection()->addFieldToSelect('*')
            ->addFieldToFilter('increment_id', array(
                'eq' => $orderIncrementId
            ))
            ->addFieldToFilter('is_seller_confirmation', array(
                'in' => array('Yes', 'Rejected')
            ))
            ->addFieldToFilter('seller_id', array(
                'neq' => 0
            ))
            ->addFieldToFilter('order_status', array(
                'eq' => 'pending'
            ));
        return $orderAllowed;
    }

    public function getOrderRejectedStatus($orderIncrementId)
    {
        $orderAllowed = Mage::getModel('marketplace/commission')->getCollection()->addFieldToSelect('*')
            ->addFieldToFilter('increment_id', array(
                'eq' => $orderIncrementId
            ))
            ->addFieldToFilter('is_buyer_confirmation', array(
                'eq' => 'Rejected'
            ))
            ->addFieldToFilter('seller_id', array(
                'neq' => 0
            ))
            ->addFieldToFilter('order_status', array(
                'eq' => 'pending'
            ));
        return $orderAllowed;
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
            'index' => 'id'
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
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerDetails'
        );
        $this->addColumn('buyer_details', $buyer_details);

        $incrementId = array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width' => '100px',
            'align' => 'center',
            'index' => 'increment_id'
        );
        $this->addColumn('increment_id', $incrementId);

        $incrementId = array(
            'header' => Mage::helper('sales')->__('Product ID'),
            'width' => '100px',
            'align' => 'center',
            'index' => 'product_id'
        );
        $this->addColumn('product_id', $incrementId);

        /** Create Custom Column */
        $this->createCustomColumn('Product details', $store);

        // Add column
        $qty = array(
            'header' => Mage::helper('marketplace')->__('Quantity'),
            'width' => '20',
            'align' => 'center',
            'index' => 'product_qty'
        );
        $this->addColumn('product_qty', $qty);


        /** Create Product Price */
        $this->createCustomColumn('Product Price', $store);

        // Add column
        $order_amount = array(
            'header' => Mage::helper('sales')->__('Order total'),
            'align' => 'right',
            'index' => 'id',
            'width' => '80',
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'currency' => 'order_currency_code',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_getordertotal'
        );
        $this->addColumn('order_amount', $order_amount);


        $this->getFields($store);

        /**
         * View Action
         */
        $actions = array(
            'caption' => Mage::helper('marketplace')->__('View'),
            'url' => array(
                'base' => 'adminhtml/sales_order/view/'
            ),
            'field' => 'order_id'
        );
        $this->addColumn('view', array(
            'header' => Mage::helper('marketplace')->__('View'),
            'type' => 'action',
            'getter' => 'getOrderId',
            'actions' => array(
                $actions
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true
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
    // 	/** set mass action id */
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
            'order_id' => $row->getOrderId()
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
            'filter' => false,
            'sortable' => false,
            // 'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordersellerdetails'
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemsellerdetails'
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
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemdetails'
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
            'renderer' => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
        );
        return $this->addColumn('product_amt', $productAmt);
    }

    public function getFields($store)
    {
        $sellerAmount = array(
            'header' => Mage::helper('sales')->__('Seller\'s Earned Amount'),
            'align' => 'right',
            'index' => 'seller_amount',
            'width' => '80px',
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'currency' => 'order_currency_code'
        );
        // $this->addColumn ( 'seller_amount', $sellerAmount );

        $commissionFee = array(
            'header' => Mage::helper('sales')->__('Commission Fee'),
            'align' => 'right',
            'index' => 'commission_fee',
            'width' => '80px',
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'currency' => 'order_currency_code'
        );
        // $this->addColumn ( 'commission_fee', $commissionFee );

        // Add column
        $orderStatus = array(
            'header' => Mage::helper('customer')->__('Order Status'),
            'width' => '80',
            'type' => 'options',
            'index' => 'order_status',
            'options' => array(
                "pending" => "Pending Confirmation",
                "processing" => "Processing",
                "shipped_from_elabelz" => "Shipped from Elabelz",
                "successful_delivery_partially" => "Successful Delivery Partially",
                "failed_delivery" => "Failed Delivery",
                "successful_delivery" => "Successful Delivery",
                "complete" => "Completed Non Refundable",
                "refunded" => "Refunded",
                "closed" => "Closed",
                "pending_payment" => "Pending Payment",
                "canceled" => "Canceled"
            )
        );
        $this->addColumn('order_status', $orderStatus);

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
                "processing" => "Processing",
                "shipped_from_elabelz" => "Shipped from Elabelz",
                "failed_delivery" => "Failed Delivery",
                "successful_delivery" => "Successful Delivery",
                "complete" => "Completed Non Refundable",
                "refunded" => "Refunded",
                "closed" => "Closed",
                "pending_payment" => "Pending Payment",
                "canceled" => "Canceled"
            )
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
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemSellerConfirm'
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
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerConfirm'
        );
        $this->addColumn('is_buyer_confirmation', $buyer_confirmation);


        $orderCreatedAt = array(
            'header' => Mage::helper('marketplace')->__('Order At'),
            'align' => 'center',
            'width' => '200px',
            'index' => 'order_id',
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdate'
        );
        $this->addColumn('created_at', $orderCreatedAt);
        /**
         * Credit Action
         */
        $action = array(
            'header' => Mage::helper('marketplace')->__('Actions'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            'is_system' => true,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordercredit'
        );
        // $this->addColumn ( 'action', $action );

        /**
         * Payment status
         */
        $paymentStatus = array(
            'header' => Mage::helper('marketplace')->__('Ack Status'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'payment_status',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Receivedstatus'
        );
        // $this->addColumn ( 'payment_status', $paymentStatus );
        /**
         * Acknowledge Date
         */
        $acknowledgeDate = array(
            'header' => Mage::helper('marketplace')->__('Ack On'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'acknowledge_date',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Acknowledgedate'
        );
        // $this->addColumn ( 'acknowledge_date', $acknowledgeDate );
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
            'confirm' => Mage::helper('marketplace')->__('Are you sure?')
        ));
        /**
         * Add custom column approve
         */
        // $this->getMassactionBlock ()->addItem ( 'Approve', array (
        //         'label' => Mage::helper ( 'customer' )->__ ( 'Approve' ),
        //         'url' => $this->getUrl ( '*/*/massApprove' ) 
        // ) );
        /**
         * Add custom column disapprove
         */
        // $this->getMassactionBlock ()->addItem ( 'disapprove', array (
        //         'label' => Mage::helper ( 'customer' )->__ ( 'Disapprove' ),
        //         'url' => $this->getUrl ( '*/*/massDisapprove' ) 
        // ) );
        return $this;
    }

}

