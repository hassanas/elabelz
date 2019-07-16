<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Sales_Order
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Add simple product to cart
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Mage_Catalog_Model_Product $product
	 */
	protected function _addSimpleProductToCart($quote, $product)
	{
		$quote->addProduct(
			Mage::getModel('catalog/product')->load($product->getId()),
			new Varien_Object(array('qty' => 1))
		);
	}

	/**
	 * Add virtual product to cart
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Mage_Catalog_Model_Product $product
	 */
	protected function _addVirtualProductToCart($quote, $product)
	{
		$quote->addProduct(
			Mage::getModel('catalog/product')->load($product->getId()),
			new Varien_Object(array('qty' => 1))
		);
	}

	/**
	 * Add downloadable product to cart
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Mage_Catalog_Model_Product $product
	 */
	protected function _addDownloadableProductToCart($quote, $product)
	{
		$links = Mage::getModel('downloadable/product_type')
			->getLinks($product);

		$input = array('qty' => 1, 'links' => array());
		foreach($links as $link)
		{
			$input['links'][] = $link->getLinkId();
		}

		$quote->addProduct(
			Mage::getModel('catalog/product')->load($product->getId()),
			new Varien_Object($input)
		);
	}

	/**
	 * Add configurable product to cart
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Mage_Catalog_Model_Product $product
	 */
	protected function _addConfigurableProductToCart($quote, $product)
	{
		$productData = $product->getConfigurableProductsData();

		$options = array(
			'product' => $product->getId(),
			'super_attribute' => array(),
			'qty' => 1,
		);

		foreach($productData as $id => $data)
		{
			foreach($data as $option)
			{
				$options['super_attribute'][$option['attribute_id']]
					= $option['value_index'];
			}
		}

		$quote->addProduct(
			Mage::getModel('catalog/product')->load($product->getId()),
			new Varien_Object($options)
		);
	}

	/**
	 * Add bundle product to cart
	 *
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Mage_Catalog_Model_Product $product
	 */
	protected function _addBundleProductToCart($quote, $product)
	{
		$params = array(
			'product' => $product->getId(),
			'related_product' => null,
			'bundle_option' => array(),
			'qty' => 1,
		);

		$optionCollection = $product->getTypeInstance()->getOptionsCollection();
		$selectionCollection = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
		$options = $optionCollection->appendSelections($selectionCollection);

		foreach($options as $option)
		{
			$_selections = $option->getSelections();
			foreach($_selections as $selection)
			{
				$params['bundle_option'][$option->getOptionId()][] = $selection->getSelectionId();
			}
		}

		$quote->addProduct(
			Mage::getModel('catalog/product')->load($product->getId()),
			new Varien_Object($params)
		);
	}

	/**
	 * Create quote for customer
	 *
	 * @param array $data
	 * @return Mage_Sales_Model_Quote
	 */
	protected function _createQuote($data)
	{
		/** @var Mage_Customer_Model_Customer $customer */
		$customer = $data['customer'];
		$products = $data['products'];

		/** @var Mage_Sales_Model_Quote $quote */
		$quote = Mage::getModel('sales/quote');

		$quote->setStore($this->_store);
		$quote->assignCustomer($customer);

		$quote->getShippingAddress()->importCustomerAddress($customer->getPrimaryShippingAddress());
		$quote->getBillingAddress()->importCustomerAddress($customer->getPrimaryBillingAddress());

		// Add Products to cart
		$this->_addSimpleProductToCart($quote, $products['simple']);
		$this->_addVirtualProductToCart($quote, $products['virtual']);
		$this->_addDownloadableProductToCart($quote, $products['downloadable']);
		$this->_addConfigurableProductToCart($quote, $products['configurable']);
		$this->_addBundleProductToCart($quote, $products['bundle']);

		$quote->getShippingAddress()->setCollectShippingRates(true)
			->collectShippingRates()
			->setShippingMethod('flatrate_flatrate')
			->setPaymentMethod('checkmo');

		$quote->getPayment()->importData(array('method' => 'checkmo'));
		$quote->collectTotals()->save();

		$this->save('sales/quote', $quote->getId());

		return $quote;
	}

	/**
	 * Create new order
	 *
	 * @param array $data
	 * @return Mage_Sales_Model_Order
	 */
	public function install($data = array())
	{
		$quote = $this->_createQuote($data);

		$service = Mage::getModel('sales/service_quote', $quote);
		$service->submitAll();

		$order = $service->getOrder();

		$this->save('sales/order', $order->getId());

		return $order;
	}

	/**
	 * Uninstall order
	 */
	public function uninstall()
	{
		Mage::getModel('sales/quote')
			->loadByIdWithoutStore($this->getEntityId('sales/quote'))
			->delete();

		Mage::getModel('sales/order')
			->load($this->getEntityId('sales/order'))
			->delete();
	}
}