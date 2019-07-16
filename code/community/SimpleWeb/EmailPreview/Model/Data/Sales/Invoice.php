<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Sales_Invoice
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Create invoice from order
	 *
	 * @return Mage_Sales_Model_Order_Invoice
	 */
	public function install($data = array())
	{
		/** @var Mage_Sales_Model_Order $order */
		$order = $data['order'];

		/** @var Mage_Sales_Model_Order_Invoice $invoice */
		$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
		$invoice->register();

		// Save Invoice and Order in one transaction
		Mage::getModel('core/resource_transaction')
			->addObject($invoice)
			->addObject($invoice->getOrder())
			->save();

		$this->save('sales/invoice', $invoice->getId());

		return $invoice;
	}

	/**
	 * Uninstall invoice
	 */
	public function uninstall()
	{
		Mage::getModel('sales/order_invoice')
			->load($this->getEntityId('sales/invoice'))
			->delete();
	}
}