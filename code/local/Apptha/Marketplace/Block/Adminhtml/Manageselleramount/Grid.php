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
 * @author      Humera Bayool <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 */

/**
 * View Seller amount information
 */
class Apptha_Marketplace_Block_Adminhtml_Manageselleramount_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $orders = Mage::getModel('marketplace/commission')->getCollection()
                      ->addFieldToSelect('*')
                      ->addFieldToFilter('created_at', array('neq' => 'NULL'))
                      ->addFieldToFilter('is_buyer_confirmation','Yes')
                      ->addFieldToFilter('is_seller_confirmation','Yes')
                      ->addFieldToFilter('order_status', array(
                                                array('eq' => 'successful_delivery_partially'),
                                                array('eq' => 'completed_nonrefundable'),
                                                array('eq' => 'shipped_from_elabelz'),
                                                array('eq' => 'successful_delivery'),
                                                array('eq' => 'closed'),
                                                array('eq' => 'failed_delivery'),
                                                array('eq' => 'refunded')
                                                ));
        $this->addExportType('*/*/exportCsv', Mage::helper('marketplace')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('marketplace')->__('EXCEL'));

        /** Set Collection */
        $this->setCollection($orders);
        return parent::_prepareCollection();
    }

    public function getSellers(){
       $awaitingForCustomer = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                             ->addFieldToSelect ( '*' )
                             ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'Yes'))
                             ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')));
        $data=array();
        foreach($awaitingForCustomer as $list){
            $sellerprofile = Mage::getModel ( 'marketplace/sellerprofile' )->getCollection ()
                             ->addFieldToSelect ( 'store_title' )
                             ->addFieldToFilter ( 'seller_id', array ('eq' => $list->getSellerId()));                                        
            //echo  '<pre>'.print_r($sellerprofile); echo  '</pre>';
            foreach ($sellerprofile as $key => $value) {
                $data[$list->getSellerId()] = $value->getStoreTitle();
            }

        }
        //echo  '<pre>'.print_r($data); echo  '</pre>';
        asort($data);
        return $data;
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

        $orderCreatedAt = array(
            'header' => Mage::helper('marketplace')->__('Order At'),
            'index' => 'created_at',
            'type' => 'datetime',
            'align' => 'left',
            'width' => '100px',
        );
        $this->addColumn('created_at', $orderCreatedAt);
        
        $this->createCustomColumn('Seller detail', $store);

        $incrementId = array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width' => '100px',
            'align' => 'center',
            'index' => 'increment_id',
            'filter' => false,
        );
        $this->addColumn('increment_id', $incrementId);

        $this->getFields($store);

        $incrementId = array(
            'header' => Mage::helper('sales')->__('Product ID'),
            'width' => '100px',
            'align' => 'center',
            'index' => 'product_id',
            'filter' => false,
            'is_system' => true,
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
            'filter' => false,
        );
        $this->addColumn('product_qty', $qty);


        /** Create Product Price */
        $this->createCustomColumn('Product Price', $store);

        $this->addColumn('special_price', array(
            'header' => Mage::helper('catalog')->__('Special Price'),
            'width' => '50px',
            'type' => 'price',
            'productid' => 'product_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_SpecialPrice',
            'filter' => false,
        ));

        $sellerAmount = array(
            'header' => Mage::helper('sales')->__('Seller\'s Earned Amount'),
            'align' => 'right',
            'index' => 'seller_amount',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'filter' => false,
        );
        $this->addColumn('seller_amount', $sellerAmount);
        $commissionFee = array(
            'header' => Mage::helper('sales')->__('Commission Fee'),
            'align' => 'right',
            'index' => 'commission_fee',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'filter' => false,
            'is_system' => true,
        );
        $this->addColumn('commission_fee', $commissionFee);

        $commissionPercentage = array(
            'header' => Mage::helper('sales')->__('Commission Percentage'),
            'align' => 'right',
            'index' => 'commission_percentage',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'filter' => false,
            'is_system' => true,
        );
        $this->addColumn('commission_percentage', $commissionPercentage);
        // Add column
        $order_amount = array(
            'header' => Mage::helper('sales')->__('Order total'),
            'align' => 'right',
            'index' => 'id',
            'width' => '80',
            'type' => 'price',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_getordertotalseller',
            'filter' => false,
            'is_system' => true,
        );
        $this->addColumn('order_amount', $order_amount);


        

        return parent::_prepareColumns();
    }

    /**
     * Function for link url
     *
     * Not redirected to any page
     * return void
     */
    public function getRowUrl($row)
    {
        // return $this->getUrl('adminhtml/sales_order/view/', array(
        //     'order_id' => $row->getOrderId(),
        // ));
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
            'type' => 'options',
            'options'=> $this->getSellers(),
            'sortable' => false,
            'is_system' => true,
            'filter_condition_callback' => array($this, '_sellerEmailFilterCallBack'),
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Sellerdetails',
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
            'index' => 'product_id',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderitemsku',
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
            'type' => 'price',
            'filter'   => false,
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
                "shipped_from_elabelz" => "Shipped from Elabelz",
                "successful_delivery_partially" => "Successful Delivery Partially",
                "successful_delivery,successful_delivery_partially,complete" => "Successful Delivery Partially,Successful Delivery,Completed Non Refundable",
                "shipped_from_elabelz,refunded,failed_delivery" => "Shipped from Elabelz,Refunded,Failed Delivery",
                "failed_delivery" => "Failed Delivery",
                "successful_delivery" => "Successful Delivery",
                "complete" => "Completed Non Refundable",
                "refunded" => "Refunded",
                "closed" => "Closed",
                "canceled" => "Canceled",
            ),
            'filter_condition_callback' => array($this, '_orderStatusFilterCallBack'),
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
                "canceled" => "Canceled",
            ),
            'is_system' => true,
        );
        $this->addColumn('item_order_status', $orderItemStatus);

    }


    public function _sellerEmailFilterCallBack($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $this->getCollection()->addFieldToFilter('seller_id',$value);

            return $this;


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

    public function _orderStatusFilterCallBack($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $value = $column->getFilter()->getValue();
        $status = explode(",",$value);
        
        $this->getCollection()->addFieldToFilter('main_table.order_status', array(
                'in' => array($status),
            ));

        return $this;
    }

}