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
 * Manage Sellers Grid
 */
class Apptha_Marketplace_Block_Adminhtml_Manageseller_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Construct the inital display of grid information
     * Set the default sort for collection
     * Set the sort order as "DESC"
     *
     * Return array of data to display seller information
     *
     * @return array
     */
    public function __construct() {
        parent::__construct ();
        /**
         * set id
         */
        $this->setId ( 'managesellerGrid' );
        /**
         * set default sort
         */
        $this->setDefaultSort ( 'entity_id' );
        /**
         * set default order
         */
        $this->setDefaultDir ( 'DESC' );
        /**
         * save parameters
         */
        $this->setSaveParametersInSession ( true );
    }
    
    /**
     * Function to get seller collection
     *
     * Return the seller information
     * return array
     */
    protected function _prepareCollection() {
        /**
         * get groupid
         */
        $gid = Mage::helper ( 'marketplace' )->getGroupId (); 
        /**
         * Get Customer Collection
         */
        // $collection = Mage::getModel('customer/customer')->load($gid, "group_id");
        // $collection = Mage::getResourceModel ( 'customer/customer_collection' )->addNameToSelect ()->addAttributeToSelect ( 'email' )->addAttributeToSelect ( 'created_at' )->addAttributeToSelect ( 'group_id' )->addAttributeToSelect ( 'customerstatus' )->addAttributeToSelect ( 'seller_id' )->addFieldToFilter ( 'group_id', $gid );
        $this->addExportType('*/*/exportCsv', Mage::helper('marketplace')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('marketplace')->__('EXCEL'));  
        
        $collection = Mage::getModel ( 'customer/customer' )
            ->getCollection()
            ->addAttributeToSelect ( '*' )
            ->addFieldToFilter ( 'group_id', $gid );
        /**
         * set Collection
         */
        $this->setCollection ( $collection );
        return parent::_prepareCollection ();
    }
   
    /**
     * Function to create column to grid
     *
     * @param string $id            
     * @return string colunm value
     */
    public function addCustomColumn($id) {
        switch ($id) {
            /** add id
             * 
             */
            case 'ID' :
            $entityId = array ('header' => Mage::helper ( 'customer' )->__ ( 'ID' ),'width' => '40px','index' => 'entity_id');
                $value = $this->addColumn ( 'entity_id',$entityId);
                break;
                /** add store name
                 *
                 */
            case 'Store Name' :
            $storeTitle = array ('header' => Mage::helper ( 'customer' )->__ ( 'Store Name' ),
                'width' => '150px',
                'index' => 'entity_id',
                'is_system' => true,
                'filter_condition_callback' => array($this, '_sellerEmailFilterCallBack'), 
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemsellerdetails');  
                $value = $this->addColumn ( 'store_title',$storeTitle);
                // Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemsellerdetails
                // Apptha_Marketplace_Block_Adminhtml_Renderersource_Storetitle
                break;

                /** add email
                 *
                 */
            case 'email' :
            $email = array ('header' => Mage::helper ( 'customer' )->__ ( 'email' ),'width' => '160px','index' => 'email','filter' => false);
                $value = $this->addColumn ( 'email',$email);
                break;
                /** add name
                 *
                 */
            case 'Name' :
            
            //$customer = Mage::getModel('customer/customer')->load($entity_id);
            //$cname = $customer->getName();
            $name = array ('header' => Mage::helper ( 'customer' )->__ ( 'Name' ),'width' => '200px','index' => 'firstname');
                $value = $this->addColumn ( 'name',$name);
                break;
                /** add contact
                 *
                 */
            case 'Contact' :
            $contact = array ('header' => Mage::helper ( 'customer' )->__ ( 'Contact' ),'width' => '150px','index' => 'contact',
                'filter' => false,'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Contact');
                $value = $this->addColumn ( 'contact',$contact);
                break;
                /** add customer
                 *
                 */
            case 'customer' :
                $customerSince = array ('header' => Mage::helper ( 'customer' )->__ ( 'Customer Since' ),'type' => 'datetime','width' => '150px',
                'align' => 'center','index' => 'created_at','gmtoffset' => true);
            $value = $this->addColumn ( 'customer_since',$customerSince);
                break;
                /** add total sales
                 *
                 */
            case 'Total Sales' :
            $totalSales = array ('header' => Mage::helper ( 'customer' )->__ ( 'Total Sale Price' ),'width' => '80px','index' => 'entity_id',
                'align' => 'right','filter' => false,'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Totalsales');
                $value = $this->addColumn ( 'total_sales',$totalSales);
                break;
                /** add received
                 *
                 */
            case 'Total Orders' :
            $totalSales = array ('header' => Mage::helper ( 'customer' )->__ ( 'Total Orders' ),'width' => '80px','index' => 'entity_id',
                'align' => 'right','filter' => false,'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Totalsales');
                $value = $this->addColumn ( 'total_sales',$totalSales);
                break;
                /** add received
                 *
                 */
            case 'Received' :
            $received = array ('header' => Mage::helper ( 'sales' )->__ ( 'Paid' ),'width' => '80px','align' => 'right','index' => 'entity_id',
                        'filter' => false,'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Amountreceived');
                $value = $this->addColumn ( 'amount_received',$received);
                break;
                /** add remaining
                 *
                 */
            case 'Remaining' :
                $remaining = array ('header' => Mage::helper ( 'sales' )->__ ( 'Payable' ),'width' => '80px','align' => 'right','index' => 'entity_id',
                'filter' => false,'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Amountremaining');
            $value = $this->addColumn ( 'amount_remaining',$remaining);
                break;
                /** add remaining
                 *
                 */
            case 'Total Commission' :
                $commission_ttl = array ('header' => Mage::helper ( 'sales' )->__ ( 'Total<br>Commision<br>Earned' ),'width' => '80px','align' => 'right','index' => 'entity_id',
                'filter' => false,'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Totalcommission');
            $value = $this->addColumn ( 'commission_ttl',$commission_ttl);
                break;
                /** add default
                 *
                 */
            default :
            $customerstatus = array ('header' => Mage::helper ( 'customer' )->__ ( 'Status' ),'width' => '150px','type' => 'options','index' => 'customerstatus',
            'options' => Mage::getSingleton ( 'marketplace/status_status' )->getOptionArray ());
                $value = $this->addColumn ( 'customerstatus',$customerstatus);
        }
        return $value;
    }
    
    /**
     * Function to display fields with data
     *
     * Display information about Seller
     *
     * @return void
     */
    protected function _prepareColumns() {
        /**
         * Add custom column id
         */
        $this->addCustomColumn ( 'ID' );

        /**
         * Export Summary Action
         */
        $actions = array (
                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Export Summary' ),
                'url' => array (
                        'base' => 'marketplaceadmin/adminhtml_order/export/' 
                ),
                'field' => 'id', 

        );
        $this->addColumn ( 'export_summary', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Export Summary' ),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array (
                        $actions 
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true 
        ) );

        /**
         * Add custom column store name
         */
        $this->addCustomColumn ( 'Store Name' );
        /**
         * Add custom column email
         */
        $this->addCustomColumn ( 'email' );
        /**
         * Add custom column name
         */
         $this->addCustomColumn ( 'Name' );
        /**
         * Add custom column total products
         */
        $this->addColumn ( 'total_products', array (
                'header' => Mage::helper ( 'customer' )->__ ( '#Products' ),
                'width' => '10px',
                'index' => 'total_products',
                'align' => 'center',
                'filter' => false,
                'is_system' => true,
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Totalproducts' 
        ) );
        /**
         * Add custom column commission
         */
        $this->addColumn ( 'commission', array (
                'header' => Mage::helper ( 'customer' )->__ ( 'Commission(%)' ),
                'width' => '10px',
                'index' => 'commission',
                'align' => 'center',
                'filter' => false,
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Commissionrate' 
        ) );

        /**
         * Add custom column total sales
         * */
        $this->addCustomColumn ( 'Total Sales' );
        /**
         * Add custom column total commission
         * */
        $this->addCustomColumn ( 'Total Commission' );
        /**
         * Add custom column received
         */
        $this->addCustomColumn ( 'Received' );
        /**
         * Add custom column remaning
         */
        $this->addCustomColumn ( 'Remaining' );
        
        /**
         * Add custom column status
         */
       /*$status = array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Payout Status' ),
                'width' => '100px',
                'type' => 'action',
                'index' => 'entity_id',
                'filter' => false,    
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_PayoutRequest', 
                //'options' => Mage::getSingleton ( 'marketplace/status_status' )->getOptionPayoutRequestArray ()
        );
        $this->addColumn ( 'status', $status );*/
        $this->addCustomColumn ( 'Status' );
        $this->addColumn ( 'action', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'action' ),
                'type' => 'action',
                'width' => '200px',
                'getter' => 'getId',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Approve' ),
                                'url' => array (
                                        'base' => '*/*/approve/' 
                                ),
                                'field' => 'id',
                                'title' => Mage::helper ( 'marketplace' )->__ ( 'Approve' ) 
                        ),
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Disapprove' ),
                                'url' => array (
                                        'base' => "*/*/disapprove" 
                                ),
                                'field' => 'id' 
                        ),
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Delete' ),
                                'url' => array (
                                        'base' => "*/*/delete" 
                                ),
                                'field' => 'id',
                                'confirm' => Mage::helper ( 'marketplace' )->__ ( 'Are you sure?' ) 
                        ) 
                ),
                'sortable' => false,
                'is_system' => true 
        ) );
        /**
         * Payout Request
         */
        $Payout = array ('header' => Mage::helper ( 'marketplace' )->__ ( 'Payout Request' ),
                'width' => '80',
                'type' => 'action',
                'getter' => 'getId',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'Apptha_Marketplace_Block_Adminhtml_Renderersource_Payout',
                'index' => 'entity_id',
                'is_system' => true);
        $this->addColumn ( 'Payout Request',$Payout);
        /**
         * set commission
         */
        $this->addColumn ( 'set_commission', array (
                'header' => Mage::helper ( 'marketplace' )->__ ( 'Set Commission' ),
                'width' => '80',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'marketplace' )->__ ( 'Commission' ),
                                'url' => array (
                                        'base' => '*/*/setcommission/' 
                                ),
                                'field' => 'id',
                                'title' => Mage::helper ( 'marketplace' )->__ ( 'Commission' ) 
                        ) 
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true 
        ) );
       
        /**
         * Edit Action
         */
        $order = array ('header' => Mage::helper ( 'marketplace' )->__ ( 'Order' ),'width' => '80','type' => 'action','getter' => 'getId',
                'actions' => array (array ('caption' => Mage::helper ( 'marketplace' )->__ ( 'Order' ),'url' => array ('base' => 'marketplaceadmin/adminhtml_order/index/'),
                'field' => 'id')),'filter' => false,'sortable' => false,'index' => 'stores','is_system' => true);
        $this->addColumn ( 'order',$order);
        /**
         * Add custom column contact
         */
        $this->addCustomColumn ( 'Contact' );
        /**
         * Add custom column customer
         */
        $this->addCustomColumn ( 'customer' );
        return parent::_prepareColumns ();
    }
    
    /**
     * Function for Mass edit action(approve,disapprove or delete)
     *
     * Will change the status of the seller
     * return void
     */
    protected function _prepareMassaction() {
        /**
         * set Entity Id
         */
        $this->setMassactionIdField ( 'entity_id' );
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
    
    /**
     * Function for link url
     *
     * Return the seller profile edit url
     * return string
     */
    public function getRowUrl($row) {
        return $this->getUrl ( 'adminhtml/customer/edit/', array (
                'id' => $row->getId () 
        ) );
    }


    public function getXL() {
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();
      
        $data = array();
        $xl_data = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
               $data[] = ''.$column->getExportHeader().'';
            }
        }
        $xl_data[] = $data;
      
        foreach ($this->getCollection() as $item) {
        $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = $column->getRowFieldExport($item);
                }
            }
        $xl_data[] = $data;
        }
      
        if ($this->getCountTotals()) {
        $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = $column->getRowFieldExport($this->getTotals());
                }
            }
        }

        return $data;
    }

    public function _sellerEmailFilterCallBack($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $customer = Mage::getResourceModel('customer/customer_collection');
                    $customer->addFieldToFilter('email',array('like' => '%'.$value.'%')); 
        
        $sellerId = array();
        if($customer->getSize()):
            $counter = 0;
            foreach($customer as $custom):
                $sellerId[$counter] = $custom->getId();
                $counter = $counter + 1;
            endforeach;
        endif;
        
        $this->getCollection()
            ->addFieldToFilter ('entity_id', array('in' => $sellerId));
        
        return $this;
    }  

}

