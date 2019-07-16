<?php

class Apptha_Marketplace_Block_Adminhtml_Supplier_Sales_Date_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct ();
        $this->setId ( 'generalsalesdateGrid' );
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
        $order = Mage::getModel("sales/order")->getCollection()
            ->addFieldToSelect ('*')
            ->addFieldToFilter("status", array(
                array("eq"=>"shipped_from_elabelz"), 
                array("eq"=>"successful_delivery"), 
                array("eq"=>"failed_delivery"), 
                array("eq"=>"closed")
                )
            )
            ->setOrder ( 'created_at', 'DESC' );

        $idx = [];
        foreach ($order as $row) {
            $idx[] = $row->getEntityId();
        }
        
// shipped_from_elabelz
// successful_delivery
// failed_delivery
// sort price
// dir asc or desc

        $mc = Mage::getModel('marketplace/commission')->getCollection()
        ->addFieldToSelect('*')
        ->addFieldToFilter('order_id', array('in' => $idx));

        $this->setCollection ( $mc );
        return parent::_prepareCollection ();
    }

    protected function _prepareColumns()
    {
        $store = Mage::app ()->getStore ();
        $rangeDate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'From' ),
            'width'    => '250px',
            'index'    => 'created_at',
            'type'     => 'datetime',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdateformat'
        );
        $this->addColumn ('range_date', $rangeDate);

        $supplier = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Supplier' ),
            'width'    => '250px',
            'index'    => 'seller_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Supplier'
        );
        $this->addColumn ('supplier', $supplier);

        $incrementId = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
            'width'  => '100px',
            'index'  => 'increment_id'
        );
        $this->addColumn ('increment_id', $incrementId );

        $sku = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Product Details' ),
            'width'    => '100px',
            'index'    => 'id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Product'
        );
        $this->addColumn ('sku', $sku );

        $price = array (
            'header'        => Mage::helper ( 'sales' )->__ ( 'Price' ),
            'width'         => '100px',
            'index'         => 'product_amt',
            'type'          => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode()
        );
        $this->addColumn ('price', $price );

        $discount = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Discount amount' ),
            'width'  => '100px',
            'index'  => 'increment_id'
        );
        // $this->addColumn ('discount', $discount );

        $paymentToSupplier = array (
            'header'        => Mage::helper ( 'sales' )->__ ( 'Supplier Amount' ),
            'width'         => '100px',
            'index'         => 'seller_amount',
            'type'          => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode()
            // 'currency' => 'order_currency_code'
        );
        $this->addColumn ('payment_to_supplier', $paymentToSupplier );

        $paymentToElabelz = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Elabelz Amount' ),
            'width'  => '100px',
            'index'  => 'commission_fee',
            'type'          => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode()
        );
        $this->addColumn ('range_date', $rangeDate);

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
        // $this->addColumn ( 'order_status',$orderStatus);

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

