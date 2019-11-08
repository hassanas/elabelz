<?php 

// 	echo  '<p>----------------Request Example SOAP V2 (WS-I Compliance Mode)-----------------</p>';

// 	$proxy  = new SoapClient('https://admin.elabelz.com/api/v2_soap?wsdl=1'); // TODO : change url
// 	$sessionId = $proxy->login((object)array('username' => 'skuvault1', 'apiKey' => '4UisNqVGT6FhkfzDCpbE'));

// 		$complexFilter = array(
// 		    'complex_filter' => array(
// 		        array(
// 		            'key' => 'type',
// 		            'value' => array('key' => 'in', 'value' => 'simple')
// 		        )
// 		    )
// 		);
// $size = new stdClass();
// 		$size->additional_attributes = array('size');
// 		$productData = $proxy->catalogProductInfo((object)array('sessionId' => $sessionId->result, 'productId' => $resultt['product_id'],$size));
// $productData = json_decode(json_encode($productData), true);
// 	//echo  '<pre>'; print_r($productData); echo  '</pre>';	
// 	$result = $proxy->catalogProductList((object)array('sessionId' => $sessionId->result, 'filters' => $complexFilter));
// 	$results = json_decode(json_encode($result), true);
// 	foreach($results['result']['complexObjectArray'] as $resultt){
// 		//echo  '<br>'.$resultt['product_id'];
// 		$size = new stdClass();
// 		$size->additional_attributes = array('size');
// 		$productData = $proxy->catalogProductInfo((object)array('sessionId' => $sessionId->result, 'productId' => $resultt['product_id'],$size));
// 		$productData = json_decode(json_encode($productData), true);
// 		echo '<br>'.$resultt['product_id'].':- '.$productData['result']['name'];
// 		//echo  '<pre>'; print_r($productData); echo  '</pre>';
// 	}	
// 	exit;

ini_set("max_execution_time", 0);
ini_set("memory_limit", "10000M");
error_reporting(E_ALL);
try
{
// $client = new SoapClient('https://ae.elabelz.com/api/v2_soap?wsdl=1');
// $sessionId = $client->login('skuvault1','4UisNqVGT6FhkfzDCpbE');
// $result = $client->catalogCategoryTree($sessionId);
// var_dump($result);
// echo "<pre>"; print_r($result);
echo  '<p>----------------Request Example SOAP V2 (WS-I Compliance Mode)-----------------</p>';
$proxy = new SoapClient('https://admin.elabelz.com/api/v2_soap?wsdl=1'); // TODO : change url
$sessionId = $proxy->login((object)array('username' => 'skuvault1', 'apiKey' => '4UisNqVGT6FhkfzDCpbE'));
//$result = $proxy->catalogProductInfo((object)array('sessionId' => $sessionId->result, 'productId' => '13048'));
$result = $proxy->catalogCategoryTree((object)array('sessionId' => $sessionId->result, 'parentId' => '1'));
var_dump($result->result);
// echo "<pre>";
// print_r($result);
}
catch (Exception $e)
{
	echo "<pre>";
    print_r($e);
   // var_dump($e);
}
exit;

echo  '<p>----------Request Example SOAP V1--------------</p>';
$client = new SoapClient('https://admin.elabelz.com/api/soap/?wsdl');
$session = $client->login('skuvault1', '4UisNqVGT6FhkfzDCpbE');
$result = $client->call($session, 'catalog_category.tree');
var_dump($result)

?>