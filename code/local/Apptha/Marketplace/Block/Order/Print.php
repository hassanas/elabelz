<?php

class Apptha_Marketplace_Block_Order_Print extends Mage_Core_Block_Template {

    function viewOrder($orderId) {
    	/**
    	 * Load commission model
    	 */
        $order = Mage::getModel('marketplace/commission')->getCollection();
        $order->addFieldToSelect('*');
        /**
         * Filter by seller id
         */
        $order->addFieldToFilter('seller_id', Mage::getSingleton('customer/session')->getCustomer()->getId());
        /**
         * Filer by order id
         */
        $order->addFieldToFilter('order_id', $orderId);
        /**
         * Return order
         */
        return $order;
    }
    
    public function getShipPostUrl($orderId){
    	/**
    	 * Getting ship post url
    	 */
    	return $this->getUrl('marketplace/shipment/savePost',array(
    		'order_id'=>$orderId,
    	));
    }
    
    /**
     * Get order product data
     * 
     * @param number $sellerId
     * @param number $orderId
     * @param number $productId
     * @return array $productData
     */
    public function getOrderProductData($sellerId,$orderId,$productId){
    	/**
    	 * Load commission model
    	 */
    $productData = Mage::getModel('marketplace/commission')->getCollection()
    ->addFieldToFilter('seller_id',$sellerId)
    ->addFieldToFilter('order_id',$orderId)  
    ->addFieldToFilter('product_id',$productId)->getFirstItem();
    /**
     * Return product data
     */
    return $productData; 
    }   

}