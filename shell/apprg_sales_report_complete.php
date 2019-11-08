<?php
/**
 * User: imran
 * Date: 6/14/16
 * Time: 4:32 PM
 */

require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
//$from = date('Y-m-d 00:00:00', strtotime(' -1 day'));

function logMsg($msg, $display = true)
{
    if($display === true) echo $msg;
    Mage::log("apprg_sales_report_complete:".$msg);
}

//getting store titles against store_ids
/*$collStores = Mage::getModel('marketplace/sellerprofile')->getCollection()
    ->addFieldToFilter('seller_id', ["in" => [294, 293, 292, 291]])//Apparel Group Sellers
    ->distinct(true);
//echo $collection->getSelect();
$stores = [];
foreach($collStores as $store){
    $stores[$store->getSellerId()] = $store->getStoreTitle();
}*/

//store codes
$storeLocationCodes = [
    'Birkenstock Mall of Emirates' => '7584',
    'Peoples - Athletes Co Mall of Emirates' => '13007',
    'Toms - Apparel Group LLC' => '11302',
    'NEW BALANCE - Mirdif City Center' => 'NB104',

    '294' => 'NB104',
    '293' => '11302',
    '292' => '13007',
    '291' => '7584'
];

function getParentProduct($product)
{
    if($product->getTypeId() == "simple"){
        $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
        if(!$parentIds)
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        if(isset($parentIds[0])){
            return Mage::getModel('catalog/product')->load($parentIds[0]);
        }
    }
}

/**
 * @param $data actual data array to store the parse result
 * @param $cnt the index that is used to organize the indexes
 * @param $item OrderItem or InvoiceItem
 * @param bool $orderItem Call for OrderItem
 * @param bool $inv Call for InvoiceItem Object
 */
function parseItemData(&$data, &$cnt, $item, $order, $orderItem=true, $inv = null)
{
    global $storeLocationCodes;
    $productId = $item->getProductId();
    //don't process the configurable products
    if(Mage::getModel('catalog/product')->load($productId)->getTypeId() == "configurable") {
        return;
    }
    if(strtolower($item->getStatus()) == 'closed' || strtolower($item->getStatus()) == 'canceled' ||
        strtolower($order->getStatus()) == "canceled" || strtolower($order->getStatus()) == "failed delivery") {
        logMsg("Skipping item as it's item_status ({$item->getStatus()}) order_status: {$order->getStatus()} item sku: " .
            Mage::getModel('catalog/product')->load($productId)->getSku() . " in order# " . $order->getIncrementId() . "\n");
        return;
    }
    //in case of zero price get price of the configurable ordered item, as at times it was found that the child
    //product's cost was added as ZERO
    $confItem = null;
    if($item->getPrice() <= 0 && Mage::getModel('catalog/product')->load($productId)->getTypeId() != "configurable") {
        $parentProduct = getParentProduct(Mage::getModel('catalog/product')->load($productId));//get configurable product
        if(is_object($parentProduct)) {
            $confItem = Mage::getResourceModel('sales/order_item_collection')
                ->addAttributeToSelect('*')
                ->addFieldToFilter('order_id', ['eq' => $order->getId()])
                ->addFieldToFilter('product_id', ['eq' => $parentProduct->getId()])->getFirstItem();

            if($confItem->getPrice() <= 0) {//return if price is empty
                logMsg("Due to price ({$item->getPrice()}) skipping item sku: " . Mage::getModel('catalog/product')->load($productId)->getSku() . " in order# " . $order->getIncrementId() . "\n");
                return;
            }
        } else {
            logMsg("Due to price ({$item->getPrice()}) skipping item sku: " . Mage::getModel('catalog/product')->load($productId)->getSku() . " in order# " . $order->getIncrementId() . "\n");
            return;
        }
    }

    //get supplier sku
    $supplierSku = Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'supplier_sku', Mage::app()->getStore()->getStoreId());
    if($supplierSku) {
        $sellerId = Mage::getModel('catalog/product')->load($productId)->getSellerId();
        if(empty($storeLocationCodes[$sellerId])) {
            logMsg("The product is not from allowed store codes SKU:" . Mage::getModel('catalog/product')->load($productId)->getSku() . " seller_sku: $supplierSku");
            return;
        }
        $data[$cnt]['source'] = "elabelz";
        $data[$cnt]['store_id'] = $storeLocationCodes[$sellerId];

        //$sellerProfile = Mage::getModel('marketplace/sellerprofile')->load($sellerId, 'seller_id');
        //$storeName = $sellerProfile->getStoreTitle();
        $data[$cnt]['order_id'] = $order->getIncrementId();

        //in case the order is not invoiced just include the order id
        $data[$cnt]['invoice_id'] = (!empty($inv)) ? $inv->getIncrementId() : $order->getIncrementId();
        $data[$cnt]['tr_date'] = date('d/m/Y', strtotime($order->getCreatedAt()));
        $data[$cnt]['tr_time'] = date('h:i:s A', strtotime($order->getCreatedAt()));
        //identify if it's sale or refund
        $type = "Sale";
        if(!empty($inv)) {
            if($inv->getBaseTotalRefunded() > 0 || $inv->getTotalRefunded()) {
                $type = "Refund";
            }
        }

        if(strtolower($item->getStatus()) == 'closed' || strtolower($item->getStatus()) == 'canceled') {
            $type = ucfirst($item->getStatus());
        }

        //now check item is not canceled
        $orderStatus = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('item_order_status')
            ->addFieldToFilter('increment_id', ["in" => [$order->getIncrementId()]])//Apparel Group Sellers
            ->getFirstItem();//in case of refunded
        if($orderStatus->getItemOrderStatus() == "canceled") {
            $type = "Canceled";
        }

        $data[$cnt]['type'] = $type;
        //print_r($item->getData());
        $data[$cnt]['sku'] = $supplierSku;
        //$data[$cnt]['sku'] = $item->getSku();
        $data[$cnt]['qty'] = (int)$item->getQtyOrdered();
        $price = ($item->getBasePrice() > 0) ? $item->getBasePrice() : $confItem->getBasePrice();
        $data[$cnt]['unit_price'] = number_format($price, 2);
        $data[$cnt++]['disc_amt'] = number_format($item->getDiscountAmount(), 2);
    } else {
        $product = Mage::getModel('catalog/product')->load($productId);
        if($product->getTypeId() != "configurable")
            logMsg("No supplier_sku for product id " . $productId . " product_sku: {$product->getSku()}\n");
    }
}

