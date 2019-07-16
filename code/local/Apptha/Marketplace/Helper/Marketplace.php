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
 * @version     1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
/**
 * Function written in this file are globally accessed
 */
class Apptha_Marketplace_Helper_Marketplace extends Mage_Core_Helper_Abstract {
    
    /**
     * Function to get the seller rewrite url
     *
     * Passed the seller id to rewrite the particular seller url
     *
     * @param int $sellerId
     *            This function will return the rewrited url for a particular seller
     * @return string
     */
    
   
    
    public function getSellerRewriteUrl($sellerId) {
        /** getting seller id */
        $targetPath = 'marketplace/seller/displayseller/id/' . $sellerId;
        $mainUrlRewrite = Mage::getModel ( 'core/url_rewrite' )->load ( $targetPath, 'target_path' );
        $getRequestPath = $mainUrlRewrite->getRequestPath ();
        return Mage::getUrl ( $getRequestPath );
    }
    
    /**
     * Function to load particular seller information
     *
     * In this function seller id is passed to get particular seller data
     *
     * @param int $_id
     *            This function will return the particular seller information as array
     * @return array
     */
    public function getSellerCollection($_id) {
        /** load collection based on seller id */
        return Mage::getModel ( 'marketplace/sellerprofile' )->load ( $_id, 'seller_id' );
    }
    
    /**
     * Function to load particular category information
     *
     * Passed Category Id to get the category information
     *
     * @param int $catId
     *            This function will return the Category information as array
     * @return array
     */
    public function getCategoryData($catId) {
        /** load category based on category id */
        return Mage::getModel ( 'catalog/category' )->load ( $catId );
    }
    
    /**
     * Function to delete product
     *
     * Product entity id are passed to delete the product
     *
     * @param int $entityIds
     *            This function will return true or false
     * @return bool
     */
    public function deleteProduct($entityIds) {
        $productSellerId = Mage::getModel( 'catalog/product' )->load($entityIds)->getSellerId();
        if ($productSellerId == Mage::getSingleton ( 'customer/session' )->getCustomerId ()) {
            Mage::helper ( 'marketplace/general' )->changeAssignProductId( $entityIds );
            Mage::getModel ( 'catalog/product' )->setId ($entityIds)->delete();
        }
        return true;
    }
        /**
     * Function to Set out of stock product
     *
     * Product entity id are passed to outOfStock the product
     *
     * @param int $entityIds
     *            This function will return true or false
     * @return bool
     *author: Azhar Farooq <az.fq.jh@gmail.com>
     */
    public function outOfStock($entityIds){
        $productSellerId = Mage::getModel ( 'catalog/product' )->load ( $entityIds )->getSellerId ();
        if ($productSellerId == Mage::getSingleton ( 'customer/session' )->getCustomerId ()) {
        $_Product = Mage::getModel ( 'catalog/product' )->load($entityIds);
        $stockData = array ();
        $_Product->setStatus('2');  
        $_Product->setSeller_product_status('1027'); // sold out status      
        $stockData ['qty'] = 0;
        $stockData ['is_in_stock'] = 0;
        $_Product->setStockData ( $stockData );
        //Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
        //$storeId = Mage::app ()->getStore ()->getStoreId ();        
        $_Product->save ();        
         return true;
        }
       
    }
            /**
     * Function to Set paused status product
     *
     * Product entity id are passed to outOfStock the product
     *
     * @param int $entityIds
     *            This function will return true or false
     * @return bool
     *author: Azhar Farooq <az.fq.jh@gmail.com>
     */
    public function pausedStock($entityIds){
        $productSellerId = Mage::getModel ( 'catalog/product' )->load ( $entityIds )->getSellerId ();
        if ($productSellerId == Mage::getSingleton ( 'customer/session' )->getCustomerId ()) {
        $_Product = Mage::getModel ( 'catalog/product' )->load($entityIds);
        $stockData = array ();
        $_Product->setStatus('2');  
        $_Product->setSeller_product_status('1026'); // Paused status      
        $_Product->setStockData ( $stockData );
        $_Product->save ();        
         return true;
        }
       
    }
    /**
     * Function to get product collection
     *
     * Product id is passed to get the particular product information
     *
     * @param int $getProductId
     *            This function will display the particular product information as array
     * @return array
     */
    public function getProductInfo($getProductId) {
        return Mage::getModel ( 'catalog/product' )->load ( $getProductId );
    }
    
    /**
     * Function to load email template
     *
     * Passed the template id to load the email template
     *
     * @param int $templateId
     *            This function will return the email template
     * @return string
     */
    public function loadEmailTemplate($templateId) {
        return Mage::getModel ( 'core/email_template' )->load ( $templateId );
    }
    
    /**
     * Function to load customer data
     *
     * Passed the selle id to load a particular seller details
     *
     * @param int $sellerId
     *            This function will return the seller details as array
     * @return array
     */
    public function loadCustomerData($sellerId) {
        /** To load customer based on seller id */
        return Mage::getModel ( 'customer/customer' )->load ( $sellerId );
    }
    
    /**
     * Function to storing downloadable product link data
     *
     * Downloadable file data are passed as array
     *
     * @param array $linkModel
     *            This function will return true or false
     * @return bool
     */
    public function saveDownLoadLink($linkModel) {
        /** to save download link */
        $linkModel->save ();
        return true;
    }
    
    /**
     * Function to set product instock
     *
     * Passed the Product is instock or not value
     *
     * @param int $isInStock
     *            This function will return 0 or 1
     * @return bool
     */
    public function productInStock($isInStock) {
        if (isset ( $isInStock )) {
            return $stock_data ['is_in_stock'] = $isInStock;
        } else {
            return $stock_data ['is_in_stock'] = 1;
        }
    }
    
    /**
     * Function to get vacation mode savae url
     *
     * This Function will return the redirect url of vacation mode save action
     *
     * @return string
     */
    public function getVacationModeSaveUrl() {
        /** To check vacation mode */
        return Mage::getUrl ( 'marketplace/general/vacationmodesave' );
    }
        
