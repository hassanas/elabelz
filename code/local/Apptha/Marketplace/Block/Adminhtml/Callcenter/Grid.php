<?php

class Apptha_Marketplace_Block_Adminhtml_Callcenter_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct ();
        $this->setId ( 'callcenterGrid' );
        $this->setDefaultSort ( 'created_at' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }
    
    /**
     * Function to get order collection
     *
     * Return the seller product's order information
     * return array
     */
    protected function _prepareCollection()
    {
        $mc = Mage::getModel('marketplace/commission')->getCollection()
        ->addFieldToSelect ('*')
        ->addFieldToFilter("is_buyer_confirmation", array("neq"=>"Yes"));

        $increment_id = [];

        foreach($mc as $mc_row) {
            $increment_id[] = $mc_row->getIncrementId();
        }

        $increment_id = array_unique($increment_id);


        $orders = Mage::getModel("sales/order")->getCollection()
        ->addFieldToSelect ('*')
        ->addFieldToFilter("increment_id", array("in"=>$increment_id))
        ->addFieldToFilter("status", array(array("eq"=>"holded"), array("eq"=>"pending")))
        ->setOrder ( 'created_at', 'DESC' );

        $this->setCollection ( $orders );
        return parent::_prepareCollection ();
    }

    protected function _prepareColumns()
    {
        $editDate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Edit Date' ),
            'width'    => '200px',
            'index'    => 'updated_at',
            'type'     => 'datetime',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdateformat'
        );
        $this->addColumn ('edit_date', $editDate);
       
        $incrementId = array (
            'header'                    => Mage::helper ( 'sales' )->__ ( 'Order #' ),
            'width'                     => '100px',
            'index'                     => 'entity_id',
            'filter_condition_callback' => array($this, '_orderIdAndStatusCallBack'),
            'renderer'                  => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Orderid' 
        );
        $this->addColumn ('increment_id', $incrementId );

        $calllog = array (
            'header' => Mage::helper ( 'sales' )->__ ( 'Call Log' ),
            'width'  => '200px',
            'align' => 'center',
            'column_css_class' => 'calllog_col',
            'index' => 'entity_id',
            'sortable' => false,
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Calllog' 
        );
        $this->addColumn ('calllog', $calllog );

        $purchaseDate = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Purchased On' ),
            'width'    => '250px',
            'index'    => 'created_at',
            'type'     => 'datetime',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Orderdateformat'
        );
        $this->addColumn ('purchase_date', $purchaseDate);

        $shipToName = array (
            'header'   => Mage::helper ( 'marketplace' )->__ ( 'Ship to Name' ),
            'width'    => '250px',
            'index'    => 'increment_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Shipname'
        );
        $this->addColumn ('ship_to_name', $shipToName);

        $gtPurchased = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'G.T. (Purchased)' ),
            'align'    => 'right',
            'width'    => '100px',
            'index'    => 'grand_total',
            'type'     => 'currency',
            'currency' => 'order_currency_code' 
        );
        $this->addColumn('g_t_purchased', $gtPurchased);

        $gtPurchased = array (
            'header'   => Mage::helper ( 'sales' )->__ ( 'Action Required' ),
            'align'    => 'left',
            'width'    => '150px',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'entity_id',
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Action' 
        );
        $this->addColumn('g_t_purchased', $gtPurchased);

        $orderStatus = array (
            'header'  => Mage::helper ( 'customer' )->__ ( 'Order Status' ),
            'width'   => '200px',
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
                "closed"                        => "Closed",
                "canceled"                      => "Canceled"
            )
        );
        // $this->addColumn('order_status', $orderStatus);

        $actions = array (
            'caption' => Mage::helper ( 'marketplace' )->__ ( 'View' ),
            'url'     => array (
                'base' => 'adminhtml/sales_order/view/' 
            ),
            'field'    => 'order_id' 
        );
        // $this->addColumn ( 'view', array (
        //     'header'  => Mage::helper ( 'marketplace' )->__ (''),
        //     'type'    => 'action',
        //     'getter'  => 'getEntityId',
        //     'align'   => 'center',
        //     'actions' => array (
        //             $actions 
        //     ),
        //     'filter'   => false,
        //     'sortable' => false,
        //     'index'    => 'stores',
        // ));

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