//$time = time();
//$lastTime = $time - (60*60*24); // 60*60*24
//$from = date('Y-m-d 00:00:00', $lastTime);
$sellerIncrIds = [];
//338, 294, 293, 292, 291, 218, 162
$collection = Mage::getModel('marketplace/commission')->getCollection()
    ->addFieldToSelect('increment_id')
    ->addFieldToFilter('seller_id', ["in" => [294, 293, 292, 291]])//Apparel Group Sellers
    ->addFieldToFilter('is_buyer_confirmation', ['neq' => "Rejected"])
    ->addFieldToFilter('is_seller_confirmation', ['neq' => "Rejected"])
    ->addFieldToFilter('order_status', ['neq' => "canceled"])
    ->addFieldToFilter('order_status', ['neq' => "failed_delivery"])
    //->addFieldToFilter('seller_id', ["in" => [53, 17]])//Test seller
    //->addFieldToFilter('seller_id', ["in" => [53]])//Test seller
    ->distinct(true);
//->addFieldToFilter('seller_id',array("in"=>['53']))//test seller
//echo $collection->getSelect();
foreach($collection as $row){
    array_push($sellerIncrIds, $row->getIncrementId());
}
//print_r($sellerIncrIds);
$orderCollection = Mage::getModel('sales/order')->getCollection()
    ->addAttributeToFilter('increment_id', array('in' => $sellerIncrIds));

$data = [];
$cnt = 0;

foreach($orderCollection as $order) {
    /*if ($order->hasInvoices()) {
        foreach ($order->getInvoiceCollection() as $inv) {
            //print_r($inv->getData());
            foreach ($inv->getAllItems() as $item) {
                echo $inv->getIncrementId().">>>".$item->getPrice()." Row total:".$item->getRowTotalInclTax()."\n";
                parseItemData($data, $cnt, $item, $order, false, $inv);
            }
        }
    } else {*/
    //Invoice portion commented coz it's not required plus it returns values two times once with price zero and once with actual price
    $orderedItems = $order->getAllItems();
    $orderedProductIds = [];

    //$storeName = $sellerProfile->getStoreTitle();
    //$sellerProfile = Mage::getModel('marketplace/sellerprofile')->load($sellerId, 'seller_id');
    //$storeName = $sellerProfile->getStoreTitle();
    foreach ($orderedItems as $item) {
        //echo $order->getIncrementId()."<<<".$item->getPrice()." Row total:".$item->getRowTotalInclTax()."|".$item->getData('product_id')."\n";
        $orderedProductIds[] = $item->getData('product_id');
        parseItemData($data, $cnt, $item, $order);
    }
    //}
}
$dir = __DIR__.'/../var/seller/apprgrp';
$file = $dir.'/sales-report-complete.csv';
if (!file_exists($dir)) {
    mkdir($dir, 0775, true);
    logMsg("Created $dir.\n");
}
$csv = new Varien_File_Csv();
$csv->setDelimiter(',');
$csv->setEnclosure('');
try {
    if(!empty($data) && $csv->saveData($file, $data)) {
        logMsg("Csv file created\n");
        //To upload the sales file
        $remote_file = 'Elabelz_'.date('Y_m_d').'_complete_sales.csv';

        // set up basic connection
        $conn_id = ftp_connect("83.111.221.10");

        // login with username and password
        $login_result = ftp_login($conn_id, "elabelz", "7Mdj5@=7");

        // set ftp to passive mode
        ftp_pasv($conn_id, true);

        // upload a file
        if (ftp_put($conn_id, $remote_file, $file, FTP_ASCII)) {
            logMsg("successfully uploaded $file\n");
        } else {
            logMsg("There was a problem while uploading $file\n");
            print_r(error_get_last());
        }
        // close the connection
        ftp_close($conn_id);
    } else {
        logMsg("Csv file couldn't be created\n");
        if(empty($data)) logMsg("because there is no sale record for this seller.\n");
    }
} catch (Exception $e) {
    logMsg("Exception: ".$e->getMessage()."\n");
}

