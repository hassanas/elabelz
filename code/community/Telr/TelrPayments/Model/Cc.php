<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Telr
 * @package	Telr_TelrPayments
 * @copyright	Copyright (c) 2015 Telr (https://telr.com/)
 */

class Telr_TelrPayments_Model_Cc extends Mage_Payment_Model_Method_Abstract

{
	protected $_code = 'telrpayments_cc';

	protected $_isGateway			= true;
	protected $_canAuthorize		= true;
	protected $_canCapture			= true;
	protected $_canCapturePartial		= false;
	protected $_canRefund			= false;
	protected $_canRefundInvoicePartial	= false;
	protected $_canVoid			= false;
	protected $_canUseInternal		= false;
	protected $_canUseCheckout		= true;
	protected $_canUseForMultishipping	= false;
	protected $_canSaveCc			= false;

	protected $_paymentMethod		= 'cc';
	protected $_defaultLocale		= 'en';

	protected $_testAdminUrl	= 'https://secure.telr.com/gateway/order.json';
	protected $_liveAdminUrl	= 'https://secure.telr.com/gateway/order.json';

	protected $_formBlockType = 'telrpayments/form';
	protected $_infoBlockType = 'telrpayments/info';

        protected $_order = NULL;
        protected $_quote = false;


	protected function _getCheckoutSession()
	{
		return Mage::getSingleton('checkout/session');
	}

	protected function _getQuote()
	{
		if (!$this->_quote) {
			$this->_quote = $this->_getCheckoutSession()->getQuote();
		}
		return $this->_quote;
	}


	public function getOrder()
	{
		if (!$this->_order) {
			$this->_order = $this->getInfoInstance()->getOrder();
		}
		return $this->_order;
	}

	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('telrpayments/processing/redirect');
	}

	public function getPaymentMethodType()
	{
		return $this->_paymentMethod;
	}

	public function getUrl()
	{
		return $this->_liveAdminUrl;
	}

	public function getAdminUrl()
	{
		return $this->_liveAdminUrl;
	}

	private function _buildUrl($part) {
		$url= Mage::getUrl($part,array('_nosid' => true));
		$url = trim(str_replace('&amp;', '&', $url));
		return $url;
	}

	private function _tidy($value,$remove_accents) { 
		if ($remove_accents>0) {
			$value=Mage::helper('core')->removeAccents($value);
		}
		return trim($value);
	}

	public function createOrder()
	{
		$session = $this->_getCheckoutSession();
		$this->_clearTransResults($session);
		$securityKey = trim($this->getConfigData('security_key'));
		if (empty($securityKey)) {
			$message = 'Secret key not set';
			Mage::throwException($message);
		}
		$price		= number_format($this->getOrder()->getGrandTotal(),2,'.','');
		$currency	= $this->getOrder()->getOrderCurrencyCode();
		$billing	= $this->getOrder()->getBillingAddress();
		$shipping	= $this->getOrder()->getShippingAddress();

		$locale = trim(Mage::app()->getLocale()->getLocaleCode());
		if (empty($locale)) {
			$locale = trim($this->getDefaultLocale());
		}
		$order_id = $this->getOrder()->getRealOrderId();
		
		//$order_id = $order_id.uniqid();
		
		$tran_desc = trim($this->getConfigData('tran_desc'));
		if (empty($tran_desc)) {
			$tran_desc = 'Your purchase at ' . Mage::app()->getStore()->getName();
		}
		$tran_desc = str_replace('{order}', $order_id, $tran_desc);
		$version = Mage::getVersion();

		$params = 	array(
			'ivp_method' => 'create',
			'ivp_store'		=>	$this->_tidy($this->getConfigData('store_id'),0),
			'ivp_authkey'		=>	trim($this->getConfigData('security_key')),
			'ivp_source'		=>	$this->_tidy('Magento '.$version,0),
			'ivp_cart'		=>	$this->_tidy($order_id,0),
			'ivp_test'		=>	($this->getConfigData('transaction_testmode') == '0') ? '0' : '1',
			'ivp_amount'		=>	$this->_tidy($price,0),
			'ivp_currency'		=>	$this->_tidy($currency,0),
			'ivp_desc'		=>	$this->_tidy($tran_desc,0),
			'ivp_lang'		=>	$this->_tidy($locale,0),
			'bill_title'		=>	'',
			'bill_fname'		=>	$this->_tidy($billing->getFirstname(),0),
			'bill_sname'		=>	$this->_tidy($billing->getLastname(),0),
			'bill_addr1'		=>	$this->_tidy($billing->getStreet(1),0),
			'bill_addr2'		=>	$this->_tidy($billing->getStreet(2),0),
			'bill_addr3'		=>	$this->_tidy($billing->getStreet(3),0),
			'bill_city'		=>	$this->_tidy($billing->getCity(),0),
			'bill_region'		=>	$this->_tidy($billing->getRegion(),0),
			'bill_zip'		=>	$this->_tidy($billing->getPostcode(),0),
			'bill_country'		=>	$this->_tidy($billing->getCountry(),0),
			'bill_email'		=>	$this->_tidy($this->getOrder()->getCustomerEmail(),0),
			'bill_phone1'		=>	$this->_tidy($billing->getTelephone(),0),
			'delv_title'		=>	'',
			'delv_fname'		=>	$this->_tidy($shipping->getFirstname(),0),
			'delv_sname'		=>	$this->_tidy($shipping->getLastname(),0),
			'delv_addr1'		=>	$this->_tidy($shipping->getStreet(1),0),
			'delv_addr2'		=>	$this->_tidy($shipping->getStreet(2),0),
			'delv_city'		=>	$this->_tidy($shipping->getCity(),0),
			'delv_region'		=>	$this->_tidy($shipping->getRegion(),0),
			'delv_zip'		=>	$this->_tidy($shipping->getPostcode(),0),
			'delv_country'		=>	$this->_tidy($shipping->getCountry(),0),
			'return_auth'		=>	$this->_buildUrl('telrpayments/processing/success'),
			'return_can'		=>	$this->_buildUrl('telrpayments/processing/cancel'),
			'return_decl'		=>	$this->_buildUrl('telrpayments/processing/cancel'),
		);

		if (Mage::app()->getStore()->isCurrentlySecure()) {
			// https enabled
			$customer = Mage::getSingleton('customer/session');
			if ($customer->isLoggedIn()) {
				// registered user
				$params['bill_custref']=$customer->getCustomerId();
			}
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->_liveAdminUrl);
		curl_setopt($ch, CURLOPT_POST, count($params));
		curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		$results = curl_exec($ch);
		curl_close($ch);
		$results = json_decode($results,true);
		$ref= trim($results['order']['ref']);
		$url= trim($results['order']['url']);
		if (empty($ref) || empty($url)) {
			Mage::throwException("Order couldn't be created");
		}
		// Set value in session
		$session->setOrderRef($ref);
                $session->setTelrCheckoutPage($url);
		return $url;
	}

	/*
	public function void(Varien_Object $payment, $amount)
	{
		$transactionId = $payment->getLastTransId();

		$params['ivp_trantype']		= 'void';
		$params['ivp_amount']		= $amount;
		$params['ivp_currency']		= $payment->getOrder()->getOrderCurrencyCode();
		$params['tran_ref']		= $transactionId;

		$response = $this->processRemoteRequest($params);
		return $this;
	}
	*/

	public function capture(Varien_Object $payment, $amount)
	{
		if (!$this->canCapture()) {
			return $this;
		}
		$session = $this->_getCheckoutSession();
		if ($session) {
	                $url=$session->getTelrCheckoutPage();
			if (!empty($url)) {
				return $this;
			}
		}

		$response = $this->getOrderStatus();
		$payment->getOrder()->addStatusToHistory($payment->getOrder()->getStatus(), $this->_getHelper()->__('TelrPayments transaction has been captured.'));
		return $this;
	}

	public function canEdit () {
		return false;
	}

	public function canManageBillingAgreements () {
		return false;
	}

	public function canManageRecurringProfiles () {
		return false;
	}

	/*
	public function canVoid ()
	{
		return $this->_remoteEnabled();
	}
	*/

	public function canRefund ()
	{
		return false;
	}

	public function canRefundInvoicePartial()
	{
		return false;
	}

	public function canRefundPartialPerInvoice()
	{
		return false;
	}

	public function canCapturePartial()
	{
		return false;
	}

	protected function _remoteEnabled() {
		return 0;
	}

	protected function _setErrorResult($session) {
		$session->setTelrPaymentsCart('');
		$session->setTelrPaymentsTransRef('000000000000');
		$session->setTelrPaymentsCode('-3');
		$session->setTelrPaymentsStatus('E');
		$session->setTelrPaymentsAuth('01');
		$session->setTelrPaymentsMesg('Error checking order results');
	}

	protected function _setTransResults($session,$results) {
		if (!empty($results['error']['message'])) {
			$this->setErrorResults($session);
			return false;
		}
		if (empty($results['order']['cartid'])) {
			$this->setErrorResults($session);
			return false;
		}
		$session->setTelrPaymentsCart($results['order']['cartid']);
		$session->setTelrPaymentsTransRef($results['order']['transaction']['ref']);
		$session->setTelrPaymentsCode($results['order']['status']['code']);
		$session->setTelrPaymentsStatus($results['order']['transaction']['status']);
		$session->setTelrPaymentsAuth($results['order']['transaction']['code']);
		$session->setTelrPaymentsMesg($results['order']['transaction']['message']);
		return true;
	}

	protected function _clearTransResults($session) {
		$session->unsTelrPaymentsCart();
		$session->unsTelrPaymentsTransRef();
		$session->unsTelrPaymentsCode();
		$session->unsTelrPaymentsStatus();
		$session->unsTelrPaymentsAuth();
		$session->unsTelrPaymentsMesg();
                $session->unsTelrCheckoutPage();
	}

	public function getOrderStatus()
	{	
		$session = $this->_getCheckoutSession();


              //  if (!empty($session->getTelrPaymentsTransRef())) {
                //        return;
                //}


		$orderRef=trim($session->getOrderRef());
		$securityKey = trim(Mage::getStoreConfig('payment/telrpayments_cc/security_key'));
		$storeId = trim(Mage::getStoreConfig('payment/telrpayments_cc/store_id'));
		
		if (empty($securityKey) || empty($storeId) || empty($orderRef)) {
			$message = 'Unable to check order. Key/Store/Ref not set';
			Mage::throwException($message);
		}

		$params = array(
			'ivp_method' => 'check',
			'ivp_store'		=>	$storeId,
			'ivp_authkey'		=>	$securityKey,
			'order_ref'		=>	$orderRef
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->_liveAdminUrl);
		curl_setopt($ch, CURLOPT_POST, count($params));
		curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		$results = curl_exec($ch);
		curl_close($ch);
		$results = json_decode($results,true);

		if ($this->_setTransResults($session,$results)==false) {
			Mage::throwException('Error parsing order results');
		}
		return;
	}
}
