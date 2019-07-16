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
 * @version     1.6
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
class Apptha_Marketplace_Helper_Vieworder extends Mage_Core_Helper_Abstract
{
    /**
     * Get order product data
     *
     * @param number $sellerId
     * @param number $orderId
     * @return array
     */
    public function getOrderProductIds($sellerId, $orderId)
    {
        /**
         * Load commission model
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('product_id');
        /**
         * Filter by seller id and order id
         */
        $products->addFieldToFilter('seller_id', $sellerId);
        $products->addFieldToFilter('order_id', $orderId);
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
    }
    /**
     * Get order product data
     *
     * @param number $sellerId
     * @param number $orderId
     * @return int
     */
    public  function getOrderProductQty($sellerId, $orderId){

        $products = Mage::getModel('marketplace/commission')->getCollection()
                         ->addFieldToSelect('order_id')
                         ->addFieldToFilter('seller_id', $sellerId)
                         ->addFieldToFilter('order_id', $orderId);
        $products->getSelect()
                ->columns('SUM(product_qty) as totalQty')
                ->group('order_id');
        $data = $products->getData();
        return $data[0]['totalQty'];
    }
    
    /**
     * Get cancel order product data
     *
     * @param number $sellerId
     * @param number $orderId
     * @return array
     */
    public function cancelOrderItemProductIds($sellerId, $orderId)
    {
        /**
         * Load commission data
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('product_id');
        /**
         * Filter by order status and customer id
         */
        $products->addFieldToFilter('order_status', 'canceled');
        $products->addFieldToFilter('customer_id', 0);
        /**
         * Filter by order id and seller id
         */
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('seller_id', $sellerId);
        
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
    }
    
    /**
     * Check order status for seller
     * 
     * @param array $orderItem
     * @return string $checkOrderStatus
     * 
     */
    public function checkOrderStatusForSeller($orderItem)
    {
        /**
         * Initilize order status
         */
        $checkOrderStatus = 0;
        /**
         * Checking for virtual product or not
         */
        if ($orderItem->getIsVirtual() == 1) {
            /**
             * Checking Refunded or not
             */
            if ($orderItem->getQtyRefunded() >= $orderItem->getQtyOrdered()) {
                $checkOrderStatus = -2;
                /**
                 * Checking invoice or not
                 */
            } elseif ($orderItem->getQtyInvoiced() < $orderItem->getQtyOrdered()) {
                $checkOrderStatus = 0;
            } else {
                $checkOrderStatus = 2;
            }
        } else {
            /**
             * Checking for refunded or not
             */
            if ($orderItem->getQtyRefunded() >= $orderItem->getQtyOrdered()) {
                $checkOrderStatus = -2;
                /**
                 * Checking for invoice or not
                 */
            } elseif ($orderItem->getQtyInvoiced() < $orderItem->getQtyOrdered() && $orderItem->getQtyShipped() < $orderItem->getQtyOrdered()) {
                $checkOrderStatus = 0;
                /**
                 * Checking for shipment or not
                 */
            } elseif ($orderItem->getQtyInvoiced() >= $orderItem->getQtyOrdered() && $orderItem->getQtyShipped() < $orderItem->getQtyOrdered()) {
                $checkOrderStatus = 1;
            } elseif ($orderItem->getQtyInvoiced() < $orderItem->getQtyOrdered() && $orderItem->getQtyShipped() >= $orderItem->getQtyOrdered()) {
                $checkOrderStatus = 3;
            } else {
                $checkOrderStatus = 2;
            }
        }
        /**
         * Return order status
         */
        return $checkOrderStatus;
    }
    /**
     * Get order status for seller
     */
    public function getOrderStatusForSeller($orderDetails, $checkOrderStatusArr)
    {
        /**
         * Initilize order status
         */
        $orderStatus = '';
        /**
         * Checking for order status array
         */
        
        if (in_array(3, $checkOrderStatusArr)) {
            $orderStatus = $this->__('Shipped');
            /**
             * Checing or refunded or not
             */
        } elseif (in_array(1, $checkOrderStatusArr)) {
            //$orderStatus =  $this->__('Processing');//commenting default aptha hard coded status code.
            $orderStatus = $orderDetails->getStatusLabel();
            /**
             * Checing or refunded or not
             */
        } elseif (Mage::helper('marketplace/vieworder')->checkRefundedOrNot($checkOrderStatusArr)) {
            $orderStatus = $this->__('Refunded');
            /**
             * checking or completed or not
             */
        } elseif (in_array(2, $checkOrderStatusArr) && !in_array(1, $checkOrderStatusArr) && !in_array(0, $checkOrderStatusArr)) {
            $orderStatus = $this->__('Completed');
        } else {
            /**
             * Checking for pending or not
             */
            $orderStatus     = $this->__('Pending');
            $orderPrdouctIds = Mage::helper('marketplace/vieworder')->getOrderProductIds(Mage::getSingleton('customer/session')->getId(), $orderDetails->getId());
            /**
             * prepare for items
             */
            foreach ($orderDetails->getAllItems() as $item) {
                /**
                 * Assign product id
                 */
                
                $itemProductId = $item->getProductId();
                $orderItem     = $item;
                if (in_array($itemProductId, $orderPrdouctIds)) {
                    if ($orderItem->getQtyShipped() >= 1 || $orderItem->getQtyInvoiced() >= 1) {
                        /**
                         * Set order status processing
                         */
                        //$orderStatus =  $this->__('Processing');//commenting default aptha hard coded status code.
                        $orderStatus = $orderDetails->getStatusLabel();
                        
                    }
                    break;
                }
            }
            
        }
        /**
         * Return order status
         */
        return $orderStatus;
    }
    
