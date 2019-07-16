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
 * Display order information
 */
class Apptha_Marketplace_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Construct the inital display of grid information
     * Set the default sort for collection
     * Set the sort order as "DESC"
     *
     * Return array of data to display order information
     *
     * @return array
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'orderGrid' );
        $this->setDefaultSort ( 'entity_id' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }
    /**
     * Function to get order collection
     *
     * Return the seller product's order information
     * return array
     */
    protected function _prepareCollection() {
          $sellerId = Mage::app ()->getRequest ()->getParam ( 'id' );
        $orders = Mage::getModel ( 'marketplace/commission' )->getCollection ()->addFieldToSelect ( '*' )->addFieldToFilter ( 'order_status', array (
                'neq' => 'closed' 
        ) )->addFieldToFilter ( 'status', array (
                'eq' => 1 
        ) )->addFieldToFilter ( 'seller_id', $sellerId )->setOrder ( 'order_id', 'desc' );
        $this->setCollection ( $orders );
        $this->addExportType('*/*/exportCsv', Mage::helper('marketplace')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('marketplace')->__('EXCEL')); 
        return parent::_prepareCollection ();
    }
    /**
     * Function to display fields with data
     *
     * Display information about orders
     *
     * @return void
     */
    protected function _prepareColumns() {
        

        
        $id = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'ID' ),
                'width' => '100px',
                'index' => 'id' 
        );
        $this->addColumn ( 'id', $id );
        $increment_id = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
                'width' => '100px',
                'index' => 'increment_id' 
        );
        $this->addColumn ( 'increment_id', $increment_id );
        /*$incrementId = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
                'width' => '100px',
                'index' => 'order_id' 
        );
        $this->addColumn ( 'order_id', $incrementId );*/
        $productDetail = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Product details' ),
                'width' => '300px',
                'index' => 'id',
                // 'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderProductdetails' 
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemdetails' 
        );
        $this->addColumn ( 'productdetail', $productDetail );
        $productAmt = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Product Price' ),
                'align' => 'right',
                'index' => 'product_amt',
                'type' => 'currency',
                'currency' => 'order_currency_code' 
        );
        $this->addColumn ( 'product_amt', $productAmt );
        $sellerAmount = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Seller\'s Earned Amount' ),
                'align' => 'right',
                'index' => 'seller_amount',
                'type' => 'currency',
                'currency' => 'order_currency_code' 
        );
        $this->addColumn ( 'seller_amount', $sellerAmount );
        $commissionFee = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Commission Fee' ),
                'align' => 'right',
                'index' => 'commission_fee',
                'type' => 'currency',
                'currency' => 'order_currency_code' 
        );
        $this->addColumn ( 'commission_fee', $commissionFee );
        // $orderStatus = array (
        //     'header' => Mage::helper ( 'marketplace' )->__ ( 'Order Status' ),
        //     'type'    => 'options',
        //     'width' => '80px',
        //     'index'   => 'order_status',
        //     'options' => array(
        //         "pending"         => "Pending Customer Confirmation",
        //         "pending_seller"  => "Pending Seller Confirmation",
        //         "complete"        => "Complete",
        //         "processing"      => "Processing",
        //         "refunded"        => "Refunded",
        //         "closed"          => "Closed",
        //         "pending_payment" => "Pending Payment",
        //         "canceled"        => "Canceled"
        //     )
        // );
        // $this->addColumn ( 'order_status', $orderStatus );
        // Add column
    $orderStatus = array (
        'header'  => Mage::helper ( 'customer' )->__ ( 'Order Status' ),
        'width'   => '80',
        'type'    => 'options',
        'index'   => 'order_status',
        'options' => array(
            "pending"                       => "Pending Confirmation",
            "processing"                    => "Processing",
            "shipped_from_elabelz"          => "Shipped from Elabelz",
            "successful_delivery_partially" => "Successful Delivery Partially",
            "failed_delivery"               => "Failed Delivery",
            "successful_delivery"           => "Successful Delivery",
            "complete"                      => "Completed Non Refundable",
            "refunded"                      => "Refunded",
            "closed"                        => "Closed",
            "pending_payment"               => "Pending Payment",
            "canceled"                      => "Canceled"
        )
    );
    $this->addColumn ( 'order_status',$orderStatus);
        // $itemOrderStatus = array (
        //     'header' => Mage::helper ( 'marketplace' )->__ ( 'Item Status' ),
        //     'type'    => 'options',
        //     'width' => '80px',
        //     'index'   => 'item_order_status',
        //     'options' => array(
        //         "pending"           => "Pending Customer Confirmation",
        //         "pending_seller"    => "Pending Seller Confirmation",
        //         "rejected_customer" => "Customer Rejected",
        //         "rejected_seller"   => "Seller Rejected",
        //         "ready"             => "Ready for Processing",
        //         "complete"          => "Complete",
        //         "processing"        => "Processing",
        //         "refunded"          => "Refunded",
        //         "closed"            => "Closed",
        //         "pending_payment"   => "Pending Payment",
        //         "canceled"          => "Canceled"
        //     )
        // );
        // $this->addColumn ( 'item_order_status', $itemOrderStatus );
     // Add column
    $orderItemStatus = array (
        'header'  => Mage::helper ( 'customer' )->__ ( 'Item Status' ),
        'width'   => '80',
        'type'    => 'options',
        'index'   => 'item_order_status',
        'options' => array(
            "pending"              => "Pending Customer Confirmation",
            "pending_seller"       => "Pending Seller Confirmation",
            "rejected_customer"    => "Customer Rejected",
            "rejected_seller"      => "Seller Rejected",
            "ready"                => "Ready for Processing",
            "processing"           => "Processing",
            "shipped_from_elabelz" => "Shipped from Elabelz",
            "failed_delivery"      => "Failed Delivery",
            "successful_delivery"  => "Successful Delivery",
            "complete"             => "Completed Non Refundable",
            "refunded"             => "Refunded",
            "closed"               => "Closed",
            "pending_payment"      => "Pending Payment",
            "canceled"             => "Canceled"
        )
    );
    $this->addColumn ( 'item_order_status',$orderItemStatus);
        $orderCreatdAt = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Order At' ),
                'align' => 'center',
                'index' => 'order_id',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdate' 
        );
        $this->addColumn ( 'order_created_at', $orderCreatdAt );
        /**
         * Credit Action
         */
        $action = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Actions' ),
                'align' => 'center',
                'width' => '100',
                'seller' => $this->getRequest()->getParam("id"),
                'index' => 'id',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordercredit' 
        );
        $this->addColumn ( 'action', $action );
        /**
         * View order
         */
        // $this->addColumn ( 'changestatus', array (
        //     'header' => Mage::helper ( 'marketplace' )->__ ( 'Change Item status' ),
        //     'type' => 'action',
                
        //     'getter' => 'getId',
        //     'actions' => array (
        //             array (
        //                     'caption' => Mage::helper ( 'marketplace' )->__ ( 'Complete' ),
        //                     'url' => array (
        //                             'base' => '*/adminhtml_orderitems/status/status/complete',
        //                             'params'=>array('path'=>'seller_order','seller_id'=>$this->getRequest()->getParam("id")) 
        //                     ),
        //                     'field' => 'id',
        //                     'title' => Mage::helper ( 'marketplace' )->__ ( 'Complete' ) 
        //             ),
        //             array (
        //                     'caption' => Mage::helper ( 'marketplace' )->__ ( 'Processing' ),
        //                     'url' => array (
        //                             'base' => "*/adminhtml_orderitems/status/status/processing",
        //                             'params'=>array('path'=>'seller_order','seller_id'=>$this->getRequest()->getParam("id")) 
        //                     ),
        //                     'field' => 'id' 
        //             ),
        //             array (
        //                     'caption' => Mage::helper ( 'marketplace' )->__ ( 'Cancel' ),
        //                     'url' => array (
        //                             'base' => "*/adminhtml_orderitems/status/status/canceled",
        //                              'params'=>array('path'=>'seller_order','seller_id'=>$this->getRequest()->getParam("id")) 
        //                     ),
        //                     'field' => 'id',
        //                     'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure you want to CANCEL this item?' ) 
        //             ),
        //             array (
        //                     'caption' => Mage::helper ( 'marketplace' )->__ ( 'Pending Payment' ),
        //                     'url' => array (
        //                             'base' => "*/adminhtml_orderitems/status/status/pending_payment" ,
        //                             'params'=>array('path'=>'seller_order','seller_id'=>$this->getRequest()->getParam("id")) 
        //                     ),
        //                     'field' => 'id',
        //                     'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' ) 
        //             ) 
        //     ),
        //     'sortable' => false,
        //     'filter' => false 
        // ));
        
        
        $this->addColumn ( 'view', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'View' ),
                'width' => '80',
                'type' => 'action',
                'getter' => 'getOrderId',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'View' ),
                                'url' => array (
                                        'base' => 'adminhtml/sales_order/view/' 
                                ),
                                'field' => 'order_id' 
                        ) 
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true 
        ) );
        
        return parent::_prepareColumns ();
    }
    /**
     * Function for Mass edit action(credit payment to seller)
     *
     * Will change the credit order status of the seller
     * return void
     */
    protected function _prepareMassaction() {
        $this->setMassactionIdField ( 'id' );
        $this->getMassactionBlock ()->setFormFieldName ( 'marketplace' );
        $this->getMassactionBlock ()->addItem ( 'credit', array (
                'label' => Mage::helper ( 'marketplace' )->__ ( 'Credit' ),
                'url' => $this->getUrl ( '*/*/masscredit' ) 
        ) );
        return $this;
    }
    /**
     * Function for link url
     *
     * Not redirect to any page
     * return void
     */
    public function getRowUrl($row) {
        if (! empty ( $row )) {
            $row = false;
        }
        return $row;
    }
} 