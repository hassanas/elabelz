<?php

require_once(__DIR__ . '/../app/Mage.php'); //Path to Magento

umask(0);

Mage::app();		


// Formulazing the order data 


$OrderNumber = "100000543";//Put your order Number here

$OrderNumber = "100000370";//Put your order Number here

 	
$order = Mage::getModel('sales/order')->load($OrderNumber, 'increment_id');
//$order->getAllVisibleItems();

$orderItems = $order->getAllItems();
    
$orderData = array();
$i = 0;
foreach($orderItems as $sItem) {

    if($sItem->getProductType() == "simple")
    {

           $orderData[$i]['product_type'] =  $sItem->getProductType();
           $orderData[$i]['order_id'] =  $sItem->getOrderId();
           $orderData[$i]['increment_id'] =  $order->getIncrementId();
           $orderData[$i]['order_status'] =  $order->getStatus();
           $orderData[$i]['order_state'] =  $order->getState();
           $orderData[$i]['order_grand_total'] =  $order->getGrandTotal();
           if($order->getCustomerId() == ""){$customerId = 0;}else{$customerId = $order->getCustomerId();}
           $orderData[$i]['customer_id'] =  $customerId;
           $orderData[$i]['product_id'] =  $sItem->getProductId();
           $orderData[$i]['item_id'] =  $sItem->getId();
           $orderData[$i]['item_name'] =  $sItem->getName();
           $orderData[$i]['item_sku'] =  $sItem->getSku();
           $orderData[$i]['item_price'] =  $sItem->getPrice();
           $orderData[$i]['parent_item_price'] =  $sItem->getParentItemId();
           
           
           $pItemId = $sItem->getParentItemId();
           $item = Mage::getModel('sales/order_item')->load("$pItemId");
           
           $nProduct = Mage::getModel('catalog/product')->load($item->getProductId());
           $nSku = $nProduct->getSku();
           
           $orderData[$i]['p_product_type'] =  $item->getProductType();
           $orderData[$i]['p_product_id'] =  $item->getProductId();
           $orderData[$i]['p_item_id'] =  $item->getId();
           $orderData[$i]['p_item_sku'] =  $nSku;
           $orderData[$i]['seller_id'] =  $nProduct->getSellerId();
           $orderData[$i]['p_item_price'] =  $item->getPrice();
           $orderData[$i]['qty_cancelled'] =  $item->getQtyCanceled();
           $qty = intval($item->getQtyOrdered());
                   
           $orderData[$i]['p_quantity'] = $qty;
           
           $i++;
        

        }
    }
