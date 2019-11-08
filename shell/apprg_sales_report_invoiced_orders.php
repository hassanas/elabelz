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

$time = time();
$lastTime = $time - (60*60*24); // 60*60*24
$from = date('Y-m-d 00:00:00', $lastTime);
$sellerIncrIds = [];
$collection = Mage::getModel('marketplace/commission')->getCollection()
                ->addFieldToSelect('increment_id')
                ->addFieldToFilter('seller_id', ["in" => [294, 293, 292, 291, 218]])//Apparel Group Sellers
                //->addFieldToFilter('seller_id', ["in" => [53, 17]])//Test seller
                //->addFieldToFilter('seller_id', ["in" => [53]])//Test seller
                ->addFieldToFilter('created_at', array('gteq' =>$from))->distinct(true);
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
    if ($order->hasInvoices()) {
        foreach ($order->getInvoiceCollection() as $inv) {
            //print_r($inv->getData());
            foreach ($inv->getAllItems() as $item) {
                if($item->getPrice() <= 0) continue; //do not include products in report with zero price
                //get supplier sku
                $supplierSku = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'supplier_sku', Mage::app()->getStore()->getStoreId());
                if($supplierSku) {
                    $data[$cnt]['source'] = "elabelz";
                    $data[$cnt]['store_id'] = "E-C11";
                    $data[$cnt]['order_id'] = $order->getIncrementId();

                    $data[$cnt]['invoice_id'] = $inv->getIncrementId();
                    $data[$cnt]['tr_date'] = date('d/m/Y', strtotime($order->getCreatedAt()));
                    $data[$cnt]['tr_time'] = date('h:i:s A', strtotime($order->getCreatedAt()));
                    //identify if it's sale or refund
                    $type = "Sale";
                    if($inv->getBaseTotalRefunded() > 0 || $inv->getTotalRefunded()) {
                        $type = "Refund";
                    }
                    $data[$cnt]['type'] = $type;
                    //print_r($item->getData());
                    $data[$cnt]['sku'] = $supplierSku;
                    //$data[$cnt]['sku'] = $item->getSku();
                    $data[$cnt]['qty'] = (int)$item->getQty();
                    $data[$cnt]['unit_price'] = number_format($item->getPrice(), 2);
                    $data[$cnt++]['disc_amt'] = number_format($item->getDiscountAmount(), 2);
                    /*echo "<pre>";
                    print_r($data);
                    echo "</pre>";*/
                } else {
                    echo "No supplier_sku for product id " . $item->getProductId() . "\n";
                }
            }
        }
    }
}
$file = __DIR__.'/../var/seller/apprgrp/sales-report.csv';
$csv = new Varien_File_Csv();
$csv->setDelimiter(',');
$csv->setEnclosure('');
try {
    if(!empty($data) && $csv->saveData($file, $data)) {
        echo "Csv file created\n";
        //To upload the sales file
        $remote_file = 'Daily Sales/Elabelz_'.date('Y_m_d').'.csv';

        // set up basic connection
        $conn_id = ftp_connect("83.111.221.10");

        // login with username and password
        $login_result = ftp_login($conn_id, "elabelz", "7Mdj5@=7");

        // upload a file
        if (ftp_put($conn_id, $remote_file, $file, FTP_ASCII)) {
            echo "successfully uploaded $file\n";
        } else {
            echo "There was a problem while uploading $file\n";
        }
        // close the connection
        ftp_close($conn_id);
    } else {
        echo "Csv file couldn't be created\n";
        if(empty($data)) echo "because there is no sale record of last one day.\n";
    }
} catch (Exception $e) {
    echo "Exception: ".$e->getMessage()."\n";
}