    /**
     * Function to delete deal price and date for products
     *
     * Passed the entity id in url to get the product details
     *
     * @param int $entityIds
     *            This Function will delete deal details
     * @return bool
     */
    public function deleteDeal($entityIds) {
        Mage::getModel ( 'catalog/product' )->load ( $entityIds )->setSpecialFromDate ( '' )->setSpecialToDate ( '' )->setSpecialPrice ( '' )->save ();
        return true;
    }
    
    /**
     * Retrieve attribute id for seller shipping
     *
     * This function will return the seller shipping id
     *
     * @return int
     */
    public function getSellerShipping() {
        return Mage::getResourceModel ( 'eav/entity_attribute' )->getIdByCode ( 'catalog_product', 'seller_shipping_option' );
    }
    /**
     * Load particular product info
     *
     * @param Mage_Catalog_Model_Product $product            
     */
    protected function _loadProduct(Mage_Catalog_Model_Product $product) {
        $product->load ( $product->getId () );
    }
    /**
     * Get the New and Sale Label for a particular product
     *
     * @param Mage_Catalog_Model_Product $product            
     * @return string
     */
    public function getLabel(Mage_Catalog_Model_Product $product) {
        $html = '';
        $this->_loadProduct ( $product );
        if ($this->_isNew ( $product )) {
            $html .= '<div class="new-label new-right' . '">New</div>';
        }
        if ($this->_isOnSale ( $product )) {
            $html .= '<div class="sale-label sale-left">Sale</div>';
        }
        return $html;
    }
    /**
     * Checking the from and to date for new and sale product
     *
     * @param unknown $from            
     * @param unknown $to            
     * @return boolean
     */
    protected function _checkDate($from, $to) {
        $return = true;
        $date = date( 'Y-m-d');
        $today = strtotime ($date);
        if ($from && $today < $from) {
            $return = false;
        }
        if ($to && $today > $to) {
            $return = false;
        }
        if (! $to && ! $from) {
            $return = false;
        }
        return $return;
    }
    /**
     * Check whether a product is set as new
     *
     * @param unknown $product            
     */
    protected function _isNew($product) {
        $from = strtotime ( $product->getData ( 'news_from_date' ) );
        $to = strtotime ( $product->getData ( 'news_to_date' ) );
        return $this->_checkDate ( $from, $to );
    }
    /**
     * check whether a product is set for sale
     *
     * @param unknown $product            
     */
    protected function _isOnSale($product) {
        $from = strtotime ( $product->getData ( 'special_from_date' ) );
        $to = strtotime ( $product->getData ( 'special_to_date' ) );
        return $this->_checkDate ( $from, $to );
    }
    /**
     * Get category display url
     *
     * Return the category display url
     *
     * @return string
     */
    public function getCategoryDisplayUrl($category) {
        $subCatId = array ();
        $children = Mage::getModel ( 'catalog/category' )->getCategories ( $category );
        foreach ( $children as $_children ) {
            $subCatId [] = $_children->getId ();
        }
        if (count ( $subCatId ) > 0) {
            return Mage::getUrl ( 'marketplace/index/categorydisplay', array (
                    'id' => $category 
            ) );
        } else {
            $catInfo = Mage::getModel ( 'catalog/category' )->load ( $category );
            return Mage::getBaseUrl () . $catInfo->getUrlPath ();
        }
    
    }
    /**
     * Resize category images to display
     *
     * Return image url
     *
     * @return string
     */
    public function getResizedImage($imagePath, $width, $height = null, $quality = 100) {
        
        $return = '';
        $imageUrl = Mage::getBaseDir ( 'media' ) . DS . 'catalog' . DS . "category" . DS . $imagePath;
        
        if (! $imagePath || ! is_file ( $imageUrl )) {
            $return = false;
        } else {
            /**
             * Because clean Image cache function works in this folder only
             */
            $imageResized = Mage::getBaseDir ( 'media' ) . DS . 'catalog' . DS . 'product' . DS . "cache" . DS . "cat_resized" . DS . $width . $imagePath;
            if (! file_exists ( $imageResized ) && file_exists ( $imageUrl ) || file_exists ( $imageUrl ) && filemtime ( $imageUrl ) > filemtime ( $imageResized )) :
                $imageObj = new Varien_Image ( $imageUrl );
                $imageObj->constrainOnly ( true );
                $imageObj->keepAspectRatio ( false );
                $imageObj->keepFrame ( false );
                $imageObj->quality ( $quality );
                $imageObj->resize ( $width, $height );
                $imageObj->save ( $imageResized );
            
   
            endif;
            
            if (file_exists ( $imageResized )) {
                $return = Mage::getBaseUrl ( 'media' ) . "catalog/product/cache/cat_resized/" . $width . $imagePath;
            } else {
                $return = $imagePath;
            }
        }
        return $return;
    
    }
    
    /**
     * Function to get the dashboard url
     *
     * This Function will return the redirect url to dashboard
     *
     * @return string
     */
    public function dashboardUrl() {
        return Mage::getUrl ( 'marketplace/seller/dashboard' );
    }

    public function getTotalAmount($sellerId=0, $status=[], $formatTotal=false)
    {
        //Mage::helper('marketplace/marketplace')->getSellerCollection($_id)
        $sellerId = ($sellerId) ? $sellerId : Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $collection = Mage::getModel('marketplace/payout')->getCollection()
            ->addFieldToFilter('seller_id', $sellerId);
        if(empty($status)) {
            $collection->addFieldToFilter('status', 'Approve');
        } else {
            $statusParams = [];
            foreach($status as $val) {
                $statusParams[]['like'] = $val;
            }
            $collection->addFieldToFilter('status', $statusParams);
        }
        $collection->getSelect()->columns('SUM(request_amount) as request_amount_sum');
        foreach ($collection as $data) {
            //return from here because the result will be summed up already.
            if($formatTotal)
                return Mage::helper ( 'core' )->currency ( $data['request_amount_sum'], true, false );
            else
                return $data['request_amount_sum'];
        }
    }

