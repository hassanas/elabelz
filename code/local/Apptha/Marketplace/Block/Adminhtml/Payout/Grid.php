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
 * Display payemnt information
 */
class Apptha_Marketplace_Block_Adminhtml_Payout_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Construct the inital display of grid information
     * Set the default sort for collection
     * Set the sort order as "DESC"
     *
     * Return array of data to display order information
     *  hi
     * @return array
     */
    public function __construct() {
        //echo  '<br>Payout Grid';
        parent::__construct ();
        $this->setId ( 'payoutGrid' );
        $this->setDefaultSort ( 'entity_id' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }
    
    /**
     * Function to get commission payment collection
     *
     * Return the seller commission payment information
     * return array
     */
    protected function _prepareCollection() {
       // echo  '<br>Payout Grid';
        $seller_id = $this->getRequest ()->getParam ( 'id' );
        $collection = Mage::getModel ( 'marketplace/payout' )->getCollection ()
        ->addFieldToFilter ( 'seller_id', $seller_id );
        $this->setCollection ( $collection );
        return parent::_prepareCollection ();
    }
    
    /**
     * Function to display fields with data
     *
     * Display information about payment
     *
     * @return void
     */
    protected function _prepareColumns() {
        $id = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Payout ID' ),
                'width' => '100px',
                'index' => 'id' 
        );
        $this->addColumn ( 'id', $id );
        $sellerId = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Seller ID' ),
                'width' => '100px',
                'index' => 'seller_id' 
        );
        $this->addColumn ( 'seller_id', $sellerId);
         $store_title = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Store title' ),
                'width' => '100px',
                'index' => 'seller_id',
                 'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_SellerStoreName' 
        );
        $this->addColumn ( 'store_title', $store_title);
        $request_amount = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Request Amount' ),
                'width' => '100px',
                'index' => 'request_amount' 
        );
        $this->addColumn ( 'request_amount', $request_amount );
        $status = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Status' ),
                'width' => '100px',
                'type' => 'options',
                'index' => 'status',
                'options' => Mage::getSingleton ( 'marketplace/status_status' )->getOptionPayoutRequestArray ()
        );
        $this->addColumn ( 'status', $status );
        $this->addColumn ( 'action', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'action' ),
                'type' => 'action',
                'width' => '100px',
                'getter' => 'getId',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Pending' ),
                                'url' => array (
                                'base' => 'marketplaceadmin/adminhtml_payout/edit',
                                'params'=>array('seller_id'=>$this->getRequest()->getParam('id'),
                                                'payout_status'=>'pending')
                                        ),
                                'field' => 'id',
                                'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' ) 
                        ),
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Approve' ),
                                'url' => array (
                                'base' => 'marketplaceadmin/adminhtml_payout/edit',
                                'params'=>array('seller_id'=>$this->getRequest()->getParam('id'),
                                                'payout_status'=>'approve')
                                        ),
                                'field' => 'id',
                                'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' )
                        ),
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Disapprove' ),
                                'url' => array (
                                'base' => 'marketplaceadmin/adminhtml_payout/edit',
                                'params'=>array('seller_id'=>$this->getRequest()->getParam('id'),
                                                'payout_status'=>'disapprove')
                                        ),
                                'field' => 'id',
                                'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' ) 
                        ),
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Paid' ),
                                'url' => array (
                                'base' => 'marketplaceadmin/adminhtml_payout/edit',
                                'params'=>array('seller_id'=>$this->getRequest()->getParam('id'),
                                                'payout_status'=>'paid')
                                        ),
                                'field' => 'id',
                                'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' ) 
                        )
                ),
                'sortable' => false 
        ) );
        $ack = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Ack' ),
                'width' => '100px',
                'type' => 'options',
                'align' => 'center',
                'index' => 'ack',
                'options' => Mage::getSingleton ( 'marketplace/status_status' )->getOptionYesNoArray ()
        );
        $this->addColumn ( 'ack', $ack);
        $created_at = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Created At' ),
                'width' => '100px',
                'align' => 'center',
                'index' => 'created_at',
                'gmtoffset' => true 
        );
        $this->addColumn ( 'created_at', $created_at);
        $updated_at = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Updated At' ),
                'align' => 'center',
                'width' => '100px',
                'index' => 'updated_at',
                'gmtoffset' => true 
        );
        $this->addColumn ( 'updated_at', $updated_at );
        $seller_comment = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Seller comment' ),
                'width' => '100px',
                'align' => 'left',
                'index' => 'seller_comment',
                'gmtoffset' => true 
        );
        $this->addColumn ( 'seller_comment', $seller_comment);
         $admin_comment = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Admin comment' ),
                'width' => '100px',
                'align' => 'left',
                'index' => 'admin_comment',
                'gmtoffset' => true 
        );
        $this->addColumn ( 'admin_comment', $admin_comment);
        $this->addExportType ( '*/*/exportCsv', Mage::helper ( 'customer' )->__ ( 'CSV' ) );
        $this->addExportType ( '*/*/exportXml', Mage::helper ( 'customer' )->__ ( 'Excel XML' ) );
        return parent::_prepareColumns ();
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