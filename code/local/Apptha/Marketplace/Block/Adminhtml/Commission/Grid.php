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
 * 
 */

/**
 * Seller commision grid
 * Display seller commission in admin grid using commision table
 */
class Apptha_Marketplace_Block_Adminhtml_Commission_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Construct the inital display of grid information
     * Set the default sort for collection
     * Set the sort order as "DESC"
     *
     * Return array of data to with seller commission information
     * 
     * @return array
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'commissionGrid' );
        $this->setDefaultSort ( 'entity_id' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }
    
    /**
     * Get collection from commission table
     *
     * Return array of data to with seller commission information
     * 
     * @return array
     */
    protected function _prepareCollection() {
        $gid = Mage::helper ( 'marketplace' )->getGroupId ();
        $collection = Mage::getResourceModel ( 'customer/customer_collection' )->addNameToSelect ()->addAttributeToSelect ( 'email' )->addAttributeToSelect ( 'created_at' )->addAttributeToSelect ( 'group_id' )->addFieldToFilter ( 'group_id', $gid );
        $this->setCollection ( $collection );
        return parent::_prepareCollection ();
    }
    
    /**
     * Display the Seller Commission in grid
     *
     * Display information about Seller Commission
     * 
     * @return void
     */
    protected function _prepareColumns() {
        $entityId = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Seller ID' ),
                'width' => '40px',
                'index' => 'entity_id',
                'type' => 'number' 
        );
        $this->addColumn ( 'entity_id', $entityId );
        $name = array (
                'header' => Mage::helper ( 'customer' )->__ ( 'Name' ),
                'width' => '200px',
                'index' => 'name' 
        );
        $this->addColumn ( 'name', $name );
        $email = array (
                'header' => Mage::helper ( 'customer' )->__ ( 'Email' ),
                'width' => '200px',
                'index' => 'entity_id',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Ordersellerdetails' 
        );
        $this->addColumn ( 'email', $email );
        $amountReceived = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Amount Received' ),
                'align' => 'right',
                'index' => 'entity_id',
                'width' => '150px',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Amountreceived' 
        );
        $this->addColumn ( 'amount_received', $amountReceived );
        $amountRemaining = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Amount Remaining' ),
                'align' => 'right',
                'index' => 'entity_id',
                'width' => '150px',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Amountremaining' 
        );
        $this->addColumn ( 'amount_remaining', $amountRemaining );
        $paymentMode = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Payment Mode' ),
                'align' => 'left',
                'index' => 'entity_id',
                'filter' => false,
                'width' => '200px',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Paymentmode' 
        );
        $this->addColumn ( 'payment_mode', $paymentMode );
        /**
         * Pay action
         */
        $action = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Actions' ),
                'align' => 'center',
                'width' => '100',
                'index' => 'id',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Payment' 
        );
        $this->addColumn ( 'action', $action );
        $paymentComment = array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Comments' ),
                'align' => 'right',
                'index' => 'entity_id',
                'filter' => false,
                'width' => '200px',
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Paymentcomment' 
        );
        $this->addColumn ( 'payment_comment', $paymentComment );
        $customerSince = array (
                'header' => Mage::helper ( 'customer' )->__ ( 'Customer Since' ),
                'type' => 'datetime',
                'align' => 'center',
                'index' => 'created_at',
                'filter' => false,
                'sortable' => false,
                'gmtoffset' => true 
        );
        $this->addColumn ( 'customer_since', $customerSince );
        return parent::_prepareColumns ();
    }
    
    /**
     * Provide the link url of the data displayed
     *
     * Link url is set to payment grid
     * 
     * @return string
     */
    public function getRowUrl($row) {
        return $this->getUrl ( 'marketplaceadmin/adminhtml_paymentinfo/index/', array (
                'id' => $row->getId () 
        ) );
    }
}
