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
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */

/**
 * This file is used to manage order information
 */
class Apptha_Marketplace_OrderController extends Mage_Core_Controller_Front_Action 
{
    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession() {
        return Mage::getSingleton('customer/session');
    }
    /**
     * Load phtml layout file to display order information
     * 
     * @return void
     */
    public function indexAction() {
        if (!$this->_getSession()->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Manage orders by sellers
     * 
     * @return void
     */
    public function manageAction() {
        Mage::helper('marketplace')->checkMarketplaceKey();
        if (!$this->_getSession()->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function cancel_orderAction() {
    // $order = Mage::getModel('sales/order')->load($orderid, 'increment_id');
    // $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
    // $order->save();
    

    }
     /**
      * View full order information by seller
      * 
      * @return void
      */
     public function vieworderAction(){
        	/**
    	 * check license key
    	 */
    	Mage::helper('marketplace')->checkMarketplaceKey();
    	
    	/**
    	 *  Initilize customer and seller group id
    	*/
    	$customerGroupId = $sellerGroupId = $customerStatus = '';
    	$customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
    	$sellerGroupId = Mage::helper('marketplace')->getGroupId();
    	$customerStatus = Mage::getSingleton('customer/session')->getCustomer()->getCustomerstatus();
    	
    	if (!Mage::getSingleton('customer/session')->isLoggedIn() && $customerGroupId != $sellerGroupId) {
    		Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
    		$this->_redirect('marketplace/seller/login');
    		return false;
    	}
    	/**
    	 *  Checking whether customer approved or not
    	 */
    	if ($customerStatus != 1) {
    		Mage::getSingleton('core/session')->addError($this->__('Admin Approval is required. Please wait until admin confirms your Seller Account'));
    		$this->_redirect('marketplace/seller/login');
    		return false;
    	}	
    	
    	$orderId = $this->getRequest()->getParam('orderid');
    	
    	$orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(),$orderId);
    	if(count($orderPrdouctIds) <= 0){
    	$this->_redirect('marketplace/order/manage');
    	return false;
    	}

    	$collection = Mage::getModel('marketplace/commission')->getCollection()
    	->addFieldToFilter('seller_id',Mage::getSingleton('customer/session')->getId())
    	->addFieldToFilter('order_id',$orderId)    
    	->getFirstItem();
    	
    	if(count($collection) >=1 && $collection->getOrderId() == $orderId){       
          $this->loadLayout();
          $this->renderLayout();  
    	  }else{
    	 Mage::getSingleton('core/session')->addError($this->__('You do not have permission to access this page'));
    	 $this->_redirect('marketplace/order/manage');
    	 return false;
         } 
      }
     /**
      * View full transaction history by seller
      * 
      * @return void
      */
      function viewtransactionAction(){
        Mage::helper('marketplace')->checkMarketplaceKey();
        if (!$this->_getSession()->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
          $this->loadLayout();
          $this->renderLayout();  
      }

      public function printAction() {
          $this->loadLayout();
          $this->renderLayout();  
      }

      public function allprintAction() {
            $this->loadLayout();
            $this->renderLayout();
      }
       /**
        * Seller payment acknowledgement
        * 
        * @return void
        */
      function acknowledgeAction(){
        Mage::helper('marketplace')->checkMarketplaceKey();
        if (!$this->_getSession()->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        } 
          $this->loadLayout();
          $this->renderLayout();
          $commissionId = $this->getRequest()->getParam('commissionid');        
          if($commissionId!=''){
          $collection = Mage::getModel('marketplace/transaction')->changeStatus($commissionId);          
          if($collection==1){
              Mage::getSingleton('core/session')->addSuccess($this->__("Payment received status has been updated")); 
              $this->_redirect('marketplace/order/viewtransaction');
          } else  {
             Mage::getSingleton('core/session')->addError($this->__('Payment received status was not updated'));
             $this->_redirect('marketplace/order/viewtransaction'); 
          }
      }
   }
	/**
     * customer order cancel request/ customer refund item request has been intertained here
     * 
     * @return void
     */   
   	public function cancelAction(){   		
   		$orderCancelStatusFlag = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/order_cancel_request');
   		$data = $this->getRequest()->getPost(); 
   		$emailSent = '';
   		
      // $orderId = "100000015";
   		$orderId = $data['order_id'];
   		$loggedInCustomerId = '';
   		if(Mage::getSingleton('customer/session')->isLoggedIn() && isset($orderId)) {
   			$customerData = Mage::getSingleton('customer/session')->getCustomer();
   			$loggedInCustomerId = $customerData->getId();
   			$customerid = Mage::getModel('sales/order')->load($data['order_id'])->getCustomerId();   		
   		}else{
   			Mage::getSingleton('core/session')->addError($this->__("You do not have permission to access this page"));
   			// $this->_redirect('sales/order/history');
   			return;
   		}
// custom
// 
      // $_order = Mage::getModel('sales/order')->load($orderId);
      // $incrementId = $_order->getIncrementId();
      // $sellerProductDetails = array();
      // $selectedProducts = $data['products'];                


      // var_dump($orderCancelStatusFlag);
      // var_dump($loggedInCustomerId);
      // var_dump($customerid);
      // var_dump($loggedInCustomerId);
      // exit;

   		if($orderCancelStatusFlag == 1 && !empty($loggedInCustomerId) && $customerid == $loggedInCustomerId){ 				
   		
   			$shippingStatus = 0;// its means order is cancel by customer
   			    try {
                $templateId = (int) Mage::getStoreConfig('marketplace/admin_approval_seller_registration/order_cancel_request_notification_template_selection');
          
                if ($templateId) {
                    $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateId);
                } else {
                    $emailTemplate = Mage::getModel('core/email_template')
                            ->loadDefault('marketplace_cancel_order_admin_email_template_selection');
                }                
                
                $_order = Mage::getModel('sales/order')->load($orderId);
                $incrementId = $_order->getIncrementId();
                $sellerProductDetails = array();
                $selectedProducts = $data['products'];                
                $selectedItemproductId = '';
                
                foreach($_order->getAllItems() as $item){
                $itemProductId = $item->getProductId();
                $orderItem = $item;                  
                if(in_array($itemProductId,$selectedProducts)){
                  if( $orderItem->getQtyShipped() <  $orderItem->getQtyOrdered() && $orderItem->getIsVirtual() != 1){
                  $shippingStatus = 1;
                  }
                $parent_id = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($itemProductId);
                $sellerId = Mage::getModel('catalog/product')->load($parent_id)->getSellerId();
                $selectedItemproductId = $itemProductId; 
                $sellerProductDetails[$sellerId][] = $item->getName();
                }
                }                

                foreach($sellerProductDetails as $key => $productDetails){
                $productDetailsHtml = "<ul>";
                foreach($productDetails as $productDetail){
                $productDetailsHtml .= "<li>";
                $productDetailsHtml .= $productDetail;
                $productDetailsHtml .= "</li>";                	
                }	
                $productDetailsHtml .= "</ul>";                 
            
                $customer = Mage::getModel('customer/customer')->load($loggedInCustomerId);
                $seller = Mage::getModel('customer/customer')->load($key);
                
                $buyerName = $customer->getName();
                $buyerEmail = $customer->getEmail();              

                $sellerEmail = $seller->getEmail();
                $sellerName = $seller->getName();
                
                $recipient = $sellerEmail;
        
                $sellerStore = Mage::app()->getStore()->getName();               
                                
                $emailTemplate->setSenderName($buyerName);
                $emailTemplate->setSenderEmail($buyerEmail); 

                /**
                 * To set cancel/refund request sent
                 */                
                if($shippingStatus == 1){
                $requestedType = $this->__('cancellation');	            
                Mage::getModel('marketplace/order')->updateSellerRequest($selectedItemproductId,$orderId,$loggedInCustomerId,$sellerId,0);               
                }else{
                $requestedType = $this->__('return');                         
                Mage::getModel('marketplace/order')->updateSellerRequest($selectedItemproductId,$orderId,$loggedInCustomerId,$sellerId,1);                               
                }          
                
                $emailTemplateVariables = array(
                		'ownername' => $sellerName,
                		'productdetails' => $productDetailsHtml,
                		'order_id' => $incrementId,
                		'customer_email' => $buyerEmail,
                		'customer_firstname' => $buyerName,
                		'reason' => $data['reason'],
                		'requesttype' => $requestedType,
                		'requestperson' => $this->__('Customer')
                );
                
                $emailTemplate->setDesignConfig(array('area' => 'frontend'));
                /**
                 *  Sending email to admin
                */
                $emailTemplate->getProcessedTemplate($emailTemplateVariables);
                $emailSent =  $emailTemplate->send($recipient, $sellerName, $emailTemplateVariables);
                } 
                
                if($shippingStatus == 1){
                Mage::getSingleton('core/session')->addSuccess($this->__("Item cancellation request has been sent successfully."));
                }else{
                Mage::getSingleton('core/session')->addSuccess($this->__("Item return request has been sent successfully."));
                }                     
                $this->_redirect('sales/order/view/order_id/'.$data['order_id']);
                
                } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
                $this->_redirect('sales/order/view/order_id/'.$data['order_id']); 
                }           
   		}else{
   			Mage::getSingleton('core/session')->addError($this->__("You do not have permission to access this page"));   			
   			$this->_redirect('sales/order/view/order_id/'.$orderId);
   		}   	
   }

  function checkVerifyAction(){
       $nexmoApiKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apikey');
       $nexmoApiSecretKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apisecret');
       
       $request_id = Mage::app()->getRequest()->getParam('request_id');
       $code = Mage::app()->getRequest()->getParam('sms_code');
       $increment_id = Mage::app()->getRequest()->getParam('increment_id');
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
        if($data['status'] == 0){
          $status = "yes";
        }
        else{
          $status = "no";
        }
        $collection = Mage::helper("marketplace/marketplace")->getRequestId($increment_id);
        foreach($collection as $col){
          $id = $col['id'];
          $order_id = $col['order_id'];
          $model = Mage::getModel("marketplace/commission")->load($id);
          $model->setSmsVerifyStatus($status);
          // if($status == "yes"):
          //   $model->setIsBuyerConfirmation("Yes");
          //   endif;
          $model->save(); 
        // if($status == "yes"):
        //   $data = Mage::helper("marketplace/marketplace")->successAfter($order_id);
        // endif; 
        }
        if($data['status'] == 0 ){
            echo "true";
        }elseif($data['status'] == 18 ){
            echo $this->__("Cant Verify Too many request_ids provided");
        }
        elseif($data['status'] == 10){
             echo $this->__("Cant Verify Concurrent verifications to the same number are not allowed");
        }
        else{
          echo $this->__("Cant There are no matching Verify request.");
        }
    }

    function cancelSendAction(){
        $nexmoApiKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apikey');
        $nexmoApiSecretKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apisecret');
        $phNum = Mage::app()->getRequest()->getParam('phnNo');
        $request_id = Mage::app()->getRequest()->getParam('request_id');
        $increment_id = Mage::app()->getRequest()->getParam('increment_id');
        $url = 'https://api.nexmo.com/verify/control/json?' . http_build_query([
                'api_key' => $nexmoApiKey,
                'api_secret' => $nexmoApiSecretKey,
                'request_id' => $request_id,
                'cmd' => 'cancel'
            ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        error_log($response);
        $data = json_decode($response, true);
        
        if($data['status'] == 0):
           $this->sendVerify($phNum,$increment_id);
        elseif($data['status'] == 19):
            echo $this->__("Cant Send Too many attempts to re-deliver have already been made");
        else:
           echo "Cant Send too many attempts";
        endif;


    }

    function sendVerify($phNum,$increment_id){
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
        if($data['status']==0){
        $collection = Mage::helper("marketplace/marketplace")->getRequestId($increment_id);
        foreach($collection as $col):
          $id = $col['id'];
          $model = Mage::getModel("marketplace/commission")->load($id);
          $model->setSmsVerifyCode($data['request_id']);
          $model->save();
        endforeach;
       }
       if($data['status'] == 0 && $data['request_id'] != "" ):
        print_r($data['request_id']);
        return $data['request_id'];
        else:
            echo $this->__("Cant Send ".$data['error_text']);
        endif;
    }

    function sendVerifyNewAction(){
        $nexmoApiKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apikey');
        $nexmoApiSecretKey =  Mage::getStoreConfig('marketplace/nexmo/nexmo_apisecret');
        $nexmoBrand =  Mage::getStoreConfig('marketplace/nexmo/nexmo_brand');

         $phNum = Mage::app()->getRequest()->getParam('phnNo');
         $request_id = Mage::app()->getRequest()->getParam('request_id');
         $increment_id = Mage::app()->getRequest()->getParam('increment_id');
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
        if($data['status']==0){
        $collection = Mage::helper("marketplace/marketplace")->getRequestId($increment_id);
        foreach($collection as $col):
          $id = $col['id'];
          $model = Mage::getModel("marketplace/commission")->load($id);
          $model->setSmsVerifyCode($data['request_id']);
          $model->save();
        endforeach;
       }
       if($data['status'] == 0 && $data['request_id'] != "" ):
        print_r($data['request_id']);
        return $data['request_id'];
        else:
            echo $this->__("Cant Send ".$data['error_text']);
        endif;
    }

    public function saveItemStatusAction(){
       $itemStatus = $this->getRequest()->getPost('itemStatus');
       $incremen_id = $this->getRequest()->getPost('increment_id');
       $product_id = $this->getRequest()->getPost('product_id');
       $data_new = array('is_seller_confirmation'=> $itemStatus);
       $commission = Mage::getModel('marketplace/commission')->getCollection()
                     ->addFieldToFilter('increment_id',$incremen_id )
                     ->addFieldToFilter('product_id',$product_id);
      foreach($commission as $com):
       $id = $com->getId();
       $model = Mage::getModel('marketplace/commission')->load($id);
       $model->setIsSellerConfirmation($itemStatus);
       $model->save();
     endforeach;
     return Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));

     }   

      public function saveShipStatusAction(){
       $shipStatus = $this->getRequest()->getPost('shipStatus');
       $incremen_id = $this->getRequest()->getPost('increment_id');
       $product_id = $this->getRequest()->getPost('product_id');
       $data_new = array('ship_status'=> $shipStatus);
       $commission = Mage::getModel('marketplace/commission')->getCollection()
                     ->addFieldToFilter('increment_id',$incremen_id )
                     ->addFieldToFilter('product_id',$product_id);
      foreach($commission as $com):
       $id = $com->getId();
       $model = Mage::getModel('marketplace/commission')->load($id);
       $model->setShipStatus($shipStatus);
       $model->save();
     endforeach;

     }  

     public function saveMarketplaceNotesAction(){
       $comment = $this->getRequest()->getPost('comment');
       $increment_id = $this->getRequest()->getPost('increment_id');
       $product_id = $this->getRequest()->getPost('product_id');
       echo $comment;
       echo $incremen_id;
       echo $product_id;
       $data_new = array('increment_id'=>$increment_id,'item_id'=>$product_id,'note'=>$comment);
       $commission = Mage::getModel('marketplace/notes')->setData($data_new);
       $commission->save();

     }  
    
    public function saveEmailNotificationAction(){

      $seller_id = $this->getRequest()->getPost('seller_id');
      $id = $this->getRequest()->getPost('id');
      if($id === "true"){
        $data_new = array('cancel_email_notifications'=>1);
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->collectprofile ( $seller_id );
        $seller_model = Mage::getModel("marketplace/sellerprofile")->load($seller->getId());
        $seller_model->addData($data_new);
        $seller_model->save();
      }
      else{
        $data_new = array('cancel_email_notifications'=>0);
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->collectprofile ( $seller_id );
        $seller_model = Mage::getModel("marketplace/sellerprofile")->load($seller->getId());
        $seller_model->addData($data_new);
        $seller_model->save();
      }

    }
    //checking if bin no is in array or not
    public function check_binnoAction(){
        $bin_no = $this->getRequest()->getParam('bin_no');
        $binno_required = explode(",", Mage::getStoreConfig("marketplace/binno/binno20"));
        if(in_array($bin_no, $binno_required)){
            echo $result = 1;
        }
        else{
            echo $result = 0;
        }

    }

    public function getorder_urlAction($order, $direction)
    {
        echo Mage::app()->getLayout()->createBlock('catalog/product_list_toolbar')->setTemplate('catalog/product/list/sortby_list.phtml')->toHtml();
    }
} 