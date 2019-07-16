<?php

class Apptha_Marketplace_Block_Adminhtml_Supplier_Sales_General_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct ();
        $this->setId ( 'generalsalesGrid' );
        $this->setDefaultSort ( 'created_at' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }

    public function _activeCountries($collection, $column) {
        $value = $column->getFilter()->getValue();
        if ($value) {
            $object = Mage::getModel('sales/order_address')->getCollection ()
            ->addFieldToSelect ( 'parent_id' )
            ->addFieldToFilter('country_id', array ('eq' => $value));
            $object->getSelect()->distinct(true);
            $idx = array();
            foreach($object as $row){
                array_push($idx, $row->getParentId());
            }
            $idx = array_unique($idx);
            $this->getCollection()->addFieldToFilter ('main_table.entity_id', array('in' => $idx));
            return $this;
        }
    }

    public function _activePaymentMethods($collection, $column) {
        $value = $column->getFilter()->getValue();
        if ($value) {
            $object = Mage::getResourceModel('sales/order_payment_collection')
            ->addFieldToSelect ('*')
            ->addFieldToFilter('method', array ('eq' => $value));
            $object->getSelect()->distinct(true);
            $idx = array();
            foreach($object as $row){
                array_push($idx, $row->getParentId());
            }
            $idx = array_unique($idx);
            $this->getCollection()->addFieldToFilter ('main_table.entity_id', array('in' => $idx));
            return $this;
        }
    }
    /**
     * Function to get order collection
     *
     * Return the seller product's order information
     * return array
     */
    protected function _prepareCollection()
    {
        $orders = Mage::getModel("sales/order")->getCollection()
            ->addFieldToSelect ('*')
            ->addFieldToFilter("status", array(
                array("eq"=>"shipped_from_elabelz"), 
                array("eq"=>"successful_delivery"), 
                array("eq"=>"failed_delivery"), 
                array("eq"=>"closed")
                )
            )
            ->setOrder ( 'created_at', 'DESC' );

        $this->setCollection ( $orders );
        return parent::_prepareCollection ();
    }

    protected function _prepareColumns()
    {
        $incrementId = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
            'width'  => '100px',
            'index'  => 'increment_id'
        );
        $this->addColumn ('increment_id', $incrementId );

        $subTotal = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Sub Total (Base)' ),
            'width'    => '100px',
            'index'    => 'base_subtotal',
            'type'     => 'currency',
            'currency' => 'base_currency_code' 
        );
        $this->addColumn ('sub_total', $subTotal );

        $shippingRate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Shipping (Base)' ),
            'width'    => '100px',
            'index'    => 'base_shipping_amount',
            'type'     => 'currency',
            'currency' => 'base_currency_code' 
        );
        $this->addColumn ('shipping_rate', $shippingRate );

        $codRate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'COD' ),
            'width'    => '100px',
            'index'    => 'msp_base_cashondelivery_incl_tax',
            'type'     => 'currency',
            'currency' => 'base_currency_code' 
        );
        $this->addColumn ('cod_rate', $codRate );

        $discountAmount = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Discount (Base)' ),
            'width'    => '100px',
            'index'    => 'base_discount_amount',
            'type'     => 'currency',
            'currency' => 'base_currency_code' 
        );
        $this->addColumn ('discount_amount', $discountAmount );

        $discountCoupon = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Coupon Code' ),
            'width'    => '100px',
            'index'    => 'coupon_code'
        );
        $this->addColumn ('discount_coupon', $discountCoupon );

        $paymentType = array (
            'header'                    => Mage::helper ( 'sales' )->__ ( 'Payment Method' ),
            'width'                     => '100px',
            'index'                     => 'entity_id',
            'type'                      => 'options',
            'options'                   => Mage::helper('marketplace')->getActivePaymentMethods(),
            'filter_condition_callback' => array($this, '_activePaymentMethods'),
            'renderer'                  => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Payment'
        );
        $this->addColumn ('payment_type', $paymentType );

        $shippingType = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Shipping Method' ),
            'width'    => '100px',
            'index'    => 'entity_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Shipping'
        );
        $this->addColumn ('shipping_type', $shippingType );

        $shipToName = array (
            'header'   => Mage::helper ( 'marketplace' )->__ ( 'Ship to Name' ),
            'width'    => '250px',
            'index'    => 'entity_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Shipping_Name'
        );
        $this->addColumn ('ship_to_name', $shipToName);

        $shipToCountry = array (
            'header'                    => Mage::helper ( 'marketplace' )->__ ( 'Ship to Country' ),
            'width'                     => '250px',
            'index'                     => 'entity_id',
            'type'                      => 'options',
            'options'                   => Mage::helper('marketplace')->getCountries(),
            'filter_condition_callback' => array($this, '_activeCountries'),
            'renderer'                  => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Shipping_Country'
        );
        $this->addColumn ('ship_to_country', $shipToCountry);

        $orderStatus = array (
            'header'  => Mage::helper ( 'customer' )->__ ( 'Order Status' ),
            'width'   => '80',
            'type'    => 'options',
            'index'   => 'status',
            'options' => array(
                "pending"                       => "Pending Confirmation",
                "holded"                        => "On Hold",
                "processing"                    => "Processing",
                "shipped_from_elabelz"          => "Shipped from Elabelz",
                "successful_delivery_partially" => "Successful Delivery Partially",
                "failed_delivery"               => "Failed Delivery",
                "successful_delivery"           => "Successful Delivery",
                "complete"                      => "Completed Non Refundable",
                "refunded"                      => "Refunded",
                "pending_payment"               => "Pending Payment",
                "closed"                        => "Closed",
                "canceled"                      => "Canceled"
            )
        );
        $this->addColumn ( 'order_status',$orderStatus);

        $editDate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Edit Date' ),
            'width'    => '200px',
            'index'    => 'updated_at',
            'type'     => 'datetime',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdateformat'
        );
        $this->addColumn ('edit_date', $editDate);
       
        $purchaseDate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Purchased On' ),
            'width'    => '250px',
            'index'    => 'created_at',
            'type'     => 'datetime',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdateformat'
        );
        $this->addColumn ('purchase_date', $purchaseDate);

        $awb = array (
            'header'   => Mage::helper ( 'marketplace' )->__ ( 'AWB' ),
            'width'    => '250px',
            'index'    => 'entity_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Shipping_Tracker'
        );
        $this->addColumn ('awb', $awb);

        $actions = array (
            'caption' => Mage::helper ( 'marketplace' )->__ ( 'View' ),
            'url'     => array (
                'base' => 'adminhtml/sales_order/view/' 
            ),
            'field'    => 'order_id' 
        );
        $this->addColumn ( 'view', array (
            'header'  => Mage::helper ( 'marketplace' )->__ (''),
            'type'    => 'action',
            'getter'  => 'getEntityId',
            'align'   => 'center',
            'actions' => array (
                    $actions 
            ),
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
        ));

        return parent::_prepareColumns ();
    }
 
    public function getRowUrl($row)
    {
        return false;
        // return $this->getUrl ( 'adminhtml/sales_order/view/', array (
        //     'order_id' => $row->getEntityId() 
        // ));
    }

    public function _orderIdAndStatusCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        } else {
            $value = $column->getFilter()->getValue();
            if (preg_match("/\d/i", trim($value))) {
                $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $value));
            } else {
                $this->getCollection()->addFieldToFilter ('main_table.status', array('like' => '%' . $value . '%'));
            }
            return $this;
        }
    }

}

