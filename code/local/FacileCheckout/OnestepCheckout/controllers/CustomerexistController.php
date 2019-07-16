<?php
class FacileCheckout_OnestepCheckout_CustomerexistController extends Mage_Checkout_Controller_Action
{
  public function indexAction(){
    $email = Mage::app()->getRequest()->getParam('email');
    $password = Mage::app()->getRequest()->getParam('password');
    $customer = Mage::getModel('customer/customer');
    $websiteId = Mage::app()->getWebsite()->getId();
    $current_store_id = Mage::app()->getStore()->getId();
    if ($websiteId) {
        $customer->setWebsiteId($websiteId);
    }

    $customer->loadByEmail($email); 

    $customer_store_id = $customer->getStoreId();
    $_store_1 = Mage::getModel('core/store')->load($current_store_id);
    $current_store_code = $_store_1->getCode();
    $_store_2 = Mage::getModel('core/store')->load($customer_store_id);
    $customer_store_code = $_store_2->getCode();
    if ($customer->getId()) {
      echo "true";
    } else {
      echo "false";
    }
  }

  public function customerAction(){
    $email = Mage::app()->getRequest()->getParam('email');
    $password = Mage::app()->getRequest()->getParam('password');
    $customer = Mage::getModel('customer/customer');
    $websiteId = Mage::app()->getWebsite()->getId();

    if ($websiteId) {
        $customer->setWebsiteId($websiteId);
    }

         $customer->loadByEmail($email);
          if ($customer->getId()) {
          try{
           $blah = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->authenticate($email, $password);
           echo "true";
          } catch (Exception $e) {
              echo "0";
          }
        
        }

        else{
           
          echo "false";
        }
       
        

  }

  public function customerchkAction(){
    $email = Mage::app()->getRequest()->getParam('email');
    $password = Mage::app()->getRequest()->getParam('password');
    $customer = Mage::getModel('customer/customer');
    $websiteId = Mage::app()->getWebsite()->getId();
    $current_store_id = Mage::app()->getStore()->getId();

    if ($websiteId) {
      $customer->setWebsiteId($websiteId);
    }

    $customer->loadByEmail($email);
    $customer_store_id = $customer->getStoreId();
    $_store_1 = Mage::getModel('core/store')->load($current_store_id);
    $current_store_code = $_store_1->getCode();
    $_store_2 = Mage::getModel('core/store')->load($customer_store_id);
    $customer_store_code = $_store_2->getCode();

    if ($customer->getId()) {
      if ($current_store_id) {
        try {
          $blah = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->authenticate($email, $password);
          // echo json_encode(["status"=>true]);
          echo "true";
        } catch (Exception $e) {
          echo json_encode(["status"=>false, "message"=>$e->getMessage()]);
          // echo $e->getMessage();
        }
      } else {
        echo json_encode(["status"=>false, "message"=>"switch", "switch_to" => $customer_store_code]);
        // echo "switch";
      }
    } else {
      echo json_encode(["status"=>false, "message"=>"You are not registered with this store."]);
      // echo "false";
    }
       
        

  }
}