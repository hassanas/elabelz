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
class Apptha_Marketplace_Block_Adminhtml_Logistics_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Construct the inital display of grid information
     * Set the default sort for collection
     * Set the sort order as "DESC"
     *
     * Return array of data to view order information
     *
     * @return array
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'logisticsGrid' );
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
    protected function _prepareCollection() {
        /** Commission Get Collection */
         $sellerAcceptedIncrementIds = array();
         $sellerPendingIncrementIds = array();
         $orders = Mage::getModel ( 'marketplace/commission' )->getCollection ()
         ->addFieldToSelect ( '*' )
         ->setOrder ( 'created_at', 'DESC' )
         ->addFieldToFilter('is_buyer_confirmation','Yes')
         ->addFieldToFilter('is_seller_confirmation','Yes');
          foreach ($orders as $collection) {          
               array_push($sellerAcceptedIncrementIds, $collection->getIncrementId());
         }

        $sellerAcceptedIncrementIds = array_unique($sellerAcceptedIncrementIds);
        // print_r($sellerAcceptedIncrementIds);
        $orders_pending = Mage::getModel ( 'marketplace/commission' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->setOrder ( 'created_at', 'DESC' )
        ->addFieldToFilter('is_buyer_confirmation','No')
        ->addFieldToFilter('is_seller_confirmation','No');
         foreach ($orders_pending as $collection) {     
              array_push($sellerPendingIncrementIds, $collection->getIncrementId());
        }

        $sellerPendingIncrementIds = array_unique($sellerPendingIncrementIds);
        $result = array_diff($sellerAcceptedIncrementIds,$sellerPendingIncrementIds);

        $orders_new = Mage::getModel ( 'marketplace/commission' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->addFieldToFilter( 'is_buyer_confirmation', array ('eq' => 'Yes'))
        ->addFieldToFilter( 'is_seller_confirmation', array ('eq' => 'Yes'))
        ->addFieldToFilter( 'item_order_status', array ('neq' => 'canceled'))
        ->addFieldToFilter( 'order_status', array ('nin' => array('canceled','shipped_from_elabelz','successful_delivery','successful_delivery_partially','failed_delivery','complete')))
        ->setOrder ( 'created_at', 'DESC' )
        ->addFieldToFilter ('main_table.increment_id',array (
            'in' => array($result)));   
        /*$awaitingForCustomer = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                             ->addFieldToSelect ( '*' )
                             ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'Yes'))
                             ->addFieldToFilter ( 'is_seller_confirmation', array ('eq' => 'No'))
                             ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')))
                             ->addFieldToFilter ( 'item_order_status', array ('neq' => 'canceled'))
                             ->addFieldToFilter ( 'order_status', array ('neq' => 'canceled'))
                             ->setOrder ( 'created_at', 'DESC' );*/
        $this->setCollection ( $orders_new );
        
        $this->addExportType('*/*/exportCsv', Mage::helper('marketplace')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('marketplace')->__('EXCEL'));
        return parent::_prepareCollection ();
    }
    public function shippingCountriesList(){
        $stores = Mage::app()->getStores();
           $country = array();
           foreach($stores as $store){
                $code = $store->getCode();
                $codeArray = explode('en_',$code);
                if(count($codeArray) > 0){
                    $countryList = Mage::getModel('directory/country')->getResourceCollection()->loadByStore()->toOptionArray(true);
                    foreach($countryList as $list){
                         if($list['value']==strtoupper($codeArray[1])){
                            if(strtoupper($codeArray[1])!=''){
                                $country[strtoupper($codeArray[1])] =$list['label'];
                            }
                         }
                    }
                }  
            }
            return $country;
            
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
     * Function to display fields with data
     *
     * Display information about orders
     *
     * @return void
     */
    protected function _prepareColumns() {
        /** Get Store */
        $store = Mage::app ()->getStore ();
        $orderCreatedAt = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Order At' ),
            'index' => 'created_at',
            'type' => 'datetime',
            'align' => 'left',
            'width' => '100px'
        );
        $this->addColumn ( 'created_at', $orderCreatedAt );
       
        
        $incrementId = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
                'width' => '100px',
                'align' => 'center',
                'index' => 'increment_id' 
        );
        $this->addColumn ( 'increment_id', $incrementId );

        $sku = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Product Sku' ),
            'align' => 'left',
            'width' => '350px',
            'index' => 'product_id',  
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Logistics_ProductSku'
        );
        $this->addColumn ( 'sku', $sku );

        $qty = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Qty' ),
            'align' => 'center',
            'width' => '10px',
            'index' => 'product_qty',
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Logistics_ProductQty'
        );
        $this->addColumn ( 'qty', $qty );

         $seller_name = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Supplier Name' ),
            'align' => 'left',
            'width' => '300px',
            'type'    => 'options',
            'index' => 'seller_id',
            'options'=> $this->getSellers(),
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_SellerName',  
            'filter_condition_callback' => array($this, '_sellerEmailFilterCallBack')
        );
       $this->addColumn ( 'seller_name', $seller_name );

        $notes = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Notes' ),
            'align' => 'center',
            'width' => '200px',
            'index' => 'id',
            'filter' => false,
            'is_system' => true,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Notes'
        );
        $this->addColumn ( 'notes', $notes );

        $item_status = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Supplier Status' ),
            'align' => 'center',
            'width' => '200px',
            'type'    => 'options',
            'options' => array(
                "Yes" => "Confirmed",
                "No"  => "Need to Contact",
                "Rejected" => "Out of Stock"
            ),
            'index' => 'id',
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Widget_Grid_Column_Renderer_Options'
        );
        $this->addColumn ( 'item_status', $item_status );

        $this->addColumn('id', array(
            'header' => Mage::helper('marketplace')->__('Ship Country'),          
            'width' => '200px',
            'type'    => 'options',
            'align' => 'center',
            'index' => 'order_id',
            'options' => $this->shippingCountriesList(),
            'filter_condition_callback' => array($this, '_countryFilterCallBack'), 
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_ShippingCountry'
        ));

        $this->addColumn('seller_status', array(
            'header' => Mage::helper('marketplace')->__('Seller Status'),          
            'width' => '200px',
            'type'    => 'options',
            'align' => 'center',
            'index' => 'id',
            'options' => array(
                "0" => "Not Picked From Seller",
                "1"  => "Picked From Seller"
            ),
            'filter_condition_callback' => array($this, '_sellerFilterCallBack'), 
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Sellerstatus'
        ));

         $ship_status = array (
            'header' => Mage::helper ( 'marketplace' )->__ ( 'Ship Status' ),
            'align' => 'center',
            'type'    => 'options',  
            'width' => '200px',
            'options' => array(
                "0" => "Pending",
                "1"  => "Shipped in House",
                "2" => "Shipped Via Fetchr",
                "3" => "Shipped Via Aramex"
            ),
            'index' => 'id',
            'width' => '200px',
            'filter_condition_callback' => array($this, '_statusFilterCallBack'),
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Widget_Grid_Column_Renderer_Ship'
        );
        $this->addColumn ( 'ship_status', $ship_status );

        return parent::_prepareColumns ();
    }
    
   public function _createdAtFilterCallBack ($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
            $value = $column->getFilter()->getValue(); 
            //print_r($value);
            $dateStart = $value['orig_from'];
            $dateEnd = $value['orig_to'];
            $dateStart = Mage::getModel('core/date')->date('Y-m-d', $dateStart);
            $dateEnd = Mage::getModel('core/date')->date('Y-m-d', $dateEnd);
            $this->getCollection()->addFieldToFilter('main_table.created_at', array('from' => $dateStart, 'to' => $dateEnd));
            
            return $this;
        }
    }
     
     public function _sellerEmailFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
            $value = $column->getFilter()->getValue();

            $this->getCollection()->addFieldToFilter ('main_table.seller_id',$value);
            //$this->setCollection ( $orders_new );
            return $this;
        
    }

    public function _countryFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{

               $value = $column->getFilter()->getValue();
               $condition = new Zend_Db_Expr("main_table.order_id = sales_address.parent_id AND sales_address.address_type =  'shipping' AND sales_address.country_id ='$value'");
               $this->getCollection()->getSelect()->join(array('sales_address' => $this->getCollection()->getTable('sales/order_address')),
               $condition,
               array('country_id' => 'sales_address.country_id')); 

           return $this;
        }
    }

    public function _sellerFilterCallBack($collection, $column) {
        $value = $column->getFilter()->getValue();
        if($value == 0){
           $this->getCollection()->addFieldToFilter ('main_table.seller_status',$value); 
        }
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
               $value = $column->getFilter()->getValue();
               $this->getCollection()->addFieldToFilter ('main_table.seller_status',$value);
           return $this;
        }
    }

    public function _statusFilterCallBack($collection, $column) {
        $value = $column->getFilter()->getValue();
        if($value == 0){
           $this->getCollection()->addFieldToFilter ('main_table.ship_status',$value); 
        }
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
               $value = $column->getFilter()->getValue();
               $this->getCollection()->addFieldToFilter ('main_table.ship_status',$value);
           return $this;
        }
    }
}
