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

//code for bulk update
$model = Mage::getModel('catalog/product')->getCollection()
    ->addAttributeToSelect(array('sku', 'seller_id'))
    ->setPageSize(100);
$numberOfPages = $model->getLastPageNumber();
for ($i = 1; $i <= $numberOfPages; $i++) {
    $products = $model->setCurPage($i)->load();
    $prdCnt = 0;
    //re-initiate data array for every 100 product set
    $data = [
        'TenantToken' => "SssekIKZ9ibj4RsAkhxRvtE74q51xZt/2Xhb3rR4gak=",
        'UserToken' => "1ONKGyj61W7KVNTh7Gyi4nlnXjRC+f5koeDcECPiMo8="
    ];
    foreach ($products as $product) {
        $sellerInfo = Mage::getSingleton('marketplace/sellerprofile')->load($product->getSellerId(), 'seller_id');
        $sellerArray = $sellerInfo->getData();
        //echo $product->getSku() . "\n";
        $data['Items'][$prdCnt++] = ['Sku' => $product->getSku(), 'Supplier' => $sellerArray['store_title']];
        //$data['Items'][] = ['Sku' => "CR001-TS004-36814-Test", 'Supplier' => "noor noor"];
    }
    $url = "https://app.skuvault.com/api/products/updateProducts";
    $httpClient = new Varien_Http_Client($url);
    $jsonData = json_encode($data);
    try{
        $response = $httpClient->setRawData($jsonData, 'application/json')->request('POST');
        $resBody = json_decode($response->getBody());
        if($response->isSuccessful()) {
            echo "Sellers for page ".($i+1)." updated \n";
        }
        else {
            echo "Response:-".$response->getBody().", Response Code: {$response->getStatus()}\n";
        }
    } catch(Exception $e) {
        echo "Exception occurred on page ".($i+1)."\n";
    }
    echo "Next request will be made after a minute due to throtle limit.\n";
    sleep(60);
}

//individual product update
/*$products = Mage::getModel('catalog/product')->getCollection()
    ->addAttributeToSelect(array('sku', 'seller_id'));
$url = "https://app.skuvault.com/api/products/updateProduct";
$httpClient = new Varien_Http_Client($url);
foreach ($products as $product) {
    $sellerInfo = Mage::getSingleton('marketplace/sellerprofile')->load($product->getSellerId(), 'seller_id');
    $sellerArray = $sellerInfo->getData();
    //echo $product->getSku() . "\n";
    $data = [
        'TenantToken' => "SssekIKZ9ibj4RsAkhxRvtE74q51xZt/2Xhb3rR4gak=",
        'UserToken' => "1ONKGyj61W7KVNTh7Gyi4nlnXjRC+f5koeDcECPiMo8=",
        'Sku' => $product->getSku(), 'Supplier' => $sellerArray['store_title']
    ];
    $jsonData = json_encode($data);
    try{
        $response = $httpClient->setRawData($jsonData, 'application/json')->request('POST');
        //$resBody = json_decode($response->getBody());
        if($response->isSuccessful()) {
            echo "Sellers for sku {$product->getSku()} updated\n";
        }
        else {
	    echo "Error Response for SKU {$product->getSku()} store-title: {$sellerArray['store_title']}: " . $response->getBody() . ", Response Code: {$response->getStatus()}\n";
        }
    } catch(Exception $e) {
        echo "Exception for SKU: {$product->getSku()} store-title: {$sellerArray['store_title']}:-" . $e->getMessage() . "\n";
    }
//$data['Items'][] = ['Sku' => "CR001-TS004-36814-Test", 'Supplier' => "noor noor"];
}*/
