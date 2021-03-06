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
 * Manage order information
 * Manage order information with seller details and also with pagination
 */
class Apptha_Marketplace_Block_Order_Manage extends Mage_Core_Block_Template {

    public $_key = "";

    public function _construct()
    {
        parent::_construct(); // TODO: Change the autogenerated stub
        $this->_key = Mage::getSingleton('customer/session')->getCustomer()->getId()."-dashboard";
    }

    /**
     * Collection for manage orders
     *
     * @return \Apptha_Marketplace_Block_Order_Manage
     */
    protected function _prepareLayout() {
        parent::_prepareLayout ();
        /** 
         * Get Seller Orders
         */
        $currentStatuses = array("pending","approved","shipped", "canceled" , "completed");
        $isPagination = $this->isPagination($_REQUEST);
        $status = "pending";
        if($this->getRequest()->getParam('status')!= null AND $this->getRequest()->getParam('status')!= ""){
                   $status =  $this->getRequest()->getParam('status') ;
         }

        $data = $this->getSessionParams();
        if($_REQUEST['submit'] != true && !empty($data) && $isPagination){
            //$manageCollection = $data['collection'];
            $manageCollection = $this->getOrderItemsStatus($status);
        }
        else{
            $manageCollection = $this->getOrderItemsStatus($status);
            // count store in session here instead from phtml file again & again
            $statusCount = array();
            $statusCount[$status] = $manageCollection->getSize();
            foreach($currentStatuses as $currentStatus){
                if($currentStatus==$status) continue;
               $collectionForCount = $this->getOrderItemsStatus($currentStatus);
                $statusCount[$currentStatus] =  $collectionForCount->getSize();
            }
            $data['statuses_count'] = $statusCount;
            $data['params'] = $_REQUEST;
            //$data['collection'] = $manageCollection; //because in the collection are xml tags which cannot be serialized
            $this->setSessionParams($data);
        }
        $this->setCollection($manageCollection);
        /** 
         * Get Layout
         */
        $pager = $this->getLayout ()->createBlock ( 'page/html_pager', 'my.pager' )->setCollection($manageCollection);
        $pager->setAvailableLimit ( array (
                10 => 10,
                20 => 20,
                50 => 50 
        ) );
        /**
         * Set pager for manage order page
         */
        $this->setChild ( 'pager', $pager );
        return $this;
    }

    /**
     * This function will verify that request is for pagination so load
     * @param $request
     * @return bool
     */
    public function isPagination($request){
        return (!empty($request['p'])) ? true : false ;
    }
    
    /**
     * Function to get pagination
     *
     * Return pagination for collection
     *
     * @return array
     */
    public function getPagerHtml() {
        /** 
         * Get Child Html
         */
        return $this->getChildHtml ( 'pager' );
    }

    public function getPendingOrders($status) {       
        /**
         *  Convert local date to magento db date.
         */
        $orders = $this->getOrderItemsStatus($status);
        
        return $orders;        
    }

    
    /**
     * Function to get seller order details
     *
     * Return seller orders information
     *
     * @return array
     */
    public function getsellerOrders() {       
        /**
         *  Convert local date to magento db date.
         */
        $orders = Mage::getModel('marketplace/commission')->getCollection();
        $orders->addFieldToSelect('*');
        $orders->addFieldToFilter('seller_id', Mage::getSingleton('customer/session')->getCustomer()->getId());
        $orders ->getSelect()
        ->columns('SUM(seller_amount) as seller_amount')
        ->group('order_id');   
        /**
         * Set order for manage order
         */
        $orders->setOrder('order_id', 'desc');
        /**
         * Return orders
         */
        return $orders;        
    }

    public function getOtherOrders($status) {       
        /**
         *  Convert local date to magento db date.
         */
        
        $orders = Mage::getModel('marketplace/commission')->getCollection();
        $orders->addFieldToSelect('*');
        $orders->addFieldToFilter('seller_id', Mage::getSingleton('customer/session')->getCustomer()->getId());
        $orders ->getSelect()
        ->columns('SUM(seller_amount) as seller_amount')
        ->group('order_id');   
        /**
         * Set order for manage order
         */
        $orders->setOrder('order_id', 'desc');
        /**
         * Return orders
         */
        $condition = new Zend_Db_Expr("sales.entity_id = main_table.order_id AND sales.status = '$status'");
               $orders->getSelect()->join(array('sales' => $orders->getTable('sales/order')),
               $condition,
               array('status' => 'sales.status'));
        return $orders;        
    }


