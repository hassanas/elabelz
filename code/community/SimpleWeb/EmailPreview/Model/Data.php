<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data
{
	/**
	 * Install test data
	 */
	public function install()
	{
		$storeId = Mage::app()
			->getWebsite(true)
			->getDefaultGroup()
			->getDefaultStoreId();

		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

		$products = array(
			'simple'        => Mage::getModel('simpleweb_emailpreview/data_product_simple')->install(),
			'configurable'  => Mage::getModel('simpleweb_emailpreview/data_product_configurable')->install(),
			'grouped'       => Mage::getModel('simpleweb_emailpreview/data_product_grouped')->install(),
			'virtual'       => Mage::getModel('simpleweb_emailpreview/data_product_virtual')->install(),
			'bundle'        => Mage::getModel('simpleweb_emailpreview/data_product_bundle')->install(),
			'downloadable'  => Mage::getModel('simpleweb_emailpreview/data_product_downloadable')->install(),
		);

		$customer = Mage::getModel('simpleweb_emailpreview/data_customer')->install(array(
			'products' => $products,
		));

		$order = Mage::getModel('simpleweb_emailpreview/data_sales_order')->install(array(
			'products' => $products,
			'customer' => $customer,
		));

		$invoice = Mage::getModel('simpleweb_emailpreview/data_sales_invoice')->install(array(
			'order' => $order,
		));

		$shipment = Mage::getModel('simpleweb_emailpreview/data_sales_shipment')->install(array(
			'order' => $order,
		));

		$creditmemo = Mage::getModel('simpleweb_emailpreview/data_sales_creditmemo')->install(array(
			'order' => $order,
		));

		Mage::getConfig()->saveConfig('simpleweb_emailpreview/installed', 1);

		// Stop store emulation process
		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
	}

	/**
	 * Uninstall test data
	 */
	public function uninstall()
	{
		// Delete customer
		Mage::getModel('simpleweb_emailpreview/data_customer')->uninstall();

		// Delete products
		Mage::getModel('simpleweb_emailpreview/data_product_simple')->uninstall();
		Mage::getModel('simpleweb_emailpreview/data_product_configurable')->uninstall();
		Mage::getModel('simpleweb_emailpreview/data_product_grouped')->uninstall();
		Mage::getModel('simpleweb_emailpreview/data_product_virtual')->uninstall();
		Mage::getModel('simpleweb_emailpreview/data_product_bundle')->uninstall();
		Mage::getModel('simpleweb_emailpreview/data_product_downloadable')->uninstall();

		// Delete quote and order
		Mage::getModel('simpleweb_emailpreview/data_sales_order')->uninstall();

		// Delete invoice
		Mage::getModel('simpleweb_emailpreview/data_sales_invoice')->uninstall();

		// Delete shipment
		Mage::getModel('simpleweb_emailpreview/data_sales_shipment')->uninstall();

		// Delete creditmemo
		Mage::getModel('simpleweb_emailpreview/data_sales_creditmemo')->uninstall();

		Mage::getConfig()->deleteConfig('simpleweb_emailpreview/installed');
	}
}