<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
$(function() {
	$("table tr td").click(function() {
		if ($(this).parent().attr("class") == "active") {
			$(this).parent().removeClass('active');
		} else {
			$(this).parent().addClass('active');
		}
	})
})
</script>
<style type="text/css">
table {
    border: 1px solid #333;
    border-collapse: collapse;
    font-family: verdana;
}
table tr th {
    background-color: #333;
    color: #CCC;
    font-weight: bold;
    font-size: 11px;
    padding: 5px;
    border-right: 1px solid #CCC;
}
table tr th:last-child {
    border-right: 1px solid #333;
}
table tr td {
    padding: 5px;
    border: 1px solid #CCC;
    cursor: default;
}
table tr:hover td {
	background: #f4f4f4;
}
.active {
	background: #E6E6E6;
}
table tr.shead td {
	background: #333;
    color: #f4f4f4;
    font-weight: bold;
    font-size: 11px;
}
table tr.shead td code {
	color: #333;
	font-family: courier;
	font-size: 12;
	background: #CCC;
	padding: 0 2px;
}
p.script_desc {
	padding: 8px;
	background: #f4f4f4;
	border: 1px solid #CCC;
	font-size: 12px;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
	font-family: verdana;
	line-height: 22px;
	margin-bottom: 50px;
}
p.script_desc code {
	color: #f4f4f4;
	font-family: courier;
	font-size: 12;
	background: #333;
	padding: 1px 3px 2px 3px;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
}

p.output {
	padding: 8px;
	background: #d9edf7;
	border: 1px solid #bce8f1;
	color: #31708f;
	font-size: 12px;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
	font-family: verdana;
	line-height: 22px;
	margin-top: 40px;
}
p.output code {
	color: #f4f4f4;
	font-family: courier;
	font-size: 12;
	background: #007100;
	padding: 1px 3px 2px 3px;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
}
</style>
<p class="script_desc">
	What this script does?<br>
	This script loop through all orders and bring up order with state <code>complete</code> and status <code>successful_delivery</code> order date older than <code>Credit option limit (Days)</code> and set the order status to <code>Completed Non Refundable</code>, order item status to <code>Completed Non Refundable</code> and credit to <code>true</code>
</p>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <th>Increment ID</th>
        <th>Amount</th>
        <th>Quantity (Price x Quantity)</th>
        <th>Commission Fee</th>
        <th>Commission %</th>
        <th>Seller Amount</th>
        <th>Order Total</th>
        <th>Order Date</th>
        <th>Result</th>
    </tr>
<!--    <tr class="shead">
        <td colspan="11" align="center">Order item status <code>( = Completed Non Refundable)</code> and amount credit <code>( = true)</code> will be changed for following order items</td>
    </tr> -->
<?php
require_once('app/Mage.php');
umask(0);
Mage::app();

$run = $_GET["run"];

// get value of "Credit option limit (Days)" from apptha marketplace settings/config
$days = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/credit_limit');

// fetch all the orders with state complete and status successfull delivery
$orders = Mage::getModel('sales/order')->getCollection()
		->addFieldToFilter('state', 'complete')
		->setOrder('created_at', 'desc')
        ->addFieldToFilter('status', 'successful_delivery');

if (is_object($orders) && $orders->getSize()) {
	$orders_inc = 0;
	foreach($orders as $order) {

		if (isset($run) && $run == "true") {
			$order->setStatus("complete")->save();
			$orders_inc++;
		}

		//checking if the difference is greater than specified threshold
		$start = strtotime($order->getCreatedAt());
		$end = strtotime(now());
		$diff = ceil(abs($end - $start) / 86400); // 86400 = 1 day
		if ($diff > $days) {

			// Getting Seller Id from Commission Table Information
			$marketplace = Mage::getModel('marketplace/commission')->getCollection()
								->addFieldToFilter('order_id', array('eq' => $order->getId()));
	        static $items_inc = 0;
	        $currentDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
	        foreach($marketplace as $items) {
				if (isset($run) && $run == "true") {
					$items->setItemOrderStatus("complete")->save();
					#12-02-2016 -> Azhar update date of order item shipment datetime
                        $items->setSuccessfulNonRefundableDate($currentDateTime)->save ();
                        #ends here
					$items_inc++;
				}

		    ?>
		    <tr>
		        <td><?php echo $order->getIncrementId() ?></td>
		        <td><?php echo $items->getProductAmt() ?></td>
		        <td><?php echo round($items->getProductAmt() * $items->getProductQty(), 2) ?></td>
		        <td><?php echo $items->getSellerAmount() ?></td>
		        <td><?php echo $items->getCommissionFee() ?></td>
				<td><?php echo $items->getCommissionPercentage() ?></td>
		        <td><?php echo round($items->getOrderTotal(), 2) ?></td>
		        <td><?php echo Mage::getModel('core/date')->date('d.m.Y', strtotime($items->getCreatedAt()))  ?>, (<strong><?php echo $diff ?></strong>) day(s) ago</td>
		        <td align="center">
		        <?php
		        	if (isset($run) && $run == "true") {
		        		echo "Updated!";
		        	} else {
		        		echo "Dry Run";
		        	}
		        ?>
		        </td>
		    </tr>
		    <?php
			}
		}
	}
} else {
?>
		    <tr>
		        <td colspan="11" align="center">No order is found with 'complete' state and 'successfully delivered' status</td>
		    </tr>
<?php
}
$output = $orders_inc . " order(s) are updated, set the status to Completed Non Refundable<br>";
$output .= $items_inc . " order item(s) are updated, set the status to Completed Non Refundable<br>";
?>
<table>
<p class="output">
<?php 
if (isset($run)):
	echo $output;
else:
?>
RESULT: Script is in read only state, <a href="?run=true">Run Now</a>
<?php
endif;
?>
</p>
<?php
/*
$marketplace = Mage::getModel('marketplace/commission')->getCollection();
foreach($marketplace as $items) {
	$arr[] = $items->getIncrementId();
}

$arr[] = 100000334;
$arr[] = 100000333;
$arr[] = 100000332;
$arr[] = 100000329;
$arr[] = 100000308;
$arr[] = 100000306;
$arr[] = 100000307;
$arr[] = 100000305;

$test_order_ids = $arr;
Mage::register('isSecureArea', true);
foreach($test_order_ids as $id){
    try{
        Mage::getModel('sales/order')->loadByIncrementId($id)->delete();
        echo "order #".$id." is removed".PHP_EOL;
    }catch(Exception $e){
        echo "order #".$id." could not be remvoved: ".$e->getMessage().PHP_EOL;
    }
}
*/
?>

