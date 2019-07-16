<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Product_Configurable
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Create attributes used in configurable products
	 *
	 * @return array
	 */
	protected function _createAttributes()
	{
		$attributeData = array(
			'is_global'             => '1',
			'frontend_input'        => 'select',
			'is_visible_on_front'   => '1',
			'is_configurable'       => '1',
			'backend_type'          => 'int',
			'is_user_defined'       => '1',
		);

		$entityTypeID = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		$attributeSet = Mage::getModel('eav/entity_attribute_set')
			->getCollection()
			->addFieldToFilter('entity_type_id', $entityTypeID)
			->getFirstItem();

		$attributes = array();

		for ($i = 1; $i <= 3; $i++)
		{
			$attribute = Mage::getModel('catalog/resource_eav_attribute');
			$attribute->addData($attributeData);
			$attribute->setFrontendLabel('Test Attribute #' . $i);
			$attribute->setAttributeCode('test_attr_' . $i);
			$attribute->setEntityTypeId($entityTypeID);
			$attribute->setAttributeSetId($attributeSet->getId());
			$attribute->save();

			$this->save('product/configurable_attribute_' . $i, $attribute->getId());

			$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
			$setup->addAttributeOption(array(
				'attribute_id' => $attribute->getId(),
				'value' => array(
					'test_value_1' => array('Test Option #1'),
				),
			));

			// Assign attribute to set group
			$setup->addAttributeToGroup(
				$entityTypeID,
				$attributeSet->getId(),
				'General',
				$attribute->getId()
			);

			$attributes[] = $attribute;
		}

		return $attributes;
	}

	/**
	 * Create configurable product
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _createConfigurableProduct()
	{
		return $this->createProduct(array(
			'sku' => 'test_product_configurable',
			'name' => 'Test Product Configurable',
			'type_id' => 'configurable',
			'weight' => 0,
			'stock_data' => array(
				'use_config_manage_stock' => 0,
				'manage_stock' => 1,
				'is_in_stock' => 1,
			),
		), false);
	}

	/**
	 * Return array of attribute ids
	 *
	 * @param array $attributes
	 * @return array
	 */
	protected function _getAttributeIds($attributes = array())
	{
		$attributeIds = array();

		foreach($attributes as $i => $attribute)
		{
			$attributeIds[] = $attribute->getId();
		}

		return $attributeIds;
	}

	/**
	 * Get attribute data
	 *
	 * @param array $attributes
	 * @return array
	 */
	protected function _getAttributeData($attributes = array())
	{
		$attributeData = array();

		foreach($attributes as $i => $attribute)
		{
			$attributeIds[] = $attribute->getId();

			$attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attribute->getAttributeCode());
			$options = $attribute->getSource()->getAllOptions(false);

			$attributeData[] = array(
				'id' => NULL,
				'label' => $attribute->getFrontendLabel(),
				'position' => NULL,
				'values' => array(
					'0' => array(
						'value_index' => $options[0]['value'],
						'label' => $options[0]['label'],
						'is_percent' => 0,
						'pricing_value' => '0',
						'attribute_id' => $attribute->getId()
					)
				),
				'attribute_id' => $attribute->getId(),
				'attribute_code' => $attribute->getAttributeCode(),
				'frontend_label' => $attribute->getFrontendLabel(),
				'html_id' => 'config_super_product_attribute_' . $i,
			);
		}

		return $attributeData;
	}

	/**
	 * Get child product data based on attribute data
	 *
	 * @param array $attributeData
	 * @return array
	 */
	protected function _getProductData($attributeData)
	{
		$simple = $this->createProduct(array(
			'sku' => 'test_product_configurable_child',
			'name' => 'Test Product Configurable - Child',
		), false);

		foreach($attributeData as $i => $data)
		{
			$simple->setData($data['attribute_code'], $data['values'][0]['value_index']);
		}

		$simple->save();

		$this->save('product/configurable_simple', $simple->getId());

		$configurableProductsData[$simple->getId()] = array();
		foreach($attributeData as $i => $data)
		{
			$simple->setData($data['attribute_code'], $data['values'][0]['value_index']);

			$configurableProductsData[$simple->getId()][$i] = array(
				'label' => $data['label'],
				'attribute_id' => $data['attribute_id'],
				'value_index' => $data['values'][0]['value_index'],
				'is_percent' => '0',
				'pricing_value' => '50',
			);
		}

		return $configurableProductsData;
	}

	/**
	 * Install configurable product
	 *
	 * @param array $data
	 * @return Mage_Core_Model_Abstract
	 */
	public function install($data = array())
	{
		$attributes = $this->_createAttributes();

		/** @var Mage_Catalog_Model_Product $configurable */
		$configurable = $this->_createConfigurableProduct();

		$attributeData = $this->_getAttributeData($attributes);
		$productData = $this->_getProductData($attributeData);
		$attributeIds = $this->_getAttributeIds($attributes);

		$configurable->getTypeInstance()->setUsedProductAttributeIds($attributeIds);
		$configurable->setConfigurableAttributesData($attributeData);
		$configurable->setConfigurableProductsData($productData);
		$configurable->setCanSaveConfigurableAttributes(true);

		$configurable->save();

		$this->save('product/configurable', $configurable->getId());

		return $configurable;
	}

	/**
	 * Uninstall configurable product
	 */
	public function uninstall()
	{
		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/configurable'))
			->delete();

		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/configurable_simple'))
			->delete();

		Mage::getModel('catalog/resource_eav_attribute')
			->load($this->getEntityId('product/configurable_attribute_1'))
			->delete();

		Mage::getModel('catalog/resource_eav_attribute')
			->load($this->getEntityId('product/configurable_attribute_2'))
			->delete();

		Mage::getModel('catalog/resource_eav_attribute')
			->load($this->getEntityId('product/configurable_attribute_3'))
			->delete();
	}
}