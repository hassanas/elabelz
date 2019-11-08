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

//Fetching all seller


    $creditLimitDays = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/credit_limit');    
// order collection  
    
    $order_collection_notcredited = Mage::getModel('sales/order')
                     ->getCollection()
                     ->addFieldToFilter('state', 'complete')
                     ->addFieldToFilter('status', 'successful_delivery');

        
        $uncredited_order_collection = array();
            foreach($order_collection_notcredited as $order){
            	$start = strtotime($order->getCreatedAt());
		        $end = strtotime(now());
		        $days_between = ceil(abs($end - $start) / 86400);
        
		        if($days_between > $creditLimitDays)
		        {
                   $noncredited_order_collection[] = $order->getId();	
                }
            }

    $order_collection_credited = Mage::getModel('sales/order')
                     ->getCollection()
                     ->addFieldToFilter('state', 'complete')
                     ->addFieldToFilter('status', 'complete');

            
            $credited_order_collection = array();
            foreach($order_collection_credited as $order){
                $credited_order_collection[] = $order->getId();	
            }

        
        $order_collection_partial = Mage::getModel('sales/order')
                     ->getCollection()
                     ->addFieldToFilter('state', 'complete')
                     ->addFieldToFilter('status', 'successful_delivery_partially');

        $credited_order_collection_partial = array();
            foreach($credited_order_collection_partial as $order){
                $credited_order_collection[] = $order->getId();	
            }


        $noncredited_order_collection_partial = array();
            foreach($order_collection_partial as $order){
            	$start = strtotime($order->getCreatedAt());
		        $end = strtotime(now());
		        $days_between = ceil(abs($end - $start) / 86400);
        
		        if($days_between > $creditLimitDays)
		        {
                   $noncredited_order_collection_partial[] = $order->getId();	
                }
            }


    ?>


