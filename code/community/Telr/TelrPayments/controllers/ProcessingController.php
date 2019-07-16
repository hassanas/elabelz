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
class Telr_TelrPayments_ProcessingController extends Mage_Core_Controller_Front_Action
{
	
	protected $_successBlockType = 'telrpayments/success';
	protected $_failureBlockType = 'telrpayments/failure';
	protected $_cancelBlockType = 'telrpayments/cancel';

	protected $_order = NULL;
	protected $_paymentInst = NULL;
	protected $_quote = false;
	protected $_config = null;

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

	protected function _getModel() {
		return Mage::getSingleton('telrpayments/cc');
	}

	/**
	 * when customer selects Telr payment method
	 */
	public function redirectAction()
	{
		try {
			$session = $this->_getCheckoutSession();
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($session->getLastRealOrderId());
			if (!$order->getId()) {
				Mage::throwException('No order for processing found');
			}
			if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
				$order->setState(
					Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
					Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
					Mage::helper('telrpayments')->__('Customer was redirected to gateway.')
					)->save();
			}
			if ($session->getQuoteId() && $session->getLastSuccessQuoteId()) {
				$session->setTelrPaymentsQuoteId($session->getQuoteId());
				$session->setTelrPaymentsSuccessQuoteId($session->getLastSuccessQuoteId());
				$session->setTelrPaymentsRealOrderId($session->getLastRealOrderId());
				$session->getQuote()->setIsActive(false)->save();
				$session->clear();
			}
			$this->loadLayout();
			$this->renderLayout();
			return;
		} catch (Mage_Core_Exception $e) {
			$this->_getCheckoutSession()->addError($e->getMessage());
		} catch(Exception $e) {
			Mage::logException($e);
		}
		$this->_redirect('checkout/cart');
	}

	public function responseAction()
	{
		try {
			$session = $this->_getCheckoutSession();
			$this->getOrderStatus();
			$order_code=trim($session->getTelrPaymentsCode()."");	 // Ensure session values are handled as strings
			$tran_status=trim($session->getTelrPaymentsStatus()."");
			if ($order_code === '3') {		// Paid (captured, not on hold)
				$this->_processSale(true,$tran_status);
			} elseif ($order_code === '2') {	// Authorised (may be on hold)
				$this->_processSale(false,$tran_status);
			} elseif ($order_code === '-2') {	// Cancelled
				$this->_processCancel();
			} else {
				Mage::throwException('Transaction was not successfull.');
			}
		} catch (Mage_Core_Exception $e) {
			$this->getResponse()->setBody(
				$this->getLayout()
				->createBlock($this->_failureBlockType)
				->setOrder($this->_order)
				->toHtml()
			);
		}
	}

	/**
	 * Telr return action
	 */
	public function successAction()
	{		
		try {
			$session = $this->_getCheckoutSession();
			$session->unsTelrPaymentsRealOrderId();
			$session->setQuoteId($session->getTelrPaymentsQuoteId(true));
			$session->setLastSuccessQuoteId($session->getTelrPaymentsSuccessQuoteId(true));
			$this->responseAction();
			$this->_redirect('checkout/onepage/success');
			return;
		} catch (Mage_Core_Exception $e) {
			$this->_getCheckoutSession()->addError($e->getMessage());
		} catch(Exception $e) {
			Mage::logException($e);
		}
		$this->_redirect('checkout/cart');
	}

	/**
	 * TelrPayments return action
	 */
	public function cancelAction()
	{
		// set quote to active
		$session = $this->_getCheckoutSession();
		if ($quoteId = $session->getTelrPaymentsQuoteId()) {
			$quote = Mage::getModel('sales/quote')->load($quoteId);
			if ($quote->getId()) {
				$quote->setIsActive(true)->save();
				$session->setQuoteId($quoteId);
			}
		}
		$this->responseAction();
		$session->addError(Mage::helper('telrpayments')->__('The order has been canceled.'));
		$this->_redirect('checkout/cart');
	}

	protected function getOrderStatus()
	{	
		$session = $this->_getCheckoutSession();
		$model=$this->_getModel();
		$model->getOrderStatus();
		$cart_id=$session->getTelrPaymentsCart();
		if (empty($cart_id)) {
			Mage::throwException('Cart ID not found');
		}
		$this->_order = Mage::getModel('sales/order')->loadByIncrementId($cart_id);
		if (!$this->_order->getId()) {
			Mage::throwException('Order not found');
		}
		$this->_paymentInst = $this->_order->getPayment()->getMethodInstance();
	}

	protected function _createInvoice()
	{
		if (!$this->_order->canInvoice()) {
			return;
		}
		// Triggers call to capture in Cc.php
		$invoice = $this->_order->prepareInvoice();
		$invoice->register()->capture();
	        $this->_order->addRelatedObject($invoice);
	}

	protected function _processSale($captured,$trans_status)
	{
		$session = $this->_getCheckoutSession();
		$tran_ref=$session->getTelrPaymentsTransRef();

		if ($trans_status==='H') {
			$captured=false;
			$additional_data = $this->_order->getPayment()->getAdditionalData();
			$additional_data .= ($additional_data ? "<br/>\n" : '') . 'Transaction was placed on hold by anti-fraud system';
			$this->_order->getPayment()->setAdditionalData($additional_data);
		}
		if ($captured) {
			$this->_createInvoice();
			$this->_order->setState(Mage_Sales_Model_Order::STATE_NEW, true, "Payment authorised");
			$this->_order->getPayment()->setLastTransId($tran_ref);
			$this->_order->sendNewOrderEmail();
			$this->_order->setEmailSent(true);
		} else {
			if ($trans_status==='H') {
	                	$this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, "Payment held by anti-fraud system");
			} else {
	                	$this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, "Payment authorised but requires completion");
			}
			$this->_order->getPayment()->setLastTransId($tran_ref);
		}

		$this->_order->save();

		$this->getResponse()->setBody(
			$this->getLayout()
			->createBlock($this->_successBlockType)
			->setOrder($this->_order)
			->toHtml()
		);
	}

	/**
	 * Process success response
	 */
	protected function _processCancel()
	{
		// cancel order
		if ($this->_order->canCancel()) {
			$this->_order->cancel();
			$this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper('telrpayments')->__('Payment was canceled'));
			$this->_order->save();
		}

		$this->getResponse()->setBody(
			$this->getLayout()
			->createBlock($this->_cancelBlockType)
			->setOrder($this->_order)
			->toHtml()
		);
	}

	protected function _getPendingPaymentStatus()
	{
		return Mage::helper('telrpayments')->getPendingPaymentStatus();
	}
}
