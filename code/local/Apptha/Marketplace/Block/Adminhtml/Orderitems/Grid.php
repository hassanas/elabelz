<?php

/**
 * Progos
 *
 * Get Order Items
 *
*/

class Apptha_Marketplace_Block_Adminhtml_Orderitems_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'orderitemsGrid' );
        $this->setDefaultSort ( 'created_at' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( false );
    }

    protected function _prepareCollection() {
        $sellerId = Mage::app ()->getRequest ()->getParam ( 'id' );
        $orders = Mage::getModel ( 'marketplace/commission' )->getCollection ()->addFieldToSelect ( '*' )
        ->addFieldToFilter ( 'seller_id', array (
            'neq' => 0 
        ))
        // ->addFieldToFilter(array('order_status', 'item_order_status'), array(
        //     array(array("eq"=>'pending',"eq"=>'pending')),
        //     array(array("eq"=>'pending',"eq"=>'pending'))
        // ))
        ->addFieldToFilter('order_status', array(
            array(array("eq"=>'pending'), array("eq"=>'processing'))
        ))
        ->setOrder ( 'created_at', 'DESC' );
        $this->setCollection ( $orders );
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
        $store = Mage::app ()->getStore ();

        // Add column
        $seller_details = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Seller detail' ),
            'width'    => '350',
            'index'    => 'seller_id',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemsellerdetails'
        );
        $this->addColumn ( 'selleremail', $seller_details);

        // Add column
        $buyer_details = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Buyer detail' ),
            'width'    => '350',
            // 'index'    => 'customer_id',
            'index'    => 'order_id',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerDetails'
        );
        $this->addColumn ( 'buyer_details', $buyer_details);

        // Add column
        $incrementId = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
            'width' => '120',
            'index' => 'increment_id' 
        );
        $this->addColumn ( 'increment_id', $incrementId );

        // Add column
        $item_id = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Item ID' ),
            'align'  => 'center',
            'index'  => 'order_id',
            'width'  => '50'
        );
        // $this->addColumn ( 'item_id', $item_id );

        $incrementId = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Product ID' ),
                'width' => '100px',
                'align' => 'center',
                'index' => 'product_id' 
        );
        $this->addColumn ( 'product_id', $incrementId );

        // Add column
        $productDetail = array (
            'header'   => Mage::helper ( 'marketplace' )->__ ( 'Product' ),
            'width'    => '350',
            'index'    => 'id',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemdetails' 
        );
        $this->addColumn ( 'productdetail', $productDetail );

        // Add column
        $qty = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Quantity' ),
            'width'  => '20',
            'align'  => 'center',
            'index'  => 'product_qty'
        );
        $this->addColumn ( 'product_qty', $qty );

        // Add column
        $productAmt = array (
            'header'        => Mage::helper ( 'sales' )->__ ( 'Item Price' ),
            'align'         => 'right',
            'index'         => 'product_amt',
            'width'         => '80',
            'type'          => 'currency',
            'currency_code' => $store->getBaseCurrency ()->getCode (),
            'currency'      => 'order_currency_code',
            'renderer' => 'progos_ordersgrid/adminhtml_sales_order_grid_renderer_currencyprecisionbase'
        );
        $this->addColumn ( 'product_amt', $productAmt );

        // Add column
        $order_amount = array (
            'header'        => Mage::helper ( 'sales' )->__ ( 'Order total' ),
            'align'         => 'right',
            'index'         => 'id',
            'width'         => '80',
            'type'          => 'price',
            'currency_code' => $store->getBaseCurrency ()->getCode (),
            'currency'      => 'order_currency_code',
            'renderer'      => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_getordertotal' 
        );
        $this->addColumn ( 'order_amount', $order_amount );

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
                "canceled"                      => "Canceled"
            )
        );
        $this->addColumn ( 'order_status',$orderStatus);

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
                "canceled"             => "Canceled"
            )
        );
        $this->addColumn ( 'item_order_status',$orderItemStatus);

        // Add column
        $sellerAmount = array (
            'header'        => Mage::helper ( 'sales' )->__ ( 'Seller\'s Earned Amount' ),
            'align'         => 'right',
            'index'         => 'seller_amount',
            'type'          => 'price',
            'width'         => '50',
            'currency_code' => $store->getBaseCurrency ()->getCode (),
            'currency'      => 'order_currency_code'
        );
        // $this->addColumn ( 'seller_amount', $sellerAmount );

        // Add column
        $commissionFee = array (
            'header'        => Mage::helper ( 'sales' )->__ ( 'Commission Fee' ),
            'align'         => 'right',
            'index'         => 'commission_fee',
            'type'          => 'price',
            'width'         => '50',
            'currency_code' => $store->getBaseCurrency ()->getCode (),
            'currency'      => 'order_currency_code'
        );
        // $this->addColumn ( 'commission_fee', $commissionFee );

        // Add column
        $seller_confirmation = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Seller<br>Confirmation' ),
            'align' => 'center',
            'width' => '100',
            'filter' => false,
            'index' => 'id',
            'is_system' => true, 
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemSellerConfirm'
        );
        $this->addColumn ( 'is_seller_confirmation', $seller_confirmation );

        // Add column
        $buyer_confirmation = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Buyer<br>Confirmation' ),
            'width' => '100',
            'align' => 'center',
            'filter' => false,
            'index' => 'id',
            'is_system' => true,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemBuyerConfirm'
        );
        $this->addColumn ( 'is_buyer_confirmation', $buyer_confirmation );

        // Add column
        $orderCreatdAt = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Order At' ),
            'align' => 'center',
            'width' => '250',
            // 'type' => 'datetime',
            'filter' => false,
            'index' => 'order_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdate' 
        );
        $this->addColumn ( 'created_at', $orderCreatdAt );

        // Add column
        $action = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Actions' ),
            'align' => 'center',
            'width' => '100',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            'is_system' => true,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordercredit' 
        );

        // $this->addColumn ( 'action', $action );


        // Add column
        $paymentStatus = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Payment Status' ),
            'align' => 'center',
            'width' => '50',
            'index' => 'payment_status',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemPaymentStatus'
        );
        // $this->addColumn ( 'payment_status', $paymentStatus );

        // Add column
        $acknowledgeDate = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Paid On' ),
            'align' => 'center',
            'width' => '100',
            'index' => 'acknowledge_date',
            'type' => 'datetime',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Acknowledgedate'
        );
        // $this->addColumn ( 'acknowledge_date', $acknowledgeDate );


        /**
         * View order
         */
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
        // $this->getMassactionBlock ()->addItem ( 'credit', array (
        //         'label' => Mage::helper ( 'marketplace' )->__ ( 'Credit' ),
        //         'url' => $this->getUrl ( '*/*/masscredit' ) 
        // ) );
        return $this;
    }
    /**
     * Function for link url
     *
     * Not redirect to any page
     * return void
     */
    public function getRowUrl($row) {
        // if (! empty ( $row )) {
        //     $row = false;
        // }
        // return $row;
        return $this->getUrl ( 'adminhtml/sales_order/view/', array (
                'order_id' => $row->getOrderId () 
        ) );
    }
}