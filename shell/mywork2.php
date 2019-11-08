<?php

require_once(__DIR__ . '/../app/Mage.php'); //Path to Magento
umask(0);
Mage::app();
echo  'hi ';
				/*
                 * Date calculation, Current date and Previous date
                 */
        $currentDate = date ( "Y-m-d ", Mage::getModel ( 'core/date' )->timestamp ( time () ) );
        $previousDate = date ( "Y-m-d ", Mage::getModel ( 'core/date' )->timestamp ( strtotime($currentDate .' -1 day') ) );
        
        // Getting all seller ID's whose products updated in from yesterday
        
        $_sellerCollection = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToFilter('updated_at', array(
                                                'from' => $previousDate,
                                                'date' => true,
                                                ))
                       	->addAttributeToSelect('seller_id')
                        ->groupByAttribute('seller_id')
                        ->load();
                        
        foreach ($_sellerCollection as $_seller){
	 
             $sellerId = $_seller->getSellerId (); 
             $products = "";
                 
             //Loading seller product collection
             $_productCollection = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToFilter('updated_at', array(
                                                'from' => $previousDate,
                                                'date' => true,
                                                ))
                        ->addAttributeToFilter ( 'seller_id', $_seller->getSellerId ())					  
                        ->addAttributeToSelect('*')
                        ->load();
                
                        
              foreach ($_productCollection as $_product){

                $products .= $_product->getId().'</br>';
                $products .= $_product->getName().'</br>';
                $products .= $_product->getProductUrl().'</br>';
                $products .= $_product->getPrice().'</br>';

              
              } 
              	$templateId =9;
                $adminEmailId = Mage::getStoreConfig ( 'marketplace/marketplace/admin_email_id' );
                $emailTemplate = Mage::getModel('core/email_template')->load($templateId);
              
                //Getting seller information
                
               $customer = Mage::helper ( 'marketplace/marketplace' )->loadCustomerData ( $sellerId );
               $sellerName = $customer->getName ();
               $sellerEmail = $customer->getEmail ();
               $sellerStore = Mage::app ()->getStore ()->getName ();
               $storeId= Mage::app()->getStore()->getStoreId();
               
               // Setting up default sender information
               $emailTemplate->setSenderName (Mage::getStoreConfig('trans_email/ident_general/name', $storeId) );
               $emailTemplate->setSenderEmail ( Mage::getStoreConfig('trans_email/ident_general/email', $storeId) );
                
               // Setting email variables
               $emailTemplateVariablesValue = (array (
                        'sellername' => $sellername,
                        'products' => $products,
                        'seller_store' => $sellerStore,
                         
                ));
                
                $emailTemplate->getProcessedTemplate ( $emailTemplateVariablesValue );
                
                /**
                 * Send email to the seller
                 */
               
               $emailTemplate->send ('alinasrulah@gmail.com', 'Ali Nasrullah', $emailTemplateVariablesValue ); 
               $emailTemplate->send ($sellerEmail, $sellerName, $emailTemplateVariablesValue );
            }
               
        
        ?>
