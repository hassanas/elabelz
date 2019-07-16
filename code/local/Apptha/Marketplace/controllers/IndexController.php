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
class Apptha_Marketplace_IndexController extends Mage_Core_Controller_Front_Action {

 /**
  * Retrieve customer session model object
  *
  * @return Mage_Customer_Model_Session
  */
 protected function _getSession() {
  return Mage::getSingleton ( 'customer/session' );
 }
 /**
  * Load phtml file layout
  *
  * @return void
  */
 public function indexAction() {
  if (Mage::helper ( 'marketplace' )->checkMarketplaceKey () != '') {
   $msg = Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
   Mage::app ()->getResponse ()->setBody ( $msg );
   return;
  } else {
   if (! $this->_getSession ()->isLoggedIn ()) {
    Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'You must have a Seller Account to access this page' ) );
    $this->_redirect ( 'marketplace/seller/login' );
    return;
   }
   $this->loadLayout ();
   $this->renderLayout ();
  }
 }
 /**
  * Display home page banner images
  *
  * @return void
  */
 public function bannerAction() {
  Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
  $this->loadLayout ();
  $this->renderLayout ();
 }
 /**
  * Display category listings
  *
  * @return void
  */
 public function categorydisplayAction() {
  Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
  $this->loadLayout ();
  $this->renderLayout ();
 }

 public function sendOrderEmailAction()
    {
        $email = $this->getRequest ()->getParam ( 'email' );
        $id = $this->getRequest ()->getParam ( 'id' );
        $name = $this->getRequest ()->getParam ( 'name' );
        $size = $this->getRequest ()->getParam ( 'size' );
        $body = 'Customer email Address :'.$email.' Product Id : '.$id.' , Product Name : '.$name.' Size : '.$size.''; 
        
        
        // // This is the name that you gave to the template in System -> Transactional Emails
        // $emailTemplate = Mage::getModel('core/email_template')->loadByCode('productEmail');

        // // These variables can be used in the template file by doing {{ var some_custom_variable }}
        // $emailTemplateVariables = array(
        // 'email' => $email,
        // 'id' => $id,
        // 'name' => $name
        // );

        // $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);

        // $emailTemplate->setSenderEmail($email);
        // $emailTemplate->setTemplateSubject("Size Guide");

        // $emailTemplate->send('humaira.batool@progos.org', 'Joanna Bloggs', $emailTemplateVariables);

        

         // $productDetails = '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
         // $productDetails .= '<thead><tr>';
         // $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $id . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $email . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductAmt . '</th>';
         // $productDetails .= '<th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $name . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $size . '</th></tr></thead>';
         // $templateId = ( int )Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification_template_selection');

         // $adminEmailId = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
         // $toName = Mage::getStoreConfig("trans_email/ident_$adminEmailId/name");
         // $toMailId = "elabelz.com";
         //  if ($templateId) {
         //            $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateId);
         //  } else {
         //            $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_admin_approval_seller_registration_sales_notification_template_selection');
         //  }

         //  $recipient = $toMailId;
         //  $emailTemplate->setSenderName('Admin');
         //  $emailTemplate->setSenderEmail('humaira.batool@progos.org');
         //  $emailTemplateVariablesValue = (array(
         //            'productdetails' => $productDetails,
         //            'customer_email' => $email
         //        ));
         //  $emailTemplate->setDesignConfig(array(
         //            'area' => 'frontend'
         //        ));
         //  $emailTemplate->getProcessedTemplate($emailTemplateVariablesValue);
         //        /**
         //         * Send email to the recipient
         //         */
         //  $emailTemplate->send($recipient, $toName, $emailTemplateVariablesValue);

        
       
    }

   public function savesessionAction()
    {
    
      $t = $this->getRequest ()->getParam ( 'count' );
      $label = $this->getRequest ()->getParam ( 'label' );
      $itemCount = $this->getRequest ()->getParam ( 'itemCount' );
      $counter = $this->getRequest ()->getParam ( 'counter' );
      
      $t = json_decode($t, true);
      $label = json_decode($label, true);
      $itemCount = json_decode($itemCount, true);

      Mage::getSingleton('core/session')->setMyValue($t);
      Mage::getSingleton('core/session')->setMyLabel($label);
      Mage::getSingleton('core/session')->setMyCount($itemCount);
      Mage::getSingleton('core/session')->setMyCounter($counter);
    } 
}