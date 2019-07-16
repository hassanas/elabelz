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
class Apptha_Marketplace_Block_Adminhtml_Ordercount_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
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
        $this->setId ( 'ordercountGrid' );
        $this->setDefaultSort ( 'created_at' );
        $this->setDefaultDir ( 'DESC' );
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setSaveParametersInSession ( true );
    }
      protected function _getCollectionClass()
    {
        return 'sales/order_grid_collection';
    }
    /**
     * Function to get order collection
     *
     * Return the seller product's order information
     * return array
     */
    protected function _prepareCollection() {
        /** Commission Get Collection */
    $collection = Mage::getResourceModel($this->_getCollectionClass());
    $collection->addFieldToFilter('entity_id', array('eq' => '310'));
    $this->setCollection($collection);
    return parent::_prepareCollection();
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
       
         $this->addColumn('customer', array(
            'header' => Mage::helper('marketplace')->__('Awaiting For Customer'),
            'index' => 'entity_id',
            'width' => '100px',
            'filter' => false,            
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Ordercount_AwaitingForCustomer' 
        ));
         $this->addColumn('seller', array(
            'header' => Mage::helper('marketplace')->__('Awaiting For Seller'),
            'index' => 'entity_id',
            'width' => '100px',
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Ordercount_AwaitingForSeller' 
        ));
         $this->addColumn('Processing', array(
            'header' => Mage::helper('marketplace')->__('Orders In Processing'),
            'index' => 'entity_id',
            'width' => '100px',
            'filter' => false,            
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Ordercount_OrderInProcessing' 
        ));
         $this->addColumn('shipment', array(
            'header' => Mage::helper('marketplace')->__('Awaiting for Shipment'),
            'index' => 'entity_id',
            'width' => '100px',
            'filter' => false,
            'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Ordercount_AwaitingForShipment' 
        ));
        

        return parent::_prepareColumns ();
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
     public function _createdAtFilterCallBack ($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }else{
              $value = $column->getFilter()->getValue(); 
             $dateStart = $value['orig_from'];
             $dateEnd = $value['orig_to'];
             $dateStart = Mage::getModel('core/date')->date('Y-m-d', $dateStart);
             $dateEnd = Mage::getModel('core/date')->date('Y-m-d', $dateEnd);
             $this->getCollection()->addFieldToFilter('main_table.created_at', array('from' => $dateStart, 'to' => $dateEnd));
            return $this;
        }
    }
        protected function _prepareMassaction() {
        /**
         * set Entity Id
         */
        $this->setMassactionIdField ( 'id' );
        /**
         * Set Form Field
         */
        $this->getMassactionBlock ()->setFormFieldName ( 'marketplace' );
        /**
         * Add custom column delete
         */
        $this->getMassactionBlock ()->addItem ( 'delete', array (
                'label' => Mage::helper ( 'marketplace' )->__ ( 'Delete' ),
                'url' => $this->getUrl ( '*/*/massDelete' ),
                'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' ) 
        ) );
        /**
         * Add custom column approve
         */
        $this->getMassactionBlock ()->addItem ( 'Approve', array (
                'label' => Mage::helper ( 'customer' )->__ ( 'Approve' ),
                'url' => $this->getUrl ( '*/*/massApprove' ) 
        ) );
        /**
         * Add custom column disapprove
         */
        $this->getMassactionBlock ()->addItem ( 'disapprove', array (
                'label' => Mage::helper ( 'customer' )->__ ( 'Disapprove' ),
                'url' => $this->getUrl ( '*/*/massDisapprove' ) 
        ) );
        return $this;
    }
}

