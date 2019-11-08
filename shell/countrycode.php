<?php
require_once(__DIR__ . '/../app/Mage.php');
umask(0);
Mage::app();
         $sellerAcceptedIncrementIds = array();
         $sellerPendingIncrementIds = array();
         $orders = Mage::getModel ( 'marketplace/commission' )->getCollection ()
         ->addFieldToSelect ( '*' )
         ->setOrder ( 'created_at', 'DESC' )
         ->addFieldToFilter('is_buyer_confirmation','Yes');
          foreach ($orders as $collection) {          
               array_push($sellerAcceptedIncrementIds, $collection->getIncrementId());
         }

        $sellerAcceptedIncrementIds = array_unique($sellerAcceptedIncrementIds);
        // print_r($sellerAcceptedIncrementIds);
        $orders_pending = Mage::getModel ( 'marketplace/commission' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->setOrder ( 'created_at', 'DESC' )
        ->addFieldToFilter('is_buyer_confirmation','No');
         foreach ($orders_pending as $collection) {     
              array_push($sellerPendingIncrementIds, $collection->getIncrementId());
        }

        $sellerPendingIncrementIds = array_unique($sellerPendingIncrementIds);
        $result = array_diff($sellerAcceptedIncrementIds,$sellerPendingIncrementIds);

        $orders_new = Mage::getModel ( 'sales/order' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->setOrder ( 'created_at', 'DESC' )
        ->addFieldToFilter ('main_table.increment_id',array (
            'in' => array($result)));
        foreach($orders_new as $ord):
        $_order = Mage::getModel('sales/order')->load($ord->getId());
        $_shippingAddress = $_order->getShippingAddress();
        $countryCode = $_shippingAddress->getCountry_id();
        if($countryCode !== "SA" && $countryCode !== "AE" && $countryCode !== "KW" 
        	&& $countryCode !== "QA" && $countryCode !== "BH" && $countryCode !== "OM" && $countryCode !== "IQ"):
        echo "Country Code ".$countryCode." Increment_id ".$ord->getIncrementId()."<br/>"; 
        endif;
        endforeach;

?>