<?php
require 'app/Mage.php';


Mage::app();
$sellerids=array('152'=>'125','147'=>'120','156'=>'129','218'=>'216');
  foreach($sellerids as $key=>$sellerid){
    echo '<br>'.$key.'--'. $sellerid;
     try {
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addAttributeToFilter ( 'seller_id', $sellerid);
            foreach ($collection as $product_new) {
                $products = Mage::getModel('catalog/product')->load($product_new->getId());
                    $products = Mage::getModel('catalog/product')->load($product_new->getId());
                    echo $products->getId()."<br/>";
                    echo $products->getData('seller_id')." Previous seller id <br/>";
                    $products->setData('seller_id',$key);
                    $products->save();
                    echo $products->getData('seller_id')." New seller id<br/>"; 
                    echo "successfull";     
            }
            
    } catch (Exception $e) {
            logException($e);
    }
}