    public function getOrderStatus($status){
        $getOrders = $this->getsellerOrders();
         $i = 0;
         foreach ($getOrders as $order):
            $getOrderId = $order->getOrderId();
            $getSellerId = $order->getSellerId();
            $confirm = $this->getProductConfirm($getOrderId,$getSellerId);
            if($confirm):
            $statuses = $this->getProductStatus($getOrderId,$getSellerId);
            $status = ucwords($status);
            if($statuses == "Pending"):
             $confirm = $this->getProductSellerConfirm($getOrderId,$getSellerId); 
             if(!$confirm):
                $statuses = "Partialapproved";
                $confirm = $this->getProductSellerFullConfirm($getOrderId,$getSellerId);
                $confirm_2 = $this->getProductSellerRejected($getOrderId,$getSellerId);
                if(!$confirm):
                    $statuses = "Approved";
                endif;
                if(!$confirm_2):
                    $statuses = "Rejected";
                endif;
             elseif($confirm):
                $statuses = "Pending";
             endif;

            endif;
            if($status == $statuses):
                $ordering[$i] = array($order->getOrderId());
                $i = $i + 1;
            elseif($status == "All"):
                $ordering[$i] = array($order->getOrderId());
                $i = $i + 1;
            endif;
            
            endif;
        
        endforeach;
        
        $orders = Mage::getModel('marketplace/commission')->getCollection();
        $orders->addFieldToSelect('*');
        $orders->addFieldToFilter('order_id', array('in' => array($ordering)));
        $orders->addFieldToFilter('seller_id', $getSellerId);
        $orders ->getSelect()
        ->columns('SUM(seller_amount) as seller_amount')
        ->group('order_id');   
        /**
         * Set order for manage order
         */
        $orders->setOrder('order_id', 'desc');
        /**
         * Return orders
         */
        return $orders;   

    }


