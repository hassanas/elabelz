<!DOCTYPE html>
<html>
<head>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
</head>
<body>
<?php
require_once(__DIR__ . '/../app/Mage.php'); //Path to Magento
umask(0);
Mage::app();	
//Fetching the Credit Limit days specified in the admin marketplace
$creditLimitDays = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/credit_limit');
//Fetching all the orders with state complete and status successfull delivery
$order_collection = Mage::getModel('sales/order')
                     ->getCollection()
                     ->addFieldToFilter('state', 'complete')
                     ->addFieldToFilter('status', 'successful_delivery');
                     
?>
<table>
<tr>
<tr>
<th colspan=6 style="text-align: center">Orders with state=complete and status=successful_delivery update log </th>
</tr>
   <tr>
    <th>Order#</th>
    <th>Previous Status</th>
    <th>Updated Status</th>
    <th>Seller Id</th>
    <th>Seller Name</th>
    <th>Commission-Credited</th>
    <th>Commission-Seller Amount</th>
    <th>Commission-Commission Fee</th>
    <th>Commission- Order Status</th>
    <th>Commission- Item Order Status</th>
  </tr>    
<?php
//// In this section all orders with successfull delivery status are updated in commission table
// respectively and credited to 1
if(count($order_collection)>1){
foreach($order_collection as  $orders){
  
    $credited = 0;
    $orderId = $orders->getId(); //Order Id of the order
    //if($orders->getId() == 'uu123'){
        
        //checking if the credit limit days is passed
        $start = strtotime($orders->getCreatedAt());
        $end = strtotime(now());
        $days_between = ceil(abs($end - $start) / 86400);
        
        if($days_between > $creditLimitDays)
        {
            $credited = 1;
            
            // Getting Seller Id from Commission Table Information
            $commissionData = Mage::getModel('marketplace/commission')
                    ->getCollection()
                   ->addFieldToFilter('order_id', array('eq' => $orderId));
                   
            $commissionTotal = 0;
            $SellerAmountTotal = 0;
            // Getting Seller Information
            foreach($commissionData as $commision){
                $commissionTotal += $commision->getCommissionFee();
                $sellerAmountTotal += $commision->getSellerAmount();
                
                if($commision->getSellerId() == ""){
                $seller_id = 0;
                }else{
                    $seller_id = $commision->getSellerId();
                }
               
            }
            
            $SellerData = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $seller_id, 'seller_id' );
            
            ?>
    
            <tr>
              <td><?php echo $orders->getIncrementId();?> </td>
              <td>Successfull Delivery</td>
              <td>Complete & Non Refundable</td>
              <td><?php echo $seller_id;?></td>
              <td><?php echo $SellerData->getStoreTitle();?></td>
              <td>1</td>
              <td><?php echo $sellerAmountTotal;?></td>
              <td><?php echo $commissionTotal;?></td>
              <td>complete</td>
              <td>complete</td>
            </tr>
            
            <?php 
               
        /*try {
                // Updating order status globally
                $orders->setData('state', "complete");
                $orders->setStatus("complete");
                $history = $orders->addStatusHistoryComment('Order was set to Complete by our automation tool.', false);
                $history->setIsCustomerNotified(false);
                $orders->save();

                //Updating order status and item order status in the Commission table
                $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write'); 
                $commission_arr = array();
                $commission_arr['order_status'] = 'complete';
                $commission_arr['item_order_status'] = 'complete';
                $commission_arr['credited'] = $credited;
                $where = $write_connection ->quoteInto('order_id =?', $orderId);
                $write_connection->update('marketplace_commission', $commission_arr, $where);
                
                
            } catch (Exception $e){
                echo $e->getMessage(); 
            }*/
        }        
        
  // }
}
}else{?>
            <tr> <td colspan="10">No records to update</td></tr> 
<?php }?>
</table>
 <br/>
 <br/>
 <br/><br/>
  
<table>
<tr>
<tr>
<th colspan=7 style="text-align: center">Orders with state=complete and status=successful_delivery_partially update log</th>
</tr>
   <tr>
    <th>Order#</th>
    <th>Previous Status</th>
    <th>Updated Status</th>
    <th>Product #</th>
    <th>Commission-Credited</th>
    <th>Commission- Order Status</th>
    <th>Commission- Item Order Status</th>
  </tr>    

<?php
//
//// For order status with partial successfull delivery
// In this section items with completed status are credited to 1 in the commission table

    $order_collection = Mage::getModel('sales/order')
                     ->getCollection()
                     ->addFieldToFilter('state', 'complete')
                     ->addFieldToFilter('status', 'successful_delivery_partially');
                     
    if(count($order_collection)>1){
    foreach($order_collection as  $orders){

         $orderId = $orders->getId(); //Order Id of the order
         $credited = 0;

            //checking if the credit limit days is passed
            $start = strtotime($orders->getCreatedAt());
            $end = strtotime(now());
            $days_between = ceil(abs($end - $start) / 86400);

            if($days_between > $creditLimitDays)
            {
                $credited = 1;
                // Updating the Statuses

                $commissionData = Mage::getModel('marketplace/commission')
                   ->getCollection()
                   ->addFieldToFilter('order_status', 'successful_delivery_partially')
                   ->addFieldToFilter('order_id', array('eq' => $orderId));

                foreach($commissionData as $_commission){
                    if($_commission->getItemOrderStatus() == 'successful_delivery'){?>
                       
                        <tr>
                          <td><?php echo $orders->getIncrementId();?> </td>
                          <td>successful_delivery_partially</td>
                          <td>Complete & Non Refundable</td>
                          <td><?php echo $_commission->getProductId(); ?></td>
                          <td>1</td>
                          <td>complete</td>
                          <td>complete</td>
                        </tr>

              <?php         
                        // Updating order status globally
                       /* $orders->setData('state', "complete");
                        $orders->setStatus("complete");
                        $history = $orders->addStatusHistoryComment('Order was set to Complete by our automation tool.', false);
                        $history->setIsCustomerNotified(false);
                        $orders->save();
                        
                        
                        
                        try {
                            //Updating order status and item order status in the Commission table
                            $write_connection = Mage::getSingleton('core/resource')->getConnection('core_write'); 
                            $commission_arr = array();
                            $commission_arr['order_status'] = 'complete';
                            $commission_arr['item_order_status'] = 'complete';
                            $commission_arr['credited'] = 1;
                            $where = array();
                            $where [] = $write_connection ->quoteInto('order_id =?', $orderId);
                            $where [] = $write_connection ->quoteInto('product_id =?', $_commission->getProductId());
                            $write_connection->update('marketplace_commission', $commission_arr, $where);
                            
                           

                        } catch (Exception $e){
                            echo $e->getMessage(); 
                        }*/
                    }else{ ?>
                        <tr> <td colspan="7">No records to update</td></tr>
                        
                   <?php }

                }

            }        
        }
    }else{ ?>
        <tr> <td colspan="7">No records to update</td></tr> 
        
    <?php } 
?>
</table>
    
</body>
</html>