<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Sales_Shipment
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Retrieve item quantities
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	protected function _getItemQtys(Mage_Sales_Model_Order $order)
	{
		$qtys = array();
		foreach($order->getAllVisibleItems() as $product)
		{
			$qtys[$product->getId()] = 1;
		}

		return $qtys;
	}

	/**
	 * Get tracking object
	 *
	 * @param int $number
	 * @return Mage_Sales_Model_Order_Shipment_Track
	 */
	protected function _getTracking($number = 1)
	{
		return Mage::getModel('sales/order_shipment_track')
			->setData('title', 'Tracking #' . $number)
			->setData('number', '0000000000000' . $number)
			->setData('carrier_code', 'flatrate_flatrate');
	}

	/**
	 * Create shipment from order
	 *
	 * @param array $data
	 * @return Mage_Sales_Model_Order_Shipment
	 */
	public function install($data = array())
	{
		/** @var Mage_Sales_Model_Order $order */
		$order = $data['order'];

		/** @var Mage_Sales_Model_Order_Shipment $shipment */
		$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($this->_getItemQtys($order));

		$shipment->addTrack($this->_getTracking(1));
		$shipment->addTrack($this->_getTracking(2));
		$shipment->addTrack($this->_getTracking(3));

		$shipment->register();

		// Save Order and Shipment in one transaction
		Mage::getModel('core/resource_transaction')
			->addObject($shipment)
			->addObject($shipment->getOrder())
			->save();

		$this->save('sales/shipment', $shipment->getId());

		return $shipment;
	}

	/**
	 * Uninstall invoice
	 */
	public function uninstall()
	{
		Mage::getModel('sales/order_shipment')
			->load($this->getEntityId('sales/shipment'))
			->delete();
	}
}