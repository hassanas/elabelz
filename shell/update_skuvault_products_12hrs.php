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
//products added in last 12 hours
$attributeCode = 'seller_id';
$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
$id = $attribute->getId();
$time = time();
$lastTime = $time - 86400; // 60*60*12
$from = date('Y-m-d H:i:s', $lastTime);
//For bulk product upate
$collection = Mage::getModel('catalog/product')
    ->getCollection()
    ->addAttributeToSelect(array('sku', 'seller_id'))
    ->setPageSize(100)
    ->addAttributeToFilter('created_at', array('gteq' =>$from));
$collection->getSelect()->joinInner(
    array('o'=> 'catalog_product_entity_text'),
    'e.entity_id = o.entity_id AND o.attribute_id = '.$id,
    array('o.value')
);
$collection->getSelect()->joinLeft(
    array('ms'=> 'marketplace_sellerprofile'),
    'o.value = ms.seller_id ',
    array('ms.store_title')
);
$numberOfPages = $collection->getLastPageNumber();
for ($i = 1; $i <= $numberOfPages; $i++) {
    $products = $collection->setCurPage($i)->load();
    $prdCnt = 0;
    //re-initiate data array for every 100 product set
    $data = [
        'TenantToken' => "SssekIKZ9ibj4RsAkhxRvtE74q51xZt/2Xhb3rR4gak=",
        "UserToken" => "1ONKGyj61W7KVNTh7Gyi4nlnXjRC+f5koeDcECPiMo8="
    ];
    foreach ($products as $product) {
        $data['Items'][$prdCnt++] = ['Sku' => $product->getSku(), 'Supplier' => $product->getStoreTitle()];
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
    /* Increased the throttling limit as shown in https://dev.skuvault.com/blog/updated-the-throttling-limits-for-bulk-calls */
    if($i%5 == 0) {
        echo "Next request will be made after a minute due to throttle limit.\n";
        sleep(60);
    }

}