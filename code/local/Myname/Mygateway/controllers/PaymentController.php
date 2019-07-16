<?php
/*
Mygateway Payment Controller
By: Junaid Bhura
www.junaidbhura.com
*/
use \Firebase\JWT\JWT;

class Myname_Mygateway_PaymentController extends Mage_Core_Controller_Front_Action {
	// The redirect action is triggered when someone places an order
	public function redirectAction() {
		require_once('zaincash/credentials.php');
		require_once('zaincash/includes/autoload.php');

		$_order = new Mage_Sales_Model_Order();
		$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$_order->loadByIncrementId($orderId);
		



		// ----------------- Order Details --------------------------
		//The total price of your order in Iraqi Dinar only like 1000 (if in dollar, multiply it by dollar-dinar exchange rate, like 1*1300=1300)
		$amount=$_order->getBaseGrandTotal();

		//Type of service you provide, like 'Books', 'ecommerce cart', 'Hosting services', ...
		$service_type="Magento";

		//Order id, you can use it to help you in tagging transactions with your website IDs, if you have no order numbers in your website, leave it 1
		//Variable Type is STRING, MAX: 512 chars
		$order_id=$orderId;

		//after a successful or failed order, the user will redirect to this url
		$redirection_url=Mage::getUrl('mygateway/payment/response');

		/* ------------------------------------------------------------------------------
Notes about $redirection_url: 
in this url, the api will add a new parameter (token) to its end like:
https://example.com/redirect.php?token=XXXXXXXXXXXXXX
------------------------------------------------------------------------------  */

		//building data
		$data = [
		'amount'  => intval($amount*$dollar),        
		'serviceType'  => $service_type,          
		'msisdn'  => $msisdn,  
		'orderId'  => $order_id,
		'redirectUrl'  => $redirection_url,
		];

		//Encoding Token
		$newtoken = JWT::encode(
		$data,      //Data to be encoded in the JWT
		$secret ,'HS256'
		);

		//Check if test or production mode
		$tUrl = 'https://api.zaincash.iq/transaction/init';
		$rUrl = 'https://api.zaincash.iq/transaction/pay?id=';

		//POSTing data to ZainCash API
		$data_to_post = array();
		$data_to_post['token'] = urlencode($newtoken);
		$data_to_post['merchantId'] = $merchantid;
		$data_to_post['lang'] = $language;
		$options = array(
		'http' => array(
		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		'method'  => 'POST',
		'content' => http_build_query($data_to_post),
		),
		);
		$context  = stream_context_create($options);
		$response= file_get_contents($tUrl, false, $context);

		//Parsing response
		$array = json_decode($response, true);
		$transaction_id = $array['id'];
		$newurl=$rUrl.$transaction_id;
		header('Location: '.$newurl);
		exit();

	}
	
	// The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function responseAction() {
		if (isset($_GET['token'])){
		require_once('zaincash/credentials.php');
		require_once('zaincash/includes/autoload.php');

			//you can decode the token by this PHP code:
			JWT::$leeway = 50000;
			$result= JWT::decode($_GET['token'], $secret, array('HS256'));
			$result= (array) $result;
			
			$orderId=$result['orderid'];

			//And to check for status of the transaction, use $result['status'], like this:
			if ($result['status']=='success'){
				//Successful transaction
				$validated=true;			
			}
			if ($result['status']=='failed'){
				$validated=false;
				//Failed transaction and its reason
				$reason=$result['msg'];
			}
		} else {
			//Cancelled transaction (if he clicked "Cancel and go back"
					Mage_Core_Controller_Varien_Action::_redirect('');

			//NO TOKEN HERE, SO NO $result
		}
		
		if($validated) {
			// Payment was successful, so update the order's state, send order email and move to the success page
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($orderId);
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
			
			$order->sendNewOrderEmail();
			$order->setEmailSent(true);
			
			$order->save();
			
			Mage::getSingleton('checkout/session')->unsQuoteId();
			
			Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
		}
		else {
			// There is a problem in the response we got
			$this->cancelAction();
			Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
		}
		//REDIRECTION
	}
	
	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction() {
		if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
			$order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
			if($order->getId()) {
				// Flag the order as 'cancelled' and save it
				$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
			}
		}
	}
}