     public function getProductConfirm($getOrderId,$getSellerId){
        $counter = 0;
        $i= 0;
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        //$orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $notConfirmedProducts = Mage::helper('marketplace/vieworder')->notConfirmedOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        foreach($orderDetails->getAllVisibleItems() as $item){
            if($item->getStatus() == "Canceled"){
                $itemid[$i]=$item->getProductId();
                $i = $i +1;
            }
        }
        foreach($notConfirmedProducts as $not){
            if(!in_array($not,$itemid)){
                $counter = $counter + 1;
            }

        }
        if($counter == 0){
                return true;
               }
        else{
            return false;
        }
    

     }

     public function getProductSellerConfirm($getOrderId,$getSellerId){
        $counter = 0;
        $i= 0;
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        //$orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $notConfirmedProducts = Mage::helper('marketplace/vieworder')->notConfirmedSellerOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        foreach($orderDetails->getAllVisibleItems() as $item){
            if($item->getStatus() == "Canceled"){
                $itemid[$i]=$item->getProductId();
                $i = $i +1;
            }
        }
        foreach($notConfirmedProducts as $not){
            if(!in_array($not,$itemid)){
                $counter = $counter + 1;
            }

        }
        if($counter == 0){
                return true;
               }
        else{
            return false;
        }
    

     }

     public function getProductSellerFullConfirm($getOrderId,$getSellerId){
        $counter = 0;
        $i= 0;
        $counters = 0;
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        //$orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $notConfirmedProducts = Mage::helper('marketplace/vieworder')->confirmedSellerOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedSellerOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        foreach($orderDetails->getAllVisibleItems() as $item){
            if($item->getStatus() == "Canceled"){
                $itemid[$i]=$item->getProductId();
                $i = $i +1;
            }
        }
        foreach($rejectedProducts as $rejected){
            if(!in_array($rejected,$itemid)){
                $counter = $counter + 1;
            }
        }
        if($counter ==0){
        foreach($notConfirmedProducts as $not){
            if(!in_array($not,$itemid)){
                $counters = $counters + 1;
            }

        }
        if($counters == 0){
                return true;
               }
        else{
            return false;
        }
       }
        else{
            return true;
        }
    

     }

    

     public function getProductSellerRejected($getOrderId,$getSellerId){
        $counter = 0;
        $i= 0;
        $counters = 0;
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        //$orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $confirmedProducts = Mage::helper('marketplace/vieworder')->confirmedSellerOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $notConfirmedProducts = Mage::helper('marketplace/vieworder')->rejectedSellerOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        foreach($orderDetails->getAllVisibleItems() as $item){
            if($item->getStatus() == "Canceled"){
                $itemid[$i]=$item->getProductId();
                $i = $i +1;
            }
        }
        foreach($confirmedProducts as $confirmed){
            if(!in_array($confirmed,$itemid)){
                $counter = $counter + 1;
            }
        }
        if($counter == 0){
        foreach($notConfirmedProducts as $not){
            if(!in_array($not,$itemid)){
                $counters = $counters + 1;
            }

        }

        
        if($counters == 0){
                return true;
               }
        else{
            return false;
        }
    }
    else {
        return true;
    }
    

     }

     public function getProductStatus($getOrderId,$getSellerId){
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        $getProductDetails = $this->getProductDetails($getOrderId,$getSellerId);
        $orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $cancelOrderItemProductIds = Mage::helper('marketplace/vieworder')->cancelOrderItemProductIds(Mage::getSingleton('customer/session')->getId(),$getOrderId);
        $orderAndCancelDiff = array_diff($orderPrdouctIds,$cancelOrderItemProductIds);
        $orderAndCancelDiffCount = count($orderAndCancelDiff);
        $checkOrderStatusArr = array();
          foreach($orderDetails->getAllItems() as $item){
                 $itemProductId = $item->getProductId();
                 $orderItem = $item;
                if(in_array($itemProductId,$orderPrdouctIds) && !in_array($itemProductId,$cancelOrderItemProductIds)){
                  $checkOrderStatusArr[] = Mage::helper('marketplace/vieworder')->checkOrderStatusForSeller($orderItem);
                }
            }
        if($orderAndCancelDiffCount > 0){
            return Mage::helper('marketplace/vieworder')->getOrderStatusForSeller($orderDetails,$checkOrderStatusArr);
        }else{
           return "Canceled";
        }
    
     }

    /**
     * Get seller products by order id
     *
     * @param number $getOrderId
     * @param number $getSellerId
     */
    public function getProductDetails($getOrderId,$getSellerId){
        /**
         * Getting seller product ids from order
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');
        $products->addFieldToFilter('order_id',$getOrderId);
        $products->addFieldToFilter('seller_id',$getSellerId);
        $productIds = array_unique($products->getColumnValues('product_id'));
    
        /**
         * Getting seller order product names
         */
        $productsCollection = Mage::getModel('catalog/product')
        ->getCollection()
        ->addAttributeToSelect(array('name'))
        ->addAttributeToFilter('entity_id', array('in' => $productIds));
        $productNames = array_unique($productsCollection->getColumnValues('name'));
        /**
         * Return seller product names in particualr order
         */
        return $productNameString = implode(',',$productNames);
    }

    public function getOrderItems(){

        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');
        $products->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());

        $condition = new Zend_Db_Expr("main_table.order_id = e.entity_id");
        $products->getSelect()->join(array('e' => $products->getTable('sales/order')),
        $condition,
        array('order_new_status' => 'e.status'));
        // die();
        return $products;
    }
    // Hassan: Move this function to helper in final PR
    public function getDateRange( $range = "" ){
        $dateArray = array();
        switch ($range) {
            case "today":
                // today interval
                $start_day = strtotime("-1 today midnight");
                $dateArray['from'] = date("Y-m-d", $start_day);
                $dateArray['to'] = date("Y-m-d", $start_day);
                break;
            case "yesterday":
                // yesterday interval
                $start_day = strtotime("-1 yesterday midnight");
                $dateArray['from'] = date("Y-m-d", $start_day);
                $dateArray['to'] = date("Y-m-d", $start_day);
                break;
            case "lastweek":
                // last week interval
                $to = date('d-m-Y');
                $to_day = date('l', strtotime($t));
                // if today is monday, take last monday
                if ($to_day == 'Monday') {
                    $start_day = strtotime("-1 monday midnight");
                    $end_day = strtotime("yesterday");
                } else {
                    $start_day = strtotime("-2 monday midnight");
                    $end_day = strtotime("-1 sunday midnight");
                }
                $from = date("Y-m-d", $start_day);
                $to = date("Y-m-d", $end_day);
                $to = date('Y-m-d', strtotime($to . ' + 1 day'));
                $dateArray['from'] = $from;
                $dateArray['to'] = date("Y-m-d", $end_day);
                break;
            case "lastmonth":
                // last month interval
                $from = date('Y-m-01', strtotime('last month'));
                $to = date('Y-m-t', strtotime('last month'));
                $to = date('Y-m-d', strtotime($to . ' + 1 day'));
                $dateArray['from'] = $from;
                $dateArray['to'] = date('Y-m-t', strtotime('last month'));
                break;
            case "currentmonth":
                $from = date('Y-m-01');
                $start_day = strtotime("-1 today midnight");
                $dateArray['from'] = $from;
                $dateArray['to'] = date("Y-m-d", $start_day);
                break;
            case "currentyear":
                $from = date('Y-01-01');
                $start_day = strtotime("-1 today midnight");
                $dateArray['from'] = $from;
                $dateArray['to'] = date("Y-m-d", $start_day);
                break;
            case "lastyear":
                $from = date("Y-01-01", strtotime("-1 year"));// get start date from here
                $start_day = strtotime("-1 today midnight");
                $dateArray['from'] = $from;
                $dateArray['to'] = date("Y-12-31", strtotime("-1 year"));
                break;
            case "custom":
                // last custom interval
                $from = date('Y-m-d', strtotime($data['date_from']));
                $to = date('Y-m-d', strtotime($data['date_to'] . ' + 1 day'));
                $dateArray['from'] = $from;
                $dateArray['to'] = date('Y-m-d', strtotime($data['date_to']));
                break;
            default:
                $dateArray['from'] = '';
                $dateArray['to'] = '';
        }
        return $dateArray;
    }

    public function getOrderItemsStatus($status){
        //Get Range for filter Data
            $sku_no     = trim(Mage::app()->getRequest()->getPost('sku_no'));
            $order_no   = trim(Mage::app()->getRequest()->getPost('order_no'));
            $productIdFromSku = 0;
            $orderNo = 0;
            $rangeArray = array();
            if( !empty(Mage::app()->getRequest()->getPost('filter')) ){
                $range = Mage::app()->getRequest()->getPost('filter');
                $rangeArray =  $this->getDateRange($range) ;
            }
            $collection = Mage::getModel('marketplace/commission')->getCollection();
            $collection->addFieldToSelect('increment_id');
            $collection->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());

            if(!empty($order_no)){
                $orderNo = $order_no;
                $collection->addFieldToFilter('increment_id',$orderNo);
            }
            if(!empty($sku_no)){
                $productIdFromSku = Mage::getModel("catalog/product")->getIdBySku($sku_no);
                $collection->addFieldToFilter('product_id', array('in' => $productIdFromSku));
            }

            if(!empty(Mage::app()->getRequest()->getPost('filter'))){
                $fromDate = date('Y-m-d'.' 00:00:00', strtotime($rangeArray['from']));
                $toDate = date('Y-m-d'. ' 23:59:59', strtotime($rangeArray['to']));
                $collection->addFieldToFilter('created_at', array('from' => $fromDate, 'to' => $toDate));
            }
            $sellerIncrementIds = $collection->getColumnValues('increment_id');
            $sellerIncrementIds = array_unique($sellerIncrementIds);

        if($status=='pending'){// New tab
            $collections = Mage::getModel('sales/order')->getCollection()->addFieldToSelect('increment_id')
                                ->addAttributeToFilter('increment_id', array('in' => $sellerIncrementIds))
                                ->addAttributeToFilter('state', array('nin' => array('canceled', 'holded')))
                                ;
            $sellerFinalIncrementIds = $collections->getColumnValues('increment_id');
            $collectionsMarketPlace = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id', array('in' => $sellerFinalIncrementIds))
                ->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());
            if($productIdFromSku>0) {
                $collectionsMarketPlace->addFieldToFilter('product_id', array('in' => $productIdFromSku));
            }
            $collectionsMarketPlace->addFieldToFilter('is_seller_confirmation', array('eq' => 'No'))
                ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'Yes'))
                ->addFieldToFilter('item_order_status', array('neq' => 'canceled'));
            // change filter to date wise and as per status tab
            if(!empty(Mage::app()->getRequest()->getPost('sort'))){
                if(Mage::app()->getRequest()->getPost('sort') == 'asc'){
                    $collectionsMarketPlace->getSelect()->order('is_buyer_confirmation_date ASC');
                }
                else{
                    $collectionsMarketPlace->getSelect()->order('is_buyer_confirmation_date DESC');
                }
            } else {
                //by default sort order @RT
                $collectionsMarketPlace->getSelect()->order('is_buyer_confirmation_date DESC');
            }

        }else if($status=='approved'){

            $collections = Mage::getModel('sales/order')->getCollection()->addFieldToSelect('increment_id')
                                    ->addAttributeToFilter('increment_id', array('in' => $sellerIncrementIds))
                                    ->addAttributeToFilter('state', array('nin' => array('canceled', 'holded')))
                ;
            $sellerFinalIncrementIds = $collections->getColumnValues('increment_id');
            $collectionsMarketPlace = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id', array('in' => $sellerFinalIncrementIds));
            if($productIdFromSku>0) {
                $collectionsMarketPlace->addFieldToFilter('product_id', array('in' => $productIdFromSku));
            }
            $collectionsMarketPlace->addFieldToFilter('item_order_status', array('eq' => 'ready'))
                ->addFieldToFilter('is_seller_confirmation', array('eq' => 'Yes'))
                ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'Yes'))
                ->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());

            // change filter to date wise and as per status tab
            if(!empty(Mage::app()->getRequest()->getPost('sort'))){
                if(Mage::app()->getRequest()->getPost('sort') == 'asc'){
                    $collectionsMarketPlace->getSelect()->order('is_seller_confirmation_date ASC');
                }
                else{
                    $collectionsMarketPlace->getSelect()->order('is_seller_confirmation_date DESC');
                }
            } else {
                //by default sort order @RT
                $collectionsMarketPlace->getSelect()->order('is_seller_confirmation_date DESC');
            }

        }else if($status=='shipped'){
            $shippedCollections = Mage::getModel('sales/order')->getCollection()->addFieldToSelect('increment_id')
                ->addAttributeToFilter('status', array(
                        array('eq' => 'processing'),
                        array('eq' => 'shipped_from_elabelz'),
                        array('eq' => 'successful_delivery')
                    )
                )
                ->addAttributeToFilter('increment_id', array('in' => $sellerIncrementIds));

            $sellerFinalIncrementIds = $shippedCollections->getColumnValues('increment_id');


            $collectionsMarketPlace = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id', array('in' => $sellerFinalIncrementIds));
            if($productIdFromSku>0) {
                $collectionsMarketPlace->addFieldToFilter('product_id', array('in' => $productIdFromSku));
            }
            $collectionsMarketPlace->addFieldToFilter('item_order_status', array(
                array('eq' => 'ready'),
                array('eq' => 'processing'),
                array('eq' => 'shipped_from_elabelz'),
                array('eq' => 'successful_delivery')
            ))
                ->addFieldToFilter('is_seller_confirmation', array('eq' => 'Yes'))
                ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'Yes'))
                ->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());

            // change filter to date wise and as per status tab
            if(!empty(Mage::app()->getRequest()->getPost('sort'))){
                if(Mage::app()->getRequest()->getPost('sort') == 'asc'){
                    $collectionsMarketPlace->getSelect()->order('shipped_from_elabelz_date ASC');
                }
                else{
                    $collectionsMarketPlace->getSelect()->order('shipped_from_elabelz_date DESC');
                }
            } else {
                //by default sort order @RT
                $collectionsMarketPlace->getSelect()->order('shipped_from_elabelz_date DESC');
            }

        }else if($status=='canceled'){
            $collections = Mage::getModel('sales/order')->getCollection()->addFieldToSelect('increment_id')
                ->addAttributeToFilter('increment_id', array('in' => $sellerIncrementIds))
                ->addAttributeToFilter('state', ['nin' => ['holded']]);
            $sellerFinalIncrementIds = $collections->getColumnValues('increment_id');
            $collectionsMarketPlace = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id', array('in' => $sellerFinalIncrementIds))
                ->addFieldToFilter(array(
                    'item_order_status',
                    'is_seller_confirmation',
                    'is_buyer_confirmation'
                ),
                    array(
                        array(
                            array('eq'=>'canceled'),
                            array('eq'=>'canceled_automatic'),
                            array('eq'=>'refunded'),
                            array('eq'=>'failed_delivery'),
                            array('eq'=>'sale_returned'),
                            array('eq'=>'rejected_customer'),
                            array('eq'=>'rejected_seller')
                        ),
                        array('eq'=>'Rejected'),
                        array('eq'=>'Rejected')
                    )
                )
                ->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());

            // change filter to date wise and as per status tab
            if(!empty(Mage::app()->getRequest()->getPost('sort'))){
                if(Mage::app()->getRequest()->getPost('sort') == 'asc'){
                    $collectionsMarketPlace->getSelect()->order('is_seller_confirmation_date ASC');
                }
                else{
                    $collectionsMarketPlace->getSelect()->order('is_seller_confirmation_date DESC');
                }
            } else {
                //by default sort order @RT
                $collectionsMarketPlace->getSelect()->order('is_seller_confirmation_date DESC');
            }

        }else if($status=='completed'){

            $collectionsMarketPlace = Mage::getModel('sales/order')->getCollection()->addFieldToSelect('increment_id')
                ->addAttributeToFilter('status', array(array('eq' => 'complete')))
                ->addAttributeToFilter('increment_id', array('in' => $sellerIncrementIds));

            $sellerFinalIncrementIds = $collectionsMarketPlace->getColumnValues('increment_id');


            $collectionsMarketPlace = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id', array('in' => $sellerFinalIncrementIds))
                ->addFieldToFilter(array('item_order_status', 'is_seller_confirmation', 'is_buyer_confirmation'),
                    array(
                        array(array('eq'=>'ready'),array('eq'=>'complete')),
                        array('eq'=>'Yes'),
                        array('eq'=>'Yes')
                    )
                )
                ->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId());

            // change filter to date wise and as per status tab
            if(!empty(Mage::app()->getRequest()->getPost('sort'))){
                if(Mage::app()->getRequest()->getPost('sort') == 'asc'){
                    $collectionsMarketPlace->getSelect()->order('successful_non_refundable_date ASC');
                }
                else{
                    $collectionsMarketPlace->getSelect()->order('successful_non_refundable_date DESC');
                }
            } else {
                //by default sort order @RT
                $collectionsMarketPlace->getSelect()->order('successful_non_refundable_date DESC');
            }

        }

        // This is for final refinement
        if( !empty(Mage::app()->getRequest()->getPost('filter')) ){
            $fromDate = date('Y-m-d'.' 00:00:00', strtotime($rangeArray['from']));
            $toDate = date('Y-m-d'. ' 23:59:59', strtotime($rangeArray['to']));
            $collectionsMarketPlace->addFieldToFilter('created_at', array('from' => $fromDate, 'to' => $toDate));
        }

        if(!empty($orderNo)){
            $collectionsMarketPlace->addFieldToFilter('increment_id', $orderNo);
        }

        if($productIdFromSku>0){
            $collectionsMarketPlace->addFieldToFilter('product_id', $productIdFromSku);
        }

        $collectionsMarketPlace->getSelect();

        return $collectionsMarketPlace;
    }

    /**
     * @param $data
     */
    protected function setSessionParams( $data)
    {
        Mage::getSingleton('core/session')->setData($this->_key, serialize($data));
    }

    /**
     * @return mixed
     */
    public function getSessionParams()
    {
        return unserialize(Mage::getSingleton('core/session')->getData($this->_key));
    }
}