<table>
<tr>
<tr>
<th colspan=6 style="text-align: center">Orders with state=complete and status=successful_delivery update log </th>
</tr>
  <tr>
    <th>Seller Id</th>
    <th>Store Tittle</th>
    <th>Total Sale</th>
    <th>Credited Amount</th>
    <th>New Credit Amount </th>
    <th>Total</th>
 </tr>
    <?php
    
    $seller_collection = Mage::getModel('marketplace/sellerprofile')
                        ->getCollection();

            foreach($seller_collection as $seller){
                if(!empty($credited_order_collection)){
            	//getting credited amount of seller
               	       $_collection_credited = Mage::getModel ( 'marketplace/commission' )->getCollection ()
							        ->addFieldToSelect ( 'seller_amount','order_id' )
							        ->addFieldToFilter ( 'seller_id', $seller->getSellerId() )
							        ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'credited', array('eq'=> 1) )
							        ->addFieldToFilter ( 'status', 1 )
							        ->addFieldToFilter ( 'order_status',  array('in' => array('complete')) ) /*Edited by Ali? As this value is not updated*/
							        ->addFieldToFilter ( 'item_order_status',  array('in' => array('complete')) )
							        ->addFieldToFilter ('order_id',array('in' => array($credited_order_collection)));
					    $_collection_credited->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' );
					    

					    foreach ( $_collection_credited as $amount ) {
					            $credited_seller_amount = $amount->getSellerAmount ();
					        }
					    }


                        if(!empty($credited_order_collection_partial)){
					    $_collection_credited_partial = Mage::getModel ( 'marketplace/commission' )->getCollection ()
							        ->addFieldToSelect ( 'seller_amount','order_id' )
							        ->addFieldToFilter ( 'seller_id', $seller->getSellerId() )
							        ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'credited', array('eq'=> 1) )
							        ->addFieldToFilter ( 'status', 1 )
							        ->addFieldToFilter ( 'order_status',  array('in' => array('complete')) ) /*Edited by Ali? As this value is not updated*/
							        ->addFieldToFilter ( 'item_order_status',  array('in' => array('complete')) )
							        ->addFieldToFilter ('order_id',array('in' => array($credited_order_collection_partial)));
					    $_collection_credited_partial->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' );
					    

					    foreach ( $_collection_credited_partial as $amount ) {
					            $credited_seller_amount_partial = $amount->getSellerAmount ();
					        }
					    }
					//getting non-credited amount of seller

                        if(!empty($noncredited_order_collection)){
                        $_collection_noncredited = Mage::getModel ( 'marketplace/commission' )->getCollection ()
							        ->addFieldToSelect ( 'seller_amount','order_id' )
							        ->addFieldToFilter ( 'seller_id', $seller->getSellerId() )
							        ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'credited', array('eq'=> 0) )
							        ->addFieldToFilter ( 'status', 1 )
							        ->addFieldToFilter ('order_id',array('in' => array($noncredited_order_collection)));

					   $_collection_noncredited->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' );
					  

					    foreach ( $_collection_noncredited as $amount ) {
					            $noncredited_seller_amount = $amount->getSellerAmount ();
					        }
					    }

					    if(!empty($noncredited_order_collection_partial)){ 

					     $_collection_noncredited_partial = Mage::getModel ( 'marketplace/commission' )->getCollection ()
							        ->addFieldToSelect ( 'seller_amount','order_id' )
							        ->addFieldToFilter ( 'seller_id', $seller->getSellerId() )
							        ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
							        ->addFieldToFilter ( 'credited', array('eq'=> 0) )
							        ->addFieldToFilter ( 'status', 1 )
							         ->addFieldToFilter ( 'order_status',  array('in' => array('successful_delivery')) ) /*Edited by Ali? As this value is not updated*/
							        ->addFieldToFilter ( 'item_order_status',  array('in' => array('complete')) )
							        ->addFieldToFilter ('order_id',array('in' => array($noncredited_order_collection_partial)));

					   $_collection_noncredited_partial->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' );
					  

					    foreach ( $_collection_noncredited_partial as $amount ) {
					            $noncredited_seller_amount_partial = $amount->getSellerAmount ();
					        } 

					    }


                        
                        $_collection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
							            ->addFieldToSelect ( 'seller_amount','order_id' )
							            ->addFieldToFilter ( 'seller_id', $seller->getSellerId() )
							            ->addFieldToFilter ( 'is_buyer_confirmation', 'Yes' )
							            ->addFieldToFilter ( 'is_seller_confirmation', 'Yes' )
							            ->addFieldToFilter ( 'status', 1 )
							            ->addFieldToFilter ( 'order_status',  array('in' => array('successful_delivery','shipped_from_elabelz','complete')) ) /*Edited by Ali? As this value is not updated*/ 
							            ->addFieldToFilter ( 'refund_request_customer', 0 )
							            ->addFieldToFilter ( 'refund_request_seller', 0 )
							            ->addFieldToFilter ( 'cancel_request_customer', 0 );
							        $_collection->getSelect ()->columns ( 'SUM(seller_amount) AS seller_amount' );

					    foreach ( $_collection as $amount ) {
							            $return = $amount->getSellerAmount ();
							}
			?>

			<tr>
              <td><?php echo $seller->getSellerId();?> </td>
              <td><?php echo $seller->getStoreTitle();?> </td>
              <td><?php echo $return; ?> </td>
              <td><?php echo $credited_seller_amount + $credited_seller_amount_partial ?></td>
              <td><?php echo $noncredited_seller_amount + $noncredited_seller_amount_partial ?></td>
              <?php $totalOrder = $credited_seller_amount + $noncredited_seller_amount + $credited_seller_amount_partial + $noncredited_seller_amount_partial ;  ?>
              <td><?php echo $totalOrder ?></td>
              
            </tr>

                    <?php


        
        }
                     

?>
 
 </tr>
 </table>

</body>