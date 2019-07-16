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
class Apptha_Marketplace_Block_Adminhtml_Manageroverview_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
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
        $this->setId ( 'manageroverviewGrid' );
        $this->setDefaultSort ( 'created_at' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }
      protected function _getCollectionClass()
    {
        return 'sales/order_grid_collection';
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
    /**
     * Function to get order collection
     *
     * Return the seller product's order information
     * return array
     */
    protected function _prepareCollection() {
        $ordersItems = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                         ->addFieldToSelect ( 'increment_id' )
                         ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')));
        $increment_ids = array();
        foreach($ordersItems as $item){
            array_push($increment_ids, $item->getIncrementId());
        }
        $increment_ids = array_unique($increment_ids);
        $collection = Mage::getResourceModel($this->_getCollectionClass())
                        ->addFieldToFilter ( 'increment_id', array ('in' => $increment_ids));
        
        $this->setCollection($collection);
        $this->addExportType('*/*/exportCsv', Mage::helper('marketplace')->__('CSV'));
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
        /** Get Store */
        $store = Mage::app ()->getStore ();

         $this->addColumn('id', array(
            'header' => Mage::helper('marketplace')->__(''),          
            'width' => '10px',
            'type'    => 'options',
            'align' => 'center',
            'options' => $this->shippingCountriesList(),
            'filter_condition_callback' => array($this, '_countryFilterCallBack'),
             'is_system'   => true
        ));
         $this->addColumn('created_at', array(
            'header' => Mage::helper('marketplace')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'align' => 'center',
            'width' => '100px',
            'filter_condition_callback' => array($this, '_createdAtFilterCallBack'),
        ));
       $this->addColumn ( 'increment_id', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Order #' ),
                'width' => '10px',
                'index' => 'entity_id',
                'align' => 'center',
                'filter_condition_callback' => array($this, '_orderIdFilterCallBack'),
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Orderid' 
        ) );

         $this->addColumn ( 'customer', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Customer' ),
                'width' => '10px',
                'index' => 'entity_id',
                'align' => 'center',
                'type' => 'options',
                'options' => array(
                            "No" => "Pending",
                            "Yes" => "Confirmed",                   
                ),
                'filter_condition_callback' => array($this, '_customerFilterCallBack'),
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Customer',
        ) );

         $this->addColumn ( 'customer_confirmed', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Customer Confirmed' ),
                'width' => '10px',
                'index' => 'entity_id',
                'align' => 'center',
                'type' => 'options',
                'is_system' => true,
                'options' => array(
                            12 => "Last 12 hours",
                            24 => "Last 24 hours",
                            48 => "Last 48 hours",
                            72 => "Last 72 hours",
                            ),
                'filter_condition_callback' => array($this, '_customerConfirmFilterCallBack'),
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Closed',
        ) );

        $this->addColumn ( 'supplier', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Supplier' ),
                'width' => '10px',
                'index' => 'entity_id',
                'align' => 'center',
                'type' => 'options',
                'options' => array(
                            "No" => "Pending",
                            "Yes" => "Confirmed",                 
                ),
                'filter_condition_callback' => array($this, '_sellerFilterCallBack'),
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Supplier',
        ) );
        $this->addColumn ( 'shipped', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Shipped' ),
                'width' => '10px',
                'index' => 'entity_id',
                'align' => 'center',
                'type' => 'options',
                'options' => array(
                             "processing" => "Procesing",
                             "shipped_from_elabelz" => "Shipped From Elabelz",
                            ),
                'filter_condition_callback' => array($this, '_shippedFilterCallBack'),
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Shipped',
        ) );
        $this->addColumn ( 'closed', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Closed' ),
                'width' => '10px',
                'index' => 'entity_id',
                'align' => 'center',
                'type' => 'options',
                'options' => array(
                            "all" => "All orders",
                            "not_canceled" => "All non-cancelled orders",
                            "successful_delivery" => "Successful Delivery",
                            ),
                'filter_condition_callback' => array($this, '_closedFilterCallBack'),
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Closed',
        ) );

        $this->addColumn ( 'customer_confirmed_date', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Customer Confirmed Date' ),
                'width' => '10px',
                'index' => 'entity_id',
                'column_css_class'=>'no-display',
                'header_css_class'=>'no-display',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Date',
        ) );

         $this->addColumn ( 'customer_confirmed_time', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Customer Confirmed Time' ),
                'width' => '10px',
                'column_css_class'=>'no-display',
                'header_css_class'=>'no-display',
                'index' => 'entity_id',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Time',
        ) );

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
            'width' => '5px',
            'sortable' => false,
            'index'    => 'stores',
            'is_system'   => true
        ));
        return parent::_prepareColumns ();
    }

        public function getRowUrl($row) {
            return false;
    }
    public function _countryFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
              $value = $column->getFilter()->getValue();
              $address = Mage::getModel('sales/order_address')->getCollection ()
                 ->addFieldToSelect ( 'parent_id' )
                 ->addFieldToFilter('country_id', array ('eq' => $value))
                 ->addFieldToFilter('address_type', array ('eq' => 'shipping'));
              $orderids = array();
              foreach($address as $ad){
                array_push($orderids, $ad->getParentId());
              }
              $orderids = array_unique($orderids);
              $this->getCollection()->addFieldToFilter ('main_table.entity_id', array('in' => $orderids));
            
            return $this;
        }
    }
    public function _orderIdFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
             $value = $column->getFilter()->getValue();
             $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $value));
            return $this;
        }
    }
    public function _customerFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
             $value = $column->getFilter()->getValue();
            
            $data = array();
            if($value == 'Yes'){//Again Verify any order have other item not in Pending
                 $isBuyerConfirmation = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                ->addFieldToSelect ( 'increment_id' )
                ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')))
                ->addFieldToFilter ( 'is_buyer_confirmation', array ('nin' => array('No','Rejected')));

                $increment_ids = array();
                $increment_ids2 = array();
                foreach($isBuyerConfirmation as $item){
                    array_push($increment_ids, $item->getIncrementId());
                }
                $increment_ids = array_unique($increment_ids);   

                $ordersFilter = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                            ->addFieldToSelect ( 'increment_id' )
                            ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'No'))
                            ->addFieldToFilter ( 'increment_id', array ('in' => $increment_ids ));

                foreach($ordersFilter as $item){
                    array_push($increment_ids2, $item->getIncrementId());
                }

                $data = array_diff($increment_ids, $increment_ids2);                 
            }else if($value == 'No'){               
                $isBuyerConfirmation = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                                    ->addFieldToSelect ( 'increment_id' )
                                    ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')))
                                    ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => $value)); 
                foreach($isBuyerConfirmation as $result){
                    array_push($data, $result->getIncrementId());
                }
            }
            
            array_unique($data);

            $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $data));
            return $this;
        }
    }
    public function _sellerFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
             $value = $column->getFilter()->getValue();
            
            $data = array();
            if($value == 'Yes'){//Again Verify any order have other item not in Pending
                 $isBuyerConfirmation = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                ->addFieldToSelect ( 'increment_id' )
                ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')))
                ->addFieldToFilter ( 'is_seller_confirmation', array ('nin' => array('No','Rejected')))
                ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'Yes'));

                $increment_ids = array();
                $increment_ids2 = array();

                foreach($isBuyerConfirmation as $item){
                    array_push($increment_ids, $item->getIncrementId());
                }
                $increment_ids = array_unique($increment_ids);   

                $ordersFilter = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                            ->addFieldToSelect ( 'increment_id' )
                            ->addFieldToFilter ( 'is_seller_confirmation', array ('eq' => 'No'))
                            ->addFieldToFilter ( 'increment_id', array ('in' => $increment_ids ));

                foreach($ordersFilter as $item){
                    array_push($increment_ids2, $item->getIncrementId());
                }

                $data = array_diff($increment_ids, $increment_ids2);               
            }else if($value == 'No'){               
                $isBuyerConfirmation = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                                    ->addFieldToSelect ( 'increment_id' )
                                    ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')))
                                    ->addFieldToFilter ( 'is_seller_confirmation', array ('eq' => $value))
                                    ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'Yes')); 
                foreach($isBuyerConfirmation as $result){
                    array_push($data, $result->getIncrementId());
                }
            }
            
            array_unique($data);
            $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $data));

            return $this;
        }
    }
    public function _createdAtFilterCallBack ($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
            $value = $column->getFilter()->getValue(); 
            $dateStart = $value['orig_from'];
            $dateEnd = $value['orig_to'];
            $dateStart = Mage::getModel('core/date')->date('Y-m-d 00:00:00', $dateStart);
            $dateEnd = Mage::getModel('core/date')->date('Y-m-d  23:59:59', $dateEnd);
             $this->getCollection()->addFieldToFilter('main_table.created_at', array('from' => $dateStart, 'to' => $dateEnd,'date'=>true));
            return $this;
        }
    }

    protected function _shippedFilterCallBack($collection, $column){
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else {
            $value = $column->getFilter()->getValue();
            $increment_ids = Mage::helper('marketplace/marketplace')->filterOnOrderStatus($value);
            $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $increment_ids));
            return $this;
        }
    }

    protected function _closedFilterCallBack($collection, $column){
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else {
            $value = $column->getFilter()->getValue();
            $increment_ids = Mage::helper('marketplace/marketplace')->filterOnCloseOrderStatus($value);
            $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $increment_ids));
            return $this;
        }
    }

    protected function _customerConfirmFilterCallBack($collection, $column){ 
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else {
            $value = $column->getFilter()->getValue();
            $increment_ids = Mage::helper('marketplace/marketplace')->filterOnCustomerConfirmedStatus($value);
            $this->getCollection()->addFieldToFilter ('main_table.increment_id', array('in' => $increment_ids));
            return $this;
        }
    }

}