echo "<pre>";
print_r($orderData);
//die;
foreach($orderData as $_order){
    
    $orderId = $_order['order_id'];
    $productId = $_order['product_id'];
    
        
        if($productId > 0){
        // Check if product Exists in the order. If not add it
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT * FROM `marketplace_commission` WHERE order_id='$orderId' and product_id = '$productId'";
        $commissionData = $connection->fetchAll($sql); 
        
        if($commissionData[0]['id']){
          
            if($_order['qty_cancelled'] > 0 && $_order['product_id'] == $commissionData[0]['product_id']){
                
                echo "Item #".$productId." Qunatity is decreased<br/>";
                
                $quantity = $_order['p_quantity']-$_order['qty_cancelled'];
                $productPrice = $_order['p_item_price'] * $quantity;
                $commissionFee = calculateCommission($_order['seller_id'],$productPrice);
                $sellerAmount = $productPrice - $commissionFee;
                
                //Updating existing record
                try {
                $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write'); 
                $commission_arr = array();
                $commission_arr['product_qty'] = $quantity;
                $commission_arr['product_amt'] = $productPrice;
                $commission_arr['commission_fee'] = $commissionFee;
                $commission_arr['seller_amount'] = $sellerAmount;
                $commission_arr['credited'] = 0;
                $commission_arr['order_total'] = $_order['order_grand_total'];
                if($quantity == 0){$commission_arr['item_order_status'] = 'canceled';} // Incase item is removed
                $where = array();
                $where [] = $write_connection ->quoteInto('order_id =?', $orderId);
                $where [] = $write_connection ->quoteInto('product_id =?', $productId);
                $write_connection->update('marketplace_commission', $commission_arr, $where);
                
                // Updating quantity in the product
                
                $_product = Mage::getModel('catalog/product')->load($productId);
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
                
                $newProduct->setStockData(array(
                       'use_config_manage_stock' => 0, //'Use config settings' checkbox
                       'manage_stock'=>1, //manage stock
                       'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
                       'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
                       'is_in_stock' => 1, //Stock Availability
                       'qty' => 999 //qty
                   )
                );
                }catch (Exception $e){
                
                    echo $e->getMessage(); 
                }
                
                
            }else if($commissionData[0]['product_qty'] < $_order['p_quantity']){
                
                echo "Item #".$productId." Quantity is increased<br/>";
                // update existing record
                
                $productPrice = $_order['p_item_price'] * $_order['p_quantity'];
                $commissionFee = calculateCommission($_order['seller_id'],$productPrice);
                $sellerAmount = $productPrice - $commissionFee;
    
                try {
                $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write'); 
                $commission_arr = array();
                $commission_arr['product_qty'] = $_order['p_quantity'];
                $commission_arr['product_amt'] = $productPrice;
                $commission_arr['commission_fee'] = $commissionFee;
                $commission_arr['seller_amount'] = $sellerAmount;
                $commission_arr['credited'] = 0;
                $commission_arr['order_total'] = $_order['order_grand_total'];
                $where = array();
                $where [] = $write_connection ->quoteInto('order_id =?', $orderId);
                $where [] = $write_connection ->quoteInto('product_id =?', $productId);
                $write_connection->update('marketplace_commission', $commission_arr, $where);
                }catch (Exception $e){
                
                    echo $e->getMessage(); 
                }
            
                
            }
            
        }else{
            
            echo "Item #".$productId." New product to add in commission table<br/>";
            // Adding to table incase the product is not added to the commission table
            try {
                $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write'); 
                $commission_arr = array();
                $commission_arr['seller_id'] = $_order['seller_id'];
                $commission_arr['product_id'] = $productId;
                $commission_arr['product_qty'] = $_order['p_quantity'];
                $commission_arr['product_amt'] = $productPrice;
                $commission_arr['order_id'] = $orderId;
                $commission_arr['increment_id'] = $_order['increment_id'];
                $commission_arr['commission_fee'] = $commissionFee;
                $commission_arr['seller_amount'] = $sellerAmount;
                $commission_arr['credited'] = 0;
                $commission_arr['order_total'] = $_order['order_grand_total'];
                $commission_arr['order_status'] = $_order['order_status'];
                $commission_arr['customer_id'] = $_order['customer_id'];
                $commission_arr['is_seller_confirmation'] = 'No';
                $commission_arr['is_buyer_confirmation'] = 'No';
                $commission_arr['status'] = 1;
                $commission_arr['created_at'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $commission_arr['item_order_status'] = $_order['order_status'];
               
                $write_connection->insert('marketplace_commission', $commission_arr);
                
                }catch (Exception $e){
                
                    echo $e->getMessage(); 
                }
        }
        
        // Updating Order Total for all products in commission Table after all operations performed
        echo "Item #".$productId."updating order total<br/>";
        try {
                $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write'); 
                $commission_arr = array();
                $commission_arr['order_total'] = $_order['order_grand_total'];
                $where = array();
                $where [] = $write_connection ->quoteInto('order_id =?', $orderId);
                $where [] = $write_connection ->quoteInto('product_id =?', $productId);
                $write_connection->update('marketplace_commission', $commission_arr, $where);
                }catch (Exception $e){
                
                    echo $e->getMessage(); 
                }
    }
}

function calculateCommission($seller_id,$product_price){
   
    $sellerData = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
              
    $commission = $product_price * ($sellerData['commission']/100);
    return $commission;
    
}
  
?>
        
       
