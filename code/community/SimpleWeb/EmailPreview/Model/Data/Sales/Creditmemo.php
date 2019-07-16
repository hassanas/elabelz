<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Sales_Creditmemo
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Get items to refund
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	protected function _getItemQtys($order)
	{
		$items = array();

		foreach($order->getAllVisibleItems() as $item)
		{
			/** @var $item Mage_Sales_Model_Order_Item */
			$items[ $item->getId() ] = $item->getQtyOrdered();
		}

		return $items;
	}

	/**
	 * Create creditmemo from order
	 */
	public function install($data = array())
	{
		/** @var Mage_Sales_Model_Order $order */
		$order = $data['order'];

		/** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
		$creditmemo = Mage::getModel('sales/service_order', $order)->prepareCreditmemo(array(
			'qtys' => $this->_getItemQtys($order)
		));

		$creditmemo->register();

		// Save Creditmemo and Order in one transaction
		Mage::getModel('core/resource_transaction')
			->addObject($creditmemo)
			->addObject($creditmemo->getOrder())
			->save();

		$this->save('sales/creditmemo', $creditmemo->getId());

		return $creditmemo;
	}

	/**
	 * Uninstall creditmemo
	 */
	public function uninstall()
	{
		Mage::getModel('sales/order_creditmemo')
			->load($this->getEntityId('sales/creditmemo'))
			->delete();
	}
}