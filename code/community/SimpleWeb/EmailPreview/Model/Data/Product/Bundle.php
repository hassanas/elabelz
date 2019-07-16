<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Product_Bundle
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Create bundle product
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _createBundleProduct()
	{
		return $this->createProduct(array(
			'sku' => 'test_product_bundle',
			'name' => 'Test Product Bundle',
			'type_id' => 'bundle',
			'weight' => 0,
			'weight_type' => 0,
			'shipment_type' => 0,
			'price_type' => 0,
			'price_view' => 0,
			'stock_data' => array(
				'use_config_manage_stock' => 1,
				'manage_stock' => 1,
				'is_in_stock' => 1,
			),
		), false);
	}

	/**
	 * Get bundle product options
	 *
	 * @return array
	 */
	protected function _getBundleOptions()
	{
		return array(
			'0' => array(
				'title' => 'Option #1 - Select',
				'option_id' => '',
				'delete' => '',
				'type' => 'select',
				'required' => '1',
				'position' => '1',
			),
			'1' => array(
				'title' => 'Option #2 - Checkbox',
				'option_id' => '',
				'delete' => '',
				'type' => 'checkbox',
				'required' => '1',
				'position' => '1',
			),
			'2' => array(
				'title' => 'Option #3 - Multi',
				'option_id' => '',
				'delete' => '',
				'type' => 'multi',
				'required' => '1',
				'position' => '1',
			)
		);
	}

	/**
	 * Get bundle product selection
	 *
	 * @return array
	 */
	public function _getBundleSelection()
	{
		$simple1 = $this->createProduct(array(
			'sku' => 'test_product_bundle_simple_1',
			'name' => 'Test Product Bundle - Simple 1',
		));

		$simple2 = $this->createProduct(array(
			'sku' => 'test_product_bundle_simple_2',
			'name' => 'Test Product Bundle - Simple 2',
		));
		$simple3 = $this->createProduct(array(
			'sku' => 'test_product_bundle_simple_3',
			'name' => 'Test Product Bundle - Simple 3',
		));
		$simple4 = $this->createProduct(array(
			'sku' => 'test_product_bundle_simple_4',
			'name' => 'Test Product Bundle - Simple 4',
		));

		/**
		 * Save bundle item ids in config
		 */
		$this->save('product/bundle_simple_1', $simple1->getId());
		$this->save('product/bundle_simple_2', $simple2->getId());
		$this->save('product/bundle_simple_3', $simple3->getId());
		$this->save('product/bundle_simple_4', $simple4->getId());

		return array(
			'0' => array(
				'0' => array(
					'product_id' => $simple1->getId(),
					'delete' => '',
					'selection_price_value' => '10',
					'selection_price_type' => 0,
					'selection_qty' => 1,
					'selection_can_change_qty' => 0,
					'position' => 0,
					'is_default' => 1
				),
			),
			'1' => array(
				'0' => array(
					'product_id' => $simple2->getId(),
					'delete' => '',
					'selection_price_value' => '10',
					'selection_price_type' => 0,
					'selection_qty' => 1,
					'selection_can_change_qty' => 0,
					'position' => 0,
					'is_default' => 1
				),
			),
			'2' => array(
				'0' => array(
					'product_id' => $simple3->getId(),
					'delete' => '',
					'selection_price_value' => '10',
					'selection_price_type' => 0,
					'selection_qty' => 1,
					'selection_can_change_qty' => 0,
					'position' => 0,
					'is_default' => 1
				),
				'1' => array(
					'product_id' => $simple4->getId(),
					'delete' => '',
					'selection_price_value' => '10',
					'selection_price_type' => 0,
					'selection_qty' => 1,
					'selection_can_change_qty' => 0,
					'position' => 0,
					'is_default' => 1
				),
			),
		);
	}

	/**
	 * Install configurable product
	 *
	 * @param array $data
	 * @return Mage_Core_Model_Abstract
	 */
	public function install($data = array())
	{
		/** @var Mage_Catalog_Model_Product $bundle */
		$bundle = $this->_createBundleProduct();

		$bundleOptions = $this->_getBundleOptions();
		$bundleSelection = $this->_getBundleSelection();

		//flags for saving custom options/selections
		$bundle->setCanSaveCustomOptions(true);
		$bundle->setCanSaveBundleSelections(true);
		$bundle->setAffectBundleProductSelections(true);

		Mage::register('product', $bundle);

		//setting the bundle options and selection data
		$bundle->setBundleOptionsData($bundleOptions);
		$bundle->setBundleSelectionsData($bundleSelection);

		$bundle->save();

		$this->save('product/bundle', $bundle->getId());

		return $bundle;
	}

	/**
	 * Uninstall bundle product
	 */
	public function uninstall()
	{
		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/bundle'))
			->delete();

		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/bundle_simple_1'))
			->delete();

		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/bundle_simple_2'))
			->delete();

		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/bundle_simple_3'))
			->delete();

		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/bundle_simple_4'))
			->delete();
	}
}