    /**
     * Check refunded or not
     * 
     * @param $checkOrderStatusArr
     * @return string
     */
    public function checkRefundedOrNot($checkOrderStatusArr)
    {
        /**
         * Initilize status
         */
        $status = 0;
        /**
         * checking for status
         */
        if (in_array(-2, $checkOrderStatusArr) && !in_array(2, $checkOrderStatusArr) && !in_array(1, $checkOrderStatusArr) && !in_array(0, $checkOrderStatusArr)) {
            $status = 1;
        }
        /**
         * Return status
         */
        return $status;
    }
    /**
     * Get order status
     * 
     * @param number $orderId
     * @param number $productId
     * @return string
     */
    public function getOrderStatus($orderId, $productId)
    {
        /**
         * Load commission model
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('order_status');
        /**
         * Filter model by order id and product id
         */
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $productId);
        /**
         * Return order status
         */
        return $products->getFirstItem()->getOrderStatus();
    }
    
    /**
     * Get seller shipping products by order id
     *
     * @param number $getOrderId
     * @param number $getSellerId
     */
    public function getShippingProductDetails($getOrderId, $getSellerId)
    {
        /**
         * Load commission model
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('product_id');
        /**
         * Filter by seller id and order id
         */
        $products->addFieldToFilter('seller_id', $getSellerId);
        $products->addFieldToFilter('order_id', $getOrderId);
        
        /**
         * Get product ids
         */
        $productIds = array_unique($products->getColumnValues('product_id'));
        
        /**
         * Initilize type ids
         */
        $typeIds            = array(
            'simple',
            'configurable'
        );
        $productsCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect(array(
            'name'
        ))->addAttributeToFilter('entity_id', array(
            'in' => $productIds
        ))->addAttributeToFilter('type_id', array(
            'in' => $typeIds
        ));
        /**
         * Getting name array
         */
        $productNames       = array_unique($productsCollection->getColumnValues('name'));
        /**
         * Return product names
         */
        return $productNameString = implode(',', $productNames);
    }
    
    /**
     * Getting order status based on seller items for the particular order
     * 
     * @param array $item
     * @return string $status
     */
    public function getOrderStatusForSellerItemsBased($item)
    {
        /**
         * Initilize status
         */
        $status    = '';
        $orderItem = $item;
        /**
         * Checking for refunded or not
         */
        if ($orderItem->getQtyRefunded() >= $orderItem->getQtyOrdered()) {
            $status = $this->__('refunded');
            /**
             * Checking for completed or not
             */
        } elseif ($orderItem->getIsVirtual() == 1 && $orderItem->getQtyInvoiced() >= $orderItem->getQtyOrdered()) {
            $status = $this->__('completed');
            /**
             * Checking for completed or not
             */
        } elseif ($orderItem->getIsVirtual() != 1 && $orderItem->getQtyInvoiced() >= $orderItem->getQtyOrdered() && $orderItem->getQtyShipped() >= $orderItem->getQtyOrdered()) {
            $status = $this->__('completed');
            /**
             * Checking for processing or not
             */
        } elseif ($orderItem->getIsVirtual() != 1 && $orderItem->getQtyInvoiced() >= $orderItem->getQtyOrdered() || $orderItem->getIsVirtual() != 1 && $orderItem->getQtyShipped() >= $orderItem->getQtyOrdered()) {
            //$status = $this->__('processing');//commenting default aptha code to get dynamic status name
            $status = $orderItem->getStatusLabel();
        } else {
            /**
             * Checking for pending or not
             */
            $status = $this->__('pending');
        }
        /**
         * Return status
         */
        return $status;
    }
    public function confirmOrderItem($sellerId, $orderId, $produtId)
    {
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('is_seller_confirmation','id','is_buyer_confirmation','order_status'));
        $products->addFieldToFilter('seller_id', $sellerId);
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $produtId);
        return $products;
        
    }
    
    public function sellerConfirmation($produtId, $orderId)
    {
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('is_seller_confirmation','id','is_buyer_confirmation','order_status'));
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $produtId);
        return $products->getFirstItem();
        
    }
    
    public function confirm($orderId, $produtId)
    {
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');
        $products->addFieldToFilter('order_id', $orderId);
        $products->addFieldToFilter('product_id', $produtId);
        
        
        $collectionId    = $products->getFirstItem()->getId();
        $orderStatusFlag = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/order_manage');
        
        if (!empty($collectionId) && $orderStatusFlag == 1) {
            $data            = array(
                'is_buyer_confirmation' => 'yes'
            );
            $commissionModel = Mage::getModel('marketplace/commission')->load($collectionId)->addData($data);
            $commissionModel->setId($collectionId)->save();
        }
    }
    
    public function seller_and_buyer($produtId, $orderId)
    {
        $products = Mage::getModel('marketplace/commission')->getCollection()->addFieldToSelect(array('id','is_buyer_confirmation','is_seller_confirmation'))->addFieldToFilter('order_id', $orderId)->addFieldToFilter('product_id', $produtId);
        return $products->getFirstItem();
    }

	/**
	 * @param $orderId
	 * @return int
	 * @author Hassan Ali Shahzad
	 * This function will receive orderId and return 1 if all items processed
	 * (accepted from buyer and all items accepted from seller except one)
	 * else return 0 if more then one items remaining from seller confirmations
     */
	public function isThisLastItemToAcceptFromSeller($orderId){
		$records = Mage::getModel('marketplace/commission')->getCollection()
						->addFieldToSelect('*')
						->addFieldToFilter('order_id',$orderId)
						->addFieldToFilter('is_seller_confirmation', 'No')
						->addFieldToFilter('item_order_status', array('neq'=>'canceled'))
						;
		if($records->getSize()>1) return 0;
		if($records->getSize()==1) { // for last item if buyer confirmerd then show generate invoice message and return 1
			foreach($records as $record)
			{
				if($record->getIsBuyerConfirmation() == "No")return 0;
				elseif($record->getIsBuyerConfirmation() == "Yes")return 1;
			}

		}
		return ($records->getSize()==1)?1:0;
	}

	public function _settingStore($sellerId){
		$products = Mage::getModel('marketplace/sellerprofile')->getCollection()
        ->addFieldToSelect('*')
        ->addFieldToFilter('seller_id',$sellerId);
        return $products->getFirstItem();
        
    }
    
    public function setOrAddOptionAttribute($product, $arg_attribute, $arg_value)
    {
        
        $attribute_model         = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_code          = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute               = $attribute_model->load($attribute_code);
        $attribute_options_model->setAttribute($attribute);
        $options      = $attribute_options_model->getAllOptions(false);
        // determine if this option exists
        $value_exists = false;
        foreach ($options as $option) {
            if ($option['label'] == $arg_value) {
                $value_exists = true;
                break;
            }
        }
        // if this option does not exist, add it.
        if (!$value_exists) {
            
            $attribute->setData('option', array(
                
                'value' => array(
                    
                    'option' => array(
                        $arg_value,
                        $arg_value
                    )
                    
                )
                
            ));
            
            $attribute->save();
        }
        
        $product->setData($arg_attribute, $arg_value);
    }
    
    public function notConfirmedOrderProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_status', 'order_id', 'is_buyer_confirmation', 'item_order_status', 'product_id'));
        
        $products->addFieldToFilter('order_status', array(
            'neq' => 'canceled'
        ));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_buyer_confirmation', 'No');
        $products->addFieldToFilter('item_order_status', array(
            'neq' => 'canceled'
        ));
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function notConfirmedSellerProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_status', 'order_id', 'is_seller_confirmation', 'item_order_status', 'product_id'));
        
        $products->addFieldToFilter('order_status', array(
            'neq' => 'canceled'
        ));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_seller_confirmation', 'No');
        $products->addFieldToFilter('item_order_status', array(
            'neq' => 'canceled'
        ));
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function rejectedOrderProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_id', 'order_status', 'is_buyer_confirmation', 'product_id'));
        
        $products->addFieldToFilter('order_status', array(
            'neq' => 'canceled'
        ));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_buyer_confirmation', 'Rejected');
        
        /**
         * Return product ids
         */
        
        
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function rejectedOrderProduct($getOrderId)
    {
        
        /**
         * Load commission data
         */
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_id', 'item_order_status', 'is_buyer_confirmation', 'product_id'));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter(array(
            'item_order_status',
            'is_buyer_confirmation'
        ), array(
            array(
                'eq' => 'canceled'
            ),
            array(
                array(
                    'eq' => 'Yes'
                ),
                array(
                    'eq' => 'Rejected'
                )
            )
        ));
        
        /**
         * Return product ids
         */
        
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function rejectedOrderProductSeller($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_id', 'item_order_status', 'is_seller_confirmation', 'product_id'));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter(array(
            'item_order_status',
            'is_seller_confirmation'
        ), array(
            array(
                'eq' => 'canceled'
            ),
            array(
                array(
                    'eq' => 'Yes'
                ),
                array(
                    'eq' => 'Rejected'
                )
            )
        ));
        
        /**
         * Return product ids
         */
        
        
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    // is seller confirmation
    public function notConfirmedSellerOrderProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        $sellerConfirmation = array(
            "Yes",
            "Rejected"
        );
        $products           = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_status', 'order_id', 'is_seller_confirmation', 'product_id'));
        
        $products->addFieldToFilter('order_status', array(
            'neq' => 'canceled'
        ));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_seller_confirmation', array(
            'in' => $sellerConfirmation
        ));
        
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function confirmedSellerOrderProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        $sellerConfirmation = array(
            "Yes",
            "Rejected"
        );
        $products           = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_status', 'order_id', 'is_seller_confirmation', 'product_id'));
        
        $products->addFieldToFilter('order_status', array(
            'neq' => 'canceled'
        ));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_seller_confirmation', 'Yes');
        
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function rejectedSellerOrderProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        $sellerConfirmation = array(
            "Yes",
            "Rejected"
        );
        $products           = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_status', 'order_id', 'is_seller_confirmation', 'product_id'));
        
        $products->addFieldToFilter('order_status', array(
            'neq' => 'canceled'
        ));
        $products->addFieldToFilter('order_id', $getOrderId);
        $products->addFieldToFilter('is_seller_confirmation', 'Rejected');
        
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function buyerCancel($sellerId, $orderId, $itemProductId)
    {
        $orderDetails = Mage::getModel('sales/order')->load($orderId);
        foreach ($orderDetails->getAllVisibleItems() as $item) {
            if ($item->getProductId() == $itemProductId) {
                if ($item->getStatus() == "Canceled"):
                    return true;
                else:
                    return false;
                endif;
                
            }
        }
    }
    
    public function allRejectedOrderProductIds($getSellerId, $getOrderId)
    {
        
        /**
         * Load commission data
         */
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('item_order_status', 'is_seller_confirmation', 'order_id', 'is_buyer_confirmation', 'product_id'));
        $products->addFieldToFilter('order_id', $getOrderId)->addFieldToFilter(array(
            'item_order_status',
            'is_seller_confirmation',
            'is_buyer_confirmation'
        ), array(
            array(
                array(
                    'eq' => 'canceled'
                ),
                array(
                    'eq' => 'refunded'
                ),
                array(
                    'eq' => 'failed_delivery'
                ),
                array(
                    'eq' => 'sale_returned'
                ),
                array(
                    'eq' => 'rejected_customer'
                ),
                array(
                    'eq' => 'rejected_seller'
                )
            ),
            array(
                'eq' => 'Rejected'
            ),
            array(
                'eq' => 'Rejected'
            )
        ));
        
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }
    
    public function allRejectedSellerProductIds($getOrderId)
    {
        
        /**
         * Load commission data
         */
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_id', 'is_seller_confirmation', 'is_buyer_confirmation', 'product_id'));
        $products->addFieldToFilter('order_id', $getOrderId)->addFieldToFilter("is_seller_confirmation", "Rejected")->addFieldToFilter("is_buyer_confirmation", "Yes");
        
        /**
         * Return product ids
         */
        return array_unique($products->getColumnValues('product_id'));
        
    }

    public function allProductsSellerRejected($getOrderId)
    {
        
        /**
         * Load commission data
         */
        
        $total_products = Mage::getModel('marketplace/commission')->getCollection()
        ->addFieldToFilter('order_id',$getOrderId);
        $total_products_total = count($total_products);
        
        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect(array('order_id', 'is_seller_confirmation', 'is_buyer_confirmation', 'product_id'));
        $products->addFieldToFilter('order_id', $getOrderId)->addFieldToFilter(array(
            'item_order_status',
            'is_seller_confirmation'
        ), array(
            array(
                array(
                    'eq' => 'canceled'
                ),
                array(
                    'eq' => 'rejected_customer'
                ),
                array(
                    'eq' => 'rejected_seller'
                )
            ),
            array(
                'eq' => 'Rejected'
            )
        ));

        $products_total = count($products);

        $total = $total_products_total-$products_total;
        /**
         * Return product ids
         */
        if($total == 0){
        return array_unique($products->getColumnValues('product_id'));
        }
        else{
            return array();
        }
        
    }
    
    public function cancelOrderItemEmailOld($rejected_items)
    {
        
        $itemCount      = 0;
        $orderEmailData = array();
        foreach ($rejected_items as $item) {
            $item              = Mage::getModel("marketplace/commission")->load($item);
            $order             = Mage::getModel('sales/order')->load($item->getOrderId());
            $getProductIdValue = $item->getProductId();
            
            //getting configurable product
            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
            $product   = Mage::getModel('catalog/product')->load($parentIds[0]);
            //getting seller id
            $sellerId  = $product->getSellerId();

            
            
            if ($sellerId) {
                
                
                $orderPrice = $item->getProductAmt();
                
                $orderEmailData[$itemCount]['seller_id']          = $sellerId;
                $orderEmailData[$itemCount]['product_id']         = $getProductIdValue;
                $orderEmailData[$itemCount]['product_amt']        = number_format($orderPrice, 2);
                $orderEmailData[$itemCount]['increment_id']       = $order->getIncrementId();
                $orderEmailData[$itemCount]['customer_email']     = $order->getCustomerEmail();
                $orderEmailData[$itemCount]['customer_firstname'] = $order->getCustomerFirstname();
                $itemCount                                        = $itemCount + 1;
            }
            
        }
        $this->sendCancelOrderEmail($orderEmailData);
    }

    public function cancelOrderItemEmail($rejected_items)
    {
        
        if(Mage::app()->getRequest()->getParam('order_id')){
          $order_id = Mage::app()->getRequest()->getParam('order_id');
        }
        elseif(Mage::app()->getRequest()->getParam('id')){
          $order_id = Mage::app()->getRequest()->getParam('id');  
        }
        
        $order    = Mage::getModel('sales/order')->load($order_id);
        $items    = $order->getAllItems();
        $itemCount = 0;

        foreach($items as $item){
          //checking if that item product id is in rejected_item array
          if(in_array($item->getProductId(), $rejected_items)){
            
            $getProductId = $item->getProductId();
            $products = Mage::getModel('catalog/product')->load($getProductId);

            //getting configurable product
            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
            $product   = Mage::getModel('catalog/product')->load($parentIds[0]);
            $productQty = $item->getQtyOrdered();
            
            $sellerId = $product->getSellerId();
            $productType = $product->getTypeID();
            $productName = $product->getName();

            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');

            /**
                 * Getting seller commission percent
            */
            $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
            if ($product->getSpecialPrice()) { 
                $orderPrice = $product->getSpecialPrice() * $item->getQtyOrdered();
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
            }else{
                
                $orderPrice = $product->getPrice() * $item->getQtyOrdered(); 
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
           }
                     
            //sending email of order data
            $orderEmailData [$itemCount] ['seller_id'] = $sellerId;
            $orderEmailData [$itemCount] ['product_qty'] = $productQty;
            $orderEmailData [$itemCount] ['product_id'] = $getProductId;
            $orderEmailData [$itemCount] ['product_amt'] = $orderPrice;
            $orderEmailData [$itemCount] ['product_name'] = $productName;
            $orderEmailData [$itemCount] ['commission_fee'] = $commissionFee;
            $orderEmailData [$itemCount] ['seller_amount'] = $sellerAmount;
            $orderEmailData [$itemCount] ['increment_id'] = $order->getIncrementId();
            $orderEmailData [$itemCount] ['customer_firstname'] = $order->getCustomerFirstname();
            $orderEmailData [$itemCount] ['customer_email'] = $order->getCustomerEmail();
            $orderEmailData [$itemCount] ['product_id_simple'] = $getProductId;
            $orderEmailData [$itemCount] ['itemCount'] = $itemCount;
            $itemCount = $itemCount + 1;

          }
        }
        //send email only when all item(s) in order canceled by seller(s)
        //prepare data for jsonld @RT
        //additional check added to send email only when order state is canceled after seller reject item(s)
        if (Mage::helper('progos_infotrust')->isEnableSellerCancelEmail()
            && ($order->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED
        || $order->getStatus() == Progos_OrderStatuses_Helper_Data::STATUS_CANCELED_AUTOMATIC)) {
            //if enabled logs block
            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                Mage::log('OrderId: '. $order->getIncrementId(), null, 'jsonld.log');
                Mage::log('inside cancelOrderItemEmail after seller cancel', null, 'jsonld.log');
            }
            //prepare json ld comission data for each seller
            $jsonld = Mage::helper('progos_infotrust')->getSellerCancelEmailJsonld($order, $orderEmailData);
            //send email to kustomer hook
            Mage::helper('progos_infotrust')->zendSend($jsonld, $order, 'Order Cancel Notification #'. $order->getIncrementId(), 'Seller item cancel for order #'. $order->getIncrementId());
            //if enabled logs block
            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                Mage::log('after cancelOrderItemEmail after seller cancel', null, 'jsonld.log');
            }
        }

        $this->sendCancelOrderEmail($orderEmailData);
    }
    
    //sending cancel order email
    
    public function failedOrderItemEmail($order_id)
    {
        
        $order_id = Mage::app()->getRequest()->getParam('order_id');
        $order    = Mage::getModel('sales/order')->load($order_id);
        $items    = $order->getAllItems();
        $itemCount = 0;
        
        foreach($items as $item){
            
            $getProductId = $item->getProductId();
            $products = Mage::getModel('catalog/product')->load($getProductId);
            $productTypeSimple = $products->getTypeID();
            
            //getting configurable product
            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
            $product   = Mage::getModel('catalog/product')->load($parentIds[0]);
            $productQty = $item->getQtyOrdered();
            
            $sellerId = $product->getSellerId();
            $productType = $product->getTypeID();
            $productName = $product->getName();

            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');

            /**
                 * Getting seller commission percent
            */
            $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
            if ($product->getSpecialPrice()) { 
                $orderPrice = $product->getSpecialPrice() * $item->getQtyOrdered();
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
            }else{
                
                $orderPrice = $product->getPrice() * $item->getQtyOrdered(); 
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
           }
                     
            //sending email of order data
           if($productTypeSimple == "simple"){
            $orderEmailData [$itemCount] ['seller_id'] = $sellerId;
            $orderEmailData [$itemCount] ['product_qty'] = $productQty;
            $orderEmailData [$itemCount] ['product_id'] = $getProductId;
            $orderEmailData [$itemCount] ['product_amt'] = $orderPrice;
            $orderEmailData [$itemCount] ['product_name'] = $productName;
            $orderEmailData [$itemCount] ['commission_fee'] = $commissionFee;
            $orderEmailData [$itemCount] ['seller_amount'] = $sellerAmount;
            $orderEmailData [$itemCount] ['increment_id'] = $order->getIncrementId();
            $orderEmailData [$itemCount] ['customer_firstname'] = $order->getCustomerFirstname();
            $orderEmailData [$itemCount] ['customer_email'] = $order->getCustomerEmail();
            $orderEmailData [$itemCount] ['product_id_simple'] = $getProductId;
            $orderEmailData [$itemCount] ['itemCount'] = $itemCount;
            $itemCount = $itemCount + 1;

          }
            
        }
        $this->sendCancelOrderEmail($orderEmailData);
    }
    
    public function sendCancelOrderEmail($orderEmailData)
    {
        $sellerIds = array();
        $displayProductCommission = Mage::helper('marketplace')->__('Seller Commission Fee');
        $displaySellerAmount = Mage::helper('marketplace')->__('Seller Amount');
        $displayProductName = Mage::helper('marketplace')->__('Product Details');
        $displayProductQty = Mage::helper('marketplace')->__('Product QTY');
        $displayProductAmt = Mage::helper('marketplace')->__('Product Amount');
        $displayProductStatus = Mage::helper('marketplace')->__('Product Status');

        foreach ($orderEmailData as $data) {
            /**
             * Check the seller id is not in the array of whole seller id
             * if so add the seller id in seller ids array
             */
            if (!in_array($data['seller_id'], $sellerIdsVal)) {
                $sellerIdsVal[] = $data['seller_id'];
            }
        }
        foreach ($sellerIdsVal as $key => $id) {
            $totalProductAmt    = $totalCommissionFee = $totalSellerAmt = 0;
            $totalProductAmt = $totalCommissionFee = $totalSellerAmt = 0;
            $productDetails =
                '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
            $productDetails .= '<thead><tr>';
            $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductName
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductQty
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductAmt . '</th>';
            $productDetails .= '<th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayProductCommission
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displaySellerAmount . '</th></tr></thead>';
            $productDetails .= '<tbody bgcolor="#F6F6F6">';
            
            $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
            foreach ($orderEmailData as $data) {
                if ($id == $data['seller_id']) {
                    $sellerId = $data ['seller_id'];
                    $incrementId = $data ['increment_id'];
                    $groupId = Mage::helper('marketplace')->getGroupId();
                    $productId = $data ['product_id'];
                    $productName = $data['product_name'];
                    $productAmt = $data ['product_amt'];
                    $productsNew = Mage::getModel('catalog/product')->load($productId);
                    $productGroupId = $productsNew->getGroupId();

                    if ($productsNew->getSupplierSku() != "") {
                            $product_sku = $productsNew->getSupplierSku();
                        } else {
                            $product_sku = $productsNew->getSku();
                        }
                        $product_color = $productsNew->getAttributeText('color');
                        $product_size = $productsNew->getAttributeText('size');
                    

                    if ($product_sku) {
                        $product_sku = "<br/>SKU:&nbsp;" . $product_sku;
                    } else {
                        $product_sku = "";
                    }

                    if ($product_size) {
                        $product_size = "<br/>Size:&nbsp;" . $product_size;
                    } else {
                        $product_size = "";
                    }

                    if ($product_color) {
                        $product_color = "<br/>Color:&nbsp;" . $product_color;
                    } else {
                        $product_color = "";
                    }
                    $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getBaseCurrencyCode())->getSymbol();
                    $productOptions = $product_sku . $product_size . $product_color;

                    $productDetails .= '<tr>';
                    $productDetails .='<td align="left" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $productName . '<br/>' . $productOptions
                        . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . round($data ['product_qty']) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $currencySymbol . round($productAmt, 2)
                        . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $currencySymbol . round($data ['commission_fee'], 2) . '</td>';
                     $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $currencySymbol . round($data ['seller_amount'], 2) . '</td>';
                   
                    $totalProductAmt   = $totalProductAmt + $data['product_amt'];
                    $incrementId       = $data['increment_id'];
                    $customerEmail     = $data['customer_email'];
                    $customerFirstname = $data['customer_firstname'];
                    $productDetails .= '</tr>';
                }
            }
            /**
             * Confirm the group id is equal to the product group id
             * if so then get the store configured values like
             * template id
             * admin email id
             * to mail id
             * to name
             */
            if ($groupId == $productGroupId) {
                $templateIdValue   = ( int ) Mage::getStoreConfig('marketplace/admin_approval_seller_registration/cancel_notification_template_selection');
                $adminEmailIdValue = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
                $toMailId          = Mage::getStoreConfig("trans_email/ident_$adminEmailIdValue/email");
                $toName            = Mage::getStoreConfig("trans_email/ident_$adminEmailIdValue/name");
                /**
                 * Check template id has been set
                 * if set then load that particular template
                 * if not load the default template of admin approval seller registration cancel notification template
                 */
                if ($templateIdValue) {
                    $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateIdValue);
                } else {
                    $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_admin_approval_seller_registration_cancel_notification_template_selection');
                }
                $sellerStore     = Mage::app()->getStore()->getName();
                $customer        = Mage::helper('marketplace/marketplace')->loadCustomerData($sellerId);
                $sellerEmail     = $customer->getEmail();
                $sellerName      = $customer->getName();
                $recipient       = $toMailId;
                $recipientSeller = $sellerEmail;
                $emailTemplate->setSenderName($toName);
                $emailTemplate->setSenderEmail($toMailId);
                /**
                 * Dynamically replacing the email template variables with the retrieved values
                 */
                $emailTemplateVariables = (array(
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
                $emailTemplate->getProcessedTemplate($emailTemplateVariables);
                /**
                 * Send email using dyanamically replaced template
                 */
                $emailTemplate->send($recipient, $toName, $emailTemplateVariables);
                /**
                 * Sending email to seller
                 */
                $seller_new   = Mage::getModel('marketplace/sellerprofile')->collectprofile($data['seller_id']);
                $seller_model = Mage::getModel("marketplace/sellerprofile")->load($seller_new->getId());
                
                if ($seller_model->getCancelEmailNotifications() == 1) {

                    $emailTemplateVariables = (array(
                        'ownername' => $sellerName,
                        'productdetails' => $productDetails,
                        'order_id' => $incrementId,
                        'seller_store' => $sellerStore,
                        'customer_email' => $customerEmail,
                        'customer_firstname' => $customerFirstname
                    ));
                    $emailTemplate->send($recipientSeller, $sellerName, $emailTemplateVariables);
                }
            }
        }
        
    }

    public function getProductConfirmRejected($getOrderId)
    {
        $counter = 0;
        $i = 0;
        $orderDetails = Mage::getModel('marketplace/commission')->getCollection();
        $orderDetails->addFieldToFilter('order_id',$getOrderId);
        
        $rejectedProducts = Mage::helper('marketplace/vieworder')->rejectedOrderProduct($getOrderId);
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

    public function getSellerRejectedData($sku,$sellerId,$orderId,$act = '' ){

        $store_name = Mage::getModel('marketplace/sellerprofile')->getCollection();
        $store_name->addFieldToSelect(array('store_title'))
                   ->addFieldToFilter('seller_id', $sellerId)->getFirstItem();
        foreach($store_name as $store){
            $store_title = $store->getStoreTitle();
        }
        
        $displayOrderId= Mage::helper('marketplace')->__('Order #');
        $displaySku = Mage::helper('marketplace')->__('SKU');
        $displaySupplierName = Mage::helper('marketplace')->__('Supplier Name');

        $productDetails =
                '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
            $productDetails .= '<thead><tr>';
            $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displayOrderId
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displaySku
                . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">'
                . $displaySupplierName . '</th></tr></thead>';
            $productDetails .= '<tbody bgcolor="#F6F6F6">';
            $productDetails .= '<tr>';
            $productDetails .='<td align="left" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $orderId . '<br/>'
                        . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $sku . '</td>';
            $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">'
                        . $store_title . '</td></tr>';

            $templateIdValue   = ( int ) Mage::getStoreConfig('marketplace/admin_approval_seller_registration/order_reject_notification_template_selection');
            
            //email sender details
            $adminEmailIdValue = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
            $toMailId          = Mage::getStoreConfig("trans_email/ident_$adminEmailIdValue/email");
            $toName            = Mage::getStoreConfig("trans_email/ident_$adminEmailIdValue/name");
            
            $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateIdValue);
    
            $sellerStore     = Mage::app()->getStore()->getName();
            $customer        = Mage::helper('marketplace/marketplace')->loadCustomerData($sellerId);
            if( $act == 'admin' ){
                $sellerEmail     = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/reject_notification_email_admin');
            }else{
                $sellerEmail     = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/reject_notification_email');
            }
            $recipient       = $toMailId;
            $recipientSeller = $sellerEmail;
            $sellerName      = "Supplier Support";
            $emailTemplate->setSenderName($toName);
            $emailTemplate->setSenderEmail($toMailId);
            
            /**
            * Dynamically replacing the email template variables with the retrieved values
            */
            $emailTemplateVariables = (array(
                'productdetails' => $productDetails,
                'orderId'  => $orderId
                ));
                $emailTemplate->setDesignConfig(array(
                    'area' => 'frontend'
                ));
            
            $emailTemplate->getProcessedTemplate($emailTemplateVariables);
            /**
             * Send email using dyanamically replaced template
            */
            
            $emailTemplate->send($recipient, $toName, $emailTemplateVariables);
            
            /**
            * Sending email to suppliers@elabelz.com
            */
            

            $emailTemplateVariables = (array(
                'productdetails' => $productDetails
                ));
                
            $emailTemplate->send($recipientSeller, $sellerName, $emailTemplateVariables);          
    }

    /**
     * @param $orderId
     * @return bool  return true if atleast one item left for buyer confirmation
     *
     */
    public function isAllItemsConfirmedFromBuyer($orderId){
        $orderItemsSellerStatus = Mage::getModel('marketplace/commission')
                                        ->getCollection()
                                        ->addFieldToFilter("order_id", $orderId)
                                        ->addFieldToFilter("is_buyer_confirmation", 'No');
        if($orderItemsSellerStatus->getSize()>0){
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * @param $orderId
     * @return bool  return true if atleast one item left for buyer confirmation
     *
     */
    public function isAllItemsConfirmedFromSeller($orderId){
        $orderItemsSellerStatus = Mage::getModel('marketplace/commission')
            ->getCollection()
            ->addFieldToFilter("order_id", $orderId)
            ->addFieldToFilter("is_seller_confirmation", 'No');
        if($orderItemsSellerStatus->getSize()>0){
            return false;
        }
        else {
            return true;
        }
    }

}