    public function getSellerTotal($sellerId=0) {
        $return = '';
        $sellerId = ($sellerId) ? $sellerId : Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $_collection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'seller_amount' )
            ->addFieldToFilter ( 'seller_id', $sellerId )
            ->addFieldToFilter ( 'item_order_status',  array('like' => '%complete%') ) /*Edited by Ali? As this value is not updated*/
            ->addFieldToFilter ( 'credited', 1 )
            ->addFieldToFilter ( 'refund_request_customer', 0 )
            ->addFieldToFilter ( 'refund_request_seller', 0 )
            ->addFieldToFilter ( 'cancel_request_customer', 0 );
        $_collection->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' )->group ( 'seller_id' );
        
        foreach ( $_collection as $amount ) {
            $return = $amount->getSellerAmount ();
        }
        return $return;
    }

    public function getSaleTotal_new($sellerId=0) {

        $return = '';
        $sellerId = ($sellerId) ? $sellerId : Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $_collection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'seller_amount','order_id' )
            ->addFieldToFilter ( 'seller_id', $sellerId )
            ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
            ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
            ->addFieldToFilter ( 'status', 1 )
            ->addFieldToFilter ( 'order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) ) /*Edited by Ali? As this value is not updated*/
            ->addFieldToFilter ( 'item_order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) ) 
            ->addFieldToFilter ( 'refund_request_customer', 0 )
            ->addFieldToFilter ( 'refund_request_seller', 0 )
            ->addFieldToFilter ( 'cancel_request_customer', 0 );
        $_collection->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' )->group ( 'seller_id' );

        foreach ( $_collection as $amount ) {
            $return = $amount->getSellerAmount ();
        }
        return $return;
    }

    public function getTotalCommission_new($sellerId=0) {
        $return = '';
        $sellerId = ($sellerId) ? $sellerId : Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $_collection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'commission_fee' )
            ->addFieldToFilter ( 'seller_id', $sellerId )
            ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
            ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
            ->addFieldToFilter ( 'status', 1 )
            ->addFieldToFilter ( 'order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) ) /*Edited by Ali? As this value is not updated*/
            ->addFieldToFilter ( 'item_order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) )
            ->addFieldToFilter ( 'refund_request_customer', 0 )
            ->addFieldToFilter ( 'refund_request_seller', 0 )
            ->addFieldToFilter ( 'cancel_request_customer', 0 );
        $_collection->getSelect ()->columns ( 'SUM(commission_fee) AS commission_fee' )->group ( 'seller_id' );

        foreach ( $_collection as $amount ) {
            $return = $amount->getCommissionFee ();
        }
        return $return;
    }

    public function getTotalRemaining_new($sellerId=0) {
        $commission_fee = $this->getTotalCommission_new($sellerId);
        $sale = $this->getTotalSeller_new($sellerId,1);

        $sellerPayout = Mage::getModel ( 'marketplace/payout' )->getCollection ()
        ->addFieldToFilter ( 'seller_id', $sellerId );
        $lifetimePaid = array();
        foreach ($sellerPayout as $_sellerPayout) {
            if ($_sellerPayout->getStatus() == "Paid" ) {
                $lifetimePaid[] = $_sellerPayout['request_amount'];
            }
        }
        $paid = array_sum($lifetimePaid);
        $remaining = $sale - $paid;
        return $remaining;
    }

    public function getTotalSeller_new($sellerId=0,$credited) {
        $return = '';
        $sellerId = ($sellerId) ? $sellerId : Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $_collection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'seller_amount','order_id' )
            ->addFieldToFilter ( 'seller_id', $sellerId )
            ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
            ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
            ->addFieldToFilter ( 'credited', $credited )
            ->addFieldToFilter ( 'status', 1 )
            ->addFieldToFilter ( 'order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) ) /*Edited by Ali? As this value is not updated*/
            ->addFieldToFilter ( 'item_order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) ) 
            ->addFieldToFilter ( 'refund_request_customer', 0 )
            ->addFieldToFilter ( 'refund_request_seller', 0 )
            ->addFieldToFilter ( 'cancel_request_customer', 0 );
        $_collection->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' )->group ( 'seller_id' );

        foreach ( $_collection as $amount ) {
            $return = $amount->getSellerAmount ();
        }
        return $return;
    }

    public function getPayoutRequest_new($sellerId=0) {

        $sellerPayout = Mage::getModel ( 'marketplace/payout' )->getCollection ()
        ->addFieldToFilter ( 'seller_id', $sellerId );
        $lifetimePaid = array();
        foreach ($sellerPayout as $_sellerPayout) {
            if ($_sellerPayout->getStatus() == "Pending" || $_sellerPayout->getStatus() == "Approve"  ) {
                $lifetimePaid[] = $_sellerPayout['request_amount'];
            }
        }
        $paid = array_sum($lifetimePaid);
        
        return $paid;
    }

    public function getSellerRemainingAmount($sellerId)
    {
        return $this->getSellerTotal($sellerId) - $this->getTotalAmount($sellerId, ["Paid", "Approve", "Pending"]);
    }
    
    /*edit by Azhar*/
    public function getSellerAccountsDetail($seller_id){

        $commessionCollection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->addFieldToFilter ( 'order_status', array ( 'eq' => 'complete' ) )
        ->addFieldToFilter ( 'status', array ('eq' => 1) )// 0 means refunded 1
        ->addFieldToFilter ( 'credited', array ('eq' => 1) )
        ->addFieldToFilter ( 'item_order_status', array ('like' => '%complete%') ) /*edit By Ali. As this value is not updated*/
        ->addFieldToFilter ( 'seller_id', $seller_id );

         $commessionCollection->getSelect ()->columns ( 'SUM(product_amt) AS product_amt,
          SUM(commission_fee) AS commission_fee,
          SUM(seller_amount) AS seller_amount' );

         foreach ( $commessionCollection as $amount ) {
                $return['currency_product_amount'] = Mage::helper ( 'core' )->currency ( $amount->getProductAmt (), true, false );
                $return['currency_commission_fee'] =  Mage::helper ( 'core' )->currency ( $amount->getCommissionFee (), true, false );
                $return['currency_seller_amount'] = Mage::helper ( 'core' )->currency ( $amount->getSellerAmount (), true, false );
                $return['product_amount'] = $amount->getProductAmt ();
                $return['commission_fee'] = $amount->getCommissionFee ();
                $return['seller_amount'] = $amount->getSellerAmount ();
         }
        return $return;
    }

    public function getOrderRefundAmount($seller_id) {
        $commission = Mage::getModel ( 'marketplace/commission' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->addFieldToFilter ( 'order_status', array ( 'eq' => 'complete' ) )
        ->addFieldToFilter ( 'item_order_status', array ( 'eq' => 'refund' ) )
        ->addFieldToFilter ( 'status', array ('eq' => 0) )
        ->addFieldToFilter ( 'credited', array ('eq' => 1) )
        ->addFieldToFilter ( 'seller_id', $seller_id );
        $seller_amount = [];
        foreach($commission as $item) {
            $seller_amount[] = $item->getSellerAmount();
        }
        return  array_sum($seller_amount);
    }

    public function getSellerTotalPayoutRequestAmount($sellerId,$status)
    {
        $sellerId = ($sellerId) ? $sellerId : Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $collection = Mage::getModel('marketplace/payout')->getCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('status', $status)
        ;
        $collection->getSelect()->columns('SUM(request_amount) as request_amount_sum');
        foreach ($collection as $data) {
            return $data['request_amount_sum'];
        }
    }

    public function getPendingOrders($status) {       
        /**
         *  Convert local date to magento db date.
         */
        if($status != "all"):
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
        elseif($status == "all"):
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
        endif;
        return $orders;        
    }
    
    function sendVerify($phNum){
        $nexmoApiKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apikey');
        $nexmoApiSecretKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apisecret');
        $nexmoBrand =  Mage::getStoreConfig('marketplace/nexmo/nexmo_brand');
        
        $url = 'https://api.nexmo.com/verify/json?' . http_build_query([
                'api_key' => $nexmoApiKey,
                'api_secret' => $nexmoApiSecretKey,
                'number' => $phNum,
                'brand' => $nexmoBrand,
                'avoid_voice_call' => 'true'
            ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        error_log($response);
        $data = json_decode($response, true);
        return $data;
    }

    function getRequestId($order_id){
        $orders = Mage::getModel('marketplace/commission')->getCollection();
        $orders->addFieldToSelect('*');
        $orders->addFieldToFilter('increment_id', $order_id);
        return $orders;
    }

    function checkVerify($request_id,$code){
        $nexmoApiKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apikey');
        $nexmoApiSecretKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apisecret');
       
       $url = 'https://api.nexmo.com/verify/check/json?' . http_build_query([
        'api_key' => $nexmoApiKey,
        'api_secret' => $nexmoApiSecretKey,
        'request_id' => $request_id,
        'code' => $code
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        error_log($response);
        $data = json_decode($response, true);
        if($data['status'] == 0 ){
            return true;
        }else{
            return false;
        }

    }

    public function successAfter($orderId){
        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $order = Mage::getModel('sales/order')->load($orderId);
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $getCustomerId = $customer->getId();
        $grandTotal = $order->getGrandTotal();
        $status = $order->getStatus();
        $itemCount = 0;
        $shippingCountryId = '';
        $items = $order->getAllVisibleItems();
        $orderEmailData = array();
        foreach ($items as $item) {
            $getProductId = $item->getProductId();
            $createdAt = $item->getCreatedAt();
            $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
            $products = Mage::helper('marketplace/marketplace')->getProductInfo($getProductId);
            $products_new = Mage::getModel('catalog/product')->load($item->getProductId());
            if($products_new->getTypeId() == "configurable")
                {
                   $options = $item->getProductOptions() ;

                   $sku = $options['simple_sku'] ;
                   $getProductId = Mage::getModel('catalog/product')->getIdBySku($sku);
               }
            else{
                $getProductId = $item->getProductId();
            }


            $order_item_status = Mage::getModel('marketplace/commission')
            ->getCollection()
            ->addFieldToFilter("product_id",$getProductId)
            ->addFieldToFilter("order_id",$order->getId())->getFirstItem();

            $isbuyerconfirmation = $order_item_status->getIsBuyerConfirmation();
            if($isbuyerconfirmation == "Yes"){

               $isbuyerconfirmation = "Accepted"; 
            }

            $sellerId = $products->getSellerId();
            $productType = $products->getTypeID();
            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');
            if ($sellerShippingEnabled == 1 && $productType == 'simple') {
                /**
                 * Get the product national shipping price
                 * and international shipping price
                 * and shipping country
                 */
                $nationalShippingPrice = $products->getNationalShippingPrice();
                $internationalShippingPrice = $products->getInternationalShippingPrice();
                $sellerDefaultCountry = $products->getDefaultCountry();
                $shippingCountryId = $order->getShippingAddress()->getCountry();
            }
            /**
             * Check seller id has been set
             */
            if ($sellerId) {
                $orderPrice = $item->getBasePrice() * $item->getQtyOrdered();
                $productAmt = $item->getBasePrice();
                $productQty = $item->getQtyOrdered();
                $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry,
                    $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);
                /**
                 * Getting seller commission percent
                 */
                $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $shippingPrice - $commissionFee;
                /**
                 * Storing commission information in database table
                 */

                 if ($commissionFee > 0 || $sellerAmount > 0) {
                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($getProductId);
                    $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);

                    if ($parent_product->getSpecialPrice()) {
                        $orderPrice_sp = $parent_product->getSpecialPrice() * $item->getQtyOrdered();
                        $orderPrice_base = $parent_product->getPrice() * $item->getQtyOrdered();

                        $commissionFee = $orderPrice_sp * ($percentperproduct / 100);
                        $sellerAmount = $orderPrice_sp - $commissionFee;
                    } else {
                        $orderPrice_base = $item->getBasePrice() * $item->getQtyOrdered();
                        $commissionFee = $orderPrice_base * ($percentperproduct / 100);
                        $sellerAmount = $shippingPrice - $commissionFee;
                    }
                  }
                  
                  
                   if($isbuyerconfirmation == "Accepted"){
                    
                    
                    $orderEmailData [$itemCount] ['seller_id'] = $sellerId;
                    $orderEmailData [$itemCount] ['product_qty'] = $productQty;
                    $orderEmailData [$itemCount] ['product_id'] = $getProductId;
                    $orderEmailData [$itemCount] ['product_amt'] = $productAmt;
                    $orderEmailData [$itemCount] ['commission_fee'] = $commissionFee;
                    $orderEmailData [$itemCount] ['seller_amount'] = $sellerAmount;
                    $orderEmailData [$itemCount] ['increment_id'] = $order->getIncrementId();
                    $orderEmailData [$itemCount] ['customer_firstname'] = $order->getCustomerFirstname();
                    $orderEmailData [$itemCount] ['customer_email'] = $order->getCustomerEmail();
                    $orderEmailData [$itemCount] ['product_id_simple'] = $getProductId;
                    $orderEmailData [$itemCount] ['is_buyer_confirmation'] = $isbuyerconfirmation;
                    $orderEmailData [$itemCount] ['itemCount'] = $itemCount;
                    $itemCount = $itemCount + 1;
                }


                }
            }

        
        if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
            $this->sendOrderEmail($orderEmailData);                    
        }
    }

        
    public function addOrderItem($child,$qty,$newProduct){
        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $order = Mage::getModel('sales/order')->load($child->getOrderId());
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $getCustomerId = $customer->getId();
        $grandTotal = $order->getGrandTotal();
        $status = $order->getStatus();
        $itemCount = 0;
        $shippingCountryId = '';
        $getProductId = $newProduct->getId();
        $createdAt = $order->getCreatedAt();
        $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
        //$parent_item = Mage::getModel('sales/order_item')->load($child->getParentItemId());
        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                  ->getParentIdsByChild($newProduct->getId());
        $parent_item = Mage::getModel('catalog/product')->load($parentIds[0]);
        $products = Mage::helper('marketplace/marketplace')->getProductInfo($parent_item->getId());
        $products_new = Mage::getModel('catalog/product')->load($newProduct->getId());

        $sellerId = $products->getSellerId();
       
        $productType = $products->getTypeID();

        $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');

        if ($sellerShippingEnabled == 1 && $productType == 'simple') {
                /**
                 * Get the product national shipping price
                 * and international shipping price
                 * and shipping country
                 */
                $nationalShippingPrice = $products->getNationalShippingPrice();
                $internationalShippingPrice = $products->getInternationalShippingPrice();
                $sellerDefaultCountry = $products->getDefaultCountry();
                $shippingCountryId = $order->getShippingAddress()->getCountry();
        }

        if ($sellerId) {

            $orderPrice = $products->getPrice() * $qty;
            
            $productAmt = $products->getPrice();
            
            $productQty = $qty;
            
            $is_buyer_confirmation = 'No';
            $item_order_status = 'pending';
            $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry, $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);
    
            
            /**
            * Getting seller commission percent
            */

            $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
            $percentperproduct = $sellerCollection ['commission'];
            $commissionFee = $orderPrice * ($percentperproduct / 100); 

            // Product price after deducting
            // $productAmt = $products_new->getPrice() - $commissionFee;

            $sellerAmount = $shippingPrice - $commissionFee;

            if($newProduct->getProductType() == 'simple')
            {
                $getProductId = $newProduct->getProductId();
            }

            /**
             * Storing commission information in database table
             */
            if ($commissionFee >= 0 || $sellerAmount >= 0) {

                if ($products->getSpecialPrice()) {
                    $orderPrice_sp = $products->getSpecialPrice() * $qty;
                    $orderPrice_base = $products->getPrice() * $qty;

                    $commissionFee = $orderPrice_sp * ($percentperproduct / 100);
                    $sellerAmount = $orderPrice_sp - $commissionFee;
                } else {
                    $orderPrice_base = $products->getPrice() * $qty;
                    $commissionFee = $orderPrice_base * ($percentperproduct / 100);
                    $sellerAmount = $shippingPrice - $commissionFee;
                }
                $commissionDataArr = array(
                        'seller_id' => $sellerId,
                        'product_id' => $getProductId,
                        'product_qty' => $productQty,
                        'product_amt' => $productAmt,
                        'commission_fee' => $commissionFee,
                        'seller_amount' => $sellerAmount,
                        'order_id' => $order->getId(),
                        'increment_id' => $order->getIncrementId(),
                        'order_total' => $grandTotal,
                        'order_status' => $status,
                        'credited' => $credited,
                        'customer_id' => $getCustomerId,
                        'status' => 1,
                        'created_at' => $createdAt,
                        'payment_method' => $paymentMethodCode,
                        'item_order_status' => $item_order_status,
                        'is_buyer_confirmation' => $is_buyer_confirmation,
                        'sms_verify_code' => $data,
                        'commission_percentage' => $sellerCollection ['commission']
                    );
                $commissionId = $this->storeCommissionConfiguredData($commissionDataArr);
                
        }
    }
    }

        public function storeCommissionData($commissionDataArr) {
        
        $model = Mage::getModel('marketplace/commission');
        $duplicateProduct = $model->getCollection()
            ->addFieldToSelect('order_id')
            ->addFieldToFilter('order_id',$commissionDataArr['order_id'])
            ->addFieldToFilter('product_id',$commissionDataArr['product_id'])
            ->addFieldToFilter('seller_id',$commissionDataArr['seller_id']);
        if($duplicateProduct->getSize()){
            return false;
        }
        else {
            $model->setData($commissionDataArr);
            $model->save();
            
            return $model->getId();
        }
        
    }

    public function storeCommissionConfiguredData($commissionDataArr) {
        
        $model = Mage::getModel('marketplace/commission');
        $model->setData($commissionDataArr);
        $model->save();
            
        return $model->getId();
    }
    
    public function sendOrderEmail($orderEmailData) {

        
        $sellerIds = array();
        $displayProductCommission = Mage::helper('marketplace')->__('Seller Commission Fee');
        $displaySellerAmount = Mage::helper('marketplace')->__('Seller Amount');
        $displayProductImage = Mage::helper('marketplace')->__('Product Image');
        $displayProductName = Mage::helper('marketplace')->__('Product Name');
        $displayProductQty = Mage::helper('marketplace')->__('Product QTY');
        $displayProductAmt = Mage::helper('marketplace')->__('Product Amount');
        $displayProductStatus = Mage::helper('marketplace')->__('Product Status');
        foreach ($orderEmailData as $data) {
            if (!in_array($data ['seller_id'], $sellerIds)) {
                $sellerIds [] = $data ['seller_id'];
            }
        }

        foreach ($sellerIds as $key => $id) {
            $totalProductAmt = $totalCommissionFee = $totalSellerAmt = 0;
            $productDetails = '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
            $productDetails .= '<thead><tr>';
            $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductImage . '</th><th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductName . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductQty . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductAmt . '</th>';
            $productDetails .= '<th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductCommission . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displaySellerAmount . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductStatus . '</th></tr></thead>';
            $productDetails .= '<tbody bgcolor="#F6F6F6">';
            $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
            foreach ($orderEmailData as $data) {

                if ($id == $data ['seller_id']) {
                    $sellerId = $data ['seller_id'];
                    $incrementId = $data ['increment_id'];
                    $groupId = Mage::helper('marketplace')->getGroupId();
                    $productId = $data ['product_id'];
                    $simpleProductId = $data ['product_id_simple'];                    
                    $product = Mage::helper('marketplace/marketplace')->getProductInfo($productId);
                    $productGroupId = $product->getGroupId();
                    $productName = $product->getName();
                    $productamt = $data ['product_amt'] * $data ['product_qty'];
                    $productstatus = $data['is_buyer_confirmation'];
                    

                    $products_new = Mage::getModel('catalog/product')->load($productId);
                    $product_img = $products_new->getImageUrl();
                    if($products_new->getTypeId() == "configurable"){
                    $products_new = Mage::getModel('catalog/product')->load($productId);
                        if($products_new->getSupplierSku() != ""){
                            $product_sku = $products_new->getSupplierSku();
                        }else{
                            $product_sku = $products_new->getSku();
                        }
                    $product_img = $products_new->getImageUrl();
                    $product_color = $products_new->getAttributeText('color');
                    $product_size = $products_new->getAttributeText('size');
                    }
                    else{
                            if($products_new->getSupplierSku() != ""){
                            $product_sku = $products_new->getSupplierSku();
                            }else{
                                $product_sku = $products_new->getSku();
                            }
                        $product_color = $products_new->getAttributeText('color');
                        $product_size = $products_new->getAttributeText('size');
                    }
                    if ($product_sku) {

                            $product_sku = "<br/>SKU:&nbsp;" . $product_sku;
                            
                        }else{
                            $product_sku="";
                        }

                        if ($product_size) {

                            $product_size = "<br/>Size:&nbsp;" . $product_size;
                            
                        }else{
                            $product_size="";
                        }

                        if ($product_color) {
                           $product_color = "<br/>Color:&nbsp;" . $product_color;
                            
                        }else{
                            $product_color="";
                        }
                    
                    
                    $productOptions = $product_sku.$product_size.$product_color;
                    $productDetails .= '<tr>';
                    $productDetails .= '<td align="cenetr" valign="center" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;"><img src="' . $product_img . '" width="70px"></td><td align="left" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $productName . '<br/>'. $productOptions.'</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . round($data ['product_qty']) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($productamt, 2) . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($data ['commission_fee'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($data ['seller_amount'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $productstatus . '</td>';
                    $totalProductAmt = $totalProductAmt + $productamt;
                    $totalCommissionFee = $totalCommissionFee + $data ['commission_fee'];
                    $totalSellerAmt = $totalSellerAmt + $data ['seller_amount'];
                    $orderTotal = $data ['order_total'];

                    $customerEmail = $data ['customer_email'];
                    $customerFirstname = $data ['customer_firstname'];
                    $productDetails .= '</tr>';
                }
            }
        
            $productDetails .= '</tbody><tfoot>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Seller Commision Fee</td><td align="center" style="padding:3px 9px"><span>' . $currencySymbol . round($totalCommissionFee, 2) . '</span></td></tr>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Total Amount</td><td align="center" style="padding:3px 9px"><span>' . $currencySymbol . round($totalProductAmt, 2) . '</span></td></tr>';
            $productDetails .= '</tfoot></table>';
            

            if ($groupId == $productGroupId) {
                $templateId = (int) Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification_template_selection');

                $adminEmailId = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
                $toName = Mage::getStoreConfig("trans_email/ident_$adminEmailId/name");
                $toMailId = Mage::getStoreConfig("trans_email/ident_$adminEmailId/email");

        if ($templateId) {
                    $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateId);
                } else {
                    $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_admin_approval_seller_registration_sales_notification_template_selection');
                }
                $customer = Mage::helper('marketplace/marketplace')->loadCustomerData($sellerId);
                $sellerName = $customer->getName();
                $sellerEmail = $customer->getEmail();
                $recipient = $toMailId;
                $sellerStore = Mage::app()->getStore()->getName();
                $recipientSeller = $sellerEmail;
                $emailTemplate->setSenderName($toName);
                $emailTemplate->setSenderEmail($toMailId);
                $emailTemplateVariablesValue = (array(
                    'ownername' => $toName,
                    'productdetails' => $productDetails,
                    'order_id' => $incrementId,
                    'seller_store' => $sellerStore,
                    'customer_email' => $customerEmail,
                    'customer_firstname' => $customerFirstname
                ));
                $emailTemplate->setDesignConfig(array(
                    'area' => 'frontend'
                ));
                $emailTemplate->getProcessedTemplate($emailTemplateVariablesValue);
                /**
                 * Send email to the recipient
                 */
                $emailTemplate->send($recipient, $toName, $emailTemplateVariablesValue);
                $emailTemplateVariablesValue = (array(
                    'ownername' => $sellerName,
                    'productdetails' => $productDetails,
                    'order_id' => $incrementId,
                    'seller_store' => $sellerStore,
                    'customer_email' => $customerEmail,
                    'customer_firstname' => $customerFirstname

                ));
                
                $emailTemplate->send($recipientSeller, $sellerName, $emailTemplateVariablesValue);
                return true;
            }
        }      
    }

    public function getProductConfirm($getOrderId, $getSellerId){
        $counter = 0;
        $i = 0;
        $orderDetails = Mage::getModel('sales/order')->load($getOrderId);
        $notConfirmedProducts = Mage::helper('marketplace/vieworder')->notConfirmedOrderProductIds($getSellerId, $getOrderId);
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedOrderProductIds($getSellerId, $getOrderId);
        $totalRejectedProducts = count($rejectedProducts);
        $totalOrder = count($orderDetails->getAllVisibleItems());

        foreach ($orderDetails->getAllVisibleItems() as $item) {
            if ($item->getStatus() == "Canceled") {
                $itemid[$i] = $item->getProductId();
                $i = $i + 1;
            }
        }

        foreach ($notConfirmedProducts as $not) {
            if (!in_array($not, $itemid)) {
                $counter = $counter + 1;
            }
        }

        if ($counter == 0) {
            $totalApproved = $totalOrder - $totalRejectedProducts;
            if ($totalApproved == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /*
     * Checking if all products are rejected or not
     *
     * This function written by sergee updated by Me (hassan) I updated condition item_order_status with two more options  rejected_seller & rejected_buyer
     * if rejected all items it will return true_seller
     */
    public function getProductReject($getOrderId)
    {
        $orderDetailsSize = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('product_id'))
            ->addFieldToFilter('order_id', $getOrderId)->getSize();

        $rejected_canceled_ProductsSize = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect(array('order_id', 'item_order_status', 'is_buyer_confirmation', 'product_id'))
            ->addFieldToFilter('order_id', $getOrderId)
            ->addFieldToFilter(array(
                'item_order_status'
            ), array(
                array(
                    array(
                        'eq' => 'canceled'
                    ),
                    array(
                        'eq' => 'rejected_seller'
                    ),
                    array(
                        'eq' => 'rejected_buyer'
                    )
                )
            ))
            ->addFieldToFilter(array(
                'is_seller_confirmation'
            ), array(
                array(
                    array(
                        'eq' => 'Yes'
                    ),
                    array(
                        'eq' => 'No'
                    ),
                    array(
                        'eq' => 'Rejected'
                    )
                )
            ));
        $rejected_canceled_ProductsSize = $rejected_canceled_ProductsSize->getSize();
        $totalRejectedProducts = $orderDetailsSize - $rejected_canceled_ProductsSize;
        if ($totalRejectedProducts == 0) {
            return "true_seller";
        } else {
            return "false_seller";
        }
    }
    
    public function timeAgo($time_elapsed)
    {
        $seconds    = $time_elapsed ;
        $minutes    = round($time_elapsed / 60 );
        $hours      = round($time_elapsed / 3600);
        $days       = round($time_elapsed / 86400 );
        $weeks      = round($time_elapsed / 604800);
        $months     = round($time_elapsed / 2600640 );
        $years      = round($time_elapsed / 31207680 );
        if($seconds==null){
            return "";
        }
        // Seconds
        else if($seconds> 0 && $seconds <= 60){
            return "just now";
        }
        //Minutes
        else if($minutes <=60){
            if($minutes==1){
                return "one minute ago";
            }
            else{
                return "$minutes minutes ago";
            }
        }
        //Hours
        else if($hours <=24){
            if($hours==1){
                return "an hour ago";
            }else{
                return "$hours hrs ago";
            }
        }
        //Days
        else if($days <= 7){
            if($days==1){
                return "yesterday";
            }else{
                return "$days days ago";
            }
        }
        //Weeks
        else if($weeks <= 4.3){
            if($weeks==1){
                return "a week ago";
            }else{
                return "$weeks weeks ago";
            }
        }
        //Months
        else if($months <=12){
            if($months==1){
                return "a month ago";
            }else{
                return "$months months ago";
            }
        }
        //Years
        else{
            if($years==1){
                return "one year ago";
            }else{
                return "$years years ago";
            }
        }
    }

    public function getSession($time, $next = false) {
        $datetime = date("jS F Y H:i:s", Mage::getModel('core/date')->timestamp(strtotime($time)));
        $time = date("h:i A", Mage::getModel('core/date')->timestamp(strtotime($time)));


        $session_times = [];
        $session_times["morning"] = [8,9,10,11];
        $session_times["afternoon"] = [12,13,14,15,16,17];
        $session_times["evening"] = [18,19,20,21,22,23];

        $h = (int)date('H', strtotime($datetime));
        $m = (int)date('i', strtotime($datetime));
        
        if (in_array($h, $session_times["morning"]) && $m <= 59) {
            if ($next) {
                return "Afternoon";
            }
            return "Morning";
        }
        if (in_array($h, $session_times["afternoon"]) && $m <= 59) {
            if ($next) {
                return "Evening";
            }
            return "Afternoon";
        }
        if (in_array($h, $session_times["evening"]) && $m <= 59) {
            if ($next) {
                return "Morning";
            }
            return "Evening";
        }
    }
    
    public function getCategories(){
        $categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addIsActiveFilter(); 
        return $categories;
    }

    public function filterOnOrderStatus($status){
        $ordersItems = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('increment_id')
            ->addFieldToFilter('order_status',array('in'=>$status))
            ->addFieldToFilter('increment_id', array ('nin' => array('0','')));

        $increment_ids = array();
        if(count($ordersItems)){
            foreach($ordersItems as $item){
                array_push($increment_ids, $item->getIncrementId());
            }
        }

        return array_unique($increment_ids);
    }

    public function filterOnCloseOrderStatus($status){

         $ordersItems = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('increment_id')
            ->addFieldToFilter('increment_id', array ('nin' => array('0','')));
        
        if( $status == 'not_canceled' )
            $ordersItems = $ordersItems->addFieldToFilter('order_status',array('nin'=>array('canceled')));

        if( $status == 'successful_delivery' )
            $ordersItems = $ordersItems->addFieldToFilter('order_status',array('in'=>array('successful_delivery')));

        $increment_ids = array();
        if(count($ordersItems)){
            foreach($ordersItems as $item){
                array_push($increment_ids, $item->getIncrementId());
            }
        }

        return array_unique($increment_ids);
    }

    public function filterOnCustomerConfirmedStatus($time){
        $from = date('Y-m-d H:i:s', strtotime('-'.$time.' hour'));
        $to = date('Y-m-d H:i:s');
        $ordersItems = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('increment_id')
            ->addFieldToFilter('increment_id', array ('nin' => array('0','')))
            ->addFieldToFilter('is_buyer_confirmation', array('eq' => 'Yes'))
            ->addFieldToFilter('is_buyer_confirmation_date', array('from' => $from, 'to' => $to));
        
        $increment_ids = array();
        if(count($ordersItems)){
            foreach($ordersItems as $item){
                array_push($increment_ids, $item->getIncrementId());
            }
        }

        return array_unique($increment_ids);
    }

    public function marketplace_data($product_sku,$order_id){

         $_product = Mage::getModel('catalog/product')
                ->getCollection()                
                ->addAttributeToFilter('sku',$product_sku)->getFirstItem();
        $product_id = $_product->getId();

         $marketplace = Mage::getModel('marketplace/commission')->getCollection()
                       ->addFieldToSelect('*')
                       ->addFieldToFilter('product_id',$product_id)
                        ->addFieldToFilter('order_id',$order_id)
                       ->addFieldToFilter(array(
                                            'is_seller_confirmation',
                                            'is_buyer_confirmation'
                                            ),
                                  array(
                              array('in'=> array('Yes','Rejected')),
                              array('in'=> array('Yes','Rejected'))
                          )
                    )->getFirstItem();
            $marketplace->getSelect(); 
            return $marketplace;
    }

    public function getProductConfirmSellerRejected($getOrderId, $getSellerId)
    {

        $i = 0;
        $orderDetails = Mage::getModel('marketplace/commission')->getCollection();
        $orderDetails->addFieldToFilter('order_id',$getOrderId);
        $notConfirmedProducts =
            Mage::helper('marketplace/vieworder')->notConfirmedSellerProductIds($getSellerId, $getOrderId);
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedOrderProductSeller($getSellerId, $getOrderId);
        $totalRejectedProducts = count($rejectedProducts);
        $totalOrder = count($orderDetails);

        $notConfirmedProductsTotal = $totalOrder-$totalRejectedProducts;

        if($notConfirmedProductsTotal == 0) {
            return true;
        }
        else{
            return false;
        }
    }

    public function approved_items($getOrderId){

        /**
         * Load commission data
         */
        $counter_rejected = 0;
        $counter_accepted = 0;
        $counter_remaining = 0;
        /*
         * getting total items for comaprison
         */
        $all_products = Mage::getModel('marketplace/commission')->getCollection();
        $all_products->addFieldToFilter('order_id', $getOrderId);
        $all_products = count($all_products);
        /*
         * getting all items that are not confirmed by buyer
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_id', 'item_order_status', 'is_buyer_confirmation','is_seller_confirmation' ,'product_id'));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_buyer_confirmation','No');

        if(count($products)>0){
            /*
             * if any one of the product is not confirmed for buyer order status will be pesnding_customer_confirmation
             */
            return "pending_customer_confirmation";
        }
        elseif(count($products)==0){
            /*
             * if all of the products were confirmed or rejected
             */
            $products = Mage::getModel('marketplace/commission')->getCollection();
            $products->addFieldToSelect(array('order_id', 'item_order_status', 'is_buyer_confirmation','is_seller_confirmation' ,'product_id'));
            $products->addFieldToFilter('order_id', $getOrderId);
            $products->addFieldToFilter('is_buyer_confirmation',array('in' => array('Yes','Rejected')));

            foreach($products as $product){
                if($product->getIsSellerConfirmation() == "Rejected"){
                    $counter_rejected = $counter_rejected + 1;
                }
                elseif($product->getIsSellerConfirmation() == "Yes"){
                    $counter_accepted = $counter_accepted + 1;
                }
                elseif(($product->getIsSellerConfirmation() == "No")){
                    $counter_remaining = $counter_remaining + 1;
                }
            }
            /*
             * Items that were accepted
             */
            $confirmed_products = $all_products - $counter_rejected;

            if($counter_rejected == $all_products ){
                /*
                 * if all items are rejected from seller side
                 */
                return "canceled";
            }
            elseif($counter_accepted == $confirmed_products ){
                /*
                 * if all items are confirmed from seller side
                 */
                return "confirmed";
            }
            elseif($counter_remaining > 0){
                return "pending_seller_confirmation";
            }

        }

    }

}