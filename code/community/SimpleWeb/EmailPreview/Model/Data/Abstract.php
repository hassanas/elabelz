<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
abstract class SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * @var Mage_Core_Model_Store
	 */
	protected $_store;

	/**
	 * @var int
	 */
	protected $_storeId;

	/**
	 * @var int
	 */
	protected $_websiteId;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$storeId = Mage::app()
			->getWebsite(true)
			->getDefaultGroup()
			->getDefaultStoreId();

		$this->_store = Mage::app()->getStore($storeId);
		$this->_storeId = $this->_store->getId();
		$this->_websiteId = $this->_store->getWebsiteId();

		// Reload eav config singleton model before each product saving
		Mage::unregister('_singleton/eav/config');
	}

	/**
	 * Default data installation function
	 *
	 * @return mixed
	 */
	abstract public function install($data = array());

	/**
	 * Uninstall data
	 *
	 * @return mixed
	 */
	abstract public function uninstall();

	/**
	 * Save entity ID in configuration
	 *
	 * @param string $type
	 * @param int $id
	 * @return Mage_Core_Model_Config
	 */
	public function save($type, $id)
	{
		$path = 'simpleweb_emailpreview/' . $type;
		return Mage::getConfig()->saveConfig($path, $id);
	}

	/**
	 * Return entity ID by type and delete from configuration
	 *
	 * @param string $type
	 * @param bool $delete
	 * @return int
	 */
	public function getEntityId($type, $delete = true)
	{
		$path = 'simpleweb_emailpreview/' . $type;

		$value = Mage::getStoreConfig($path);

		// Delete from database?
		if ($delete)
		{
			$this->delete($type);
		}

		return $value;
	}

	/**
	 * Delete entry from configuration
	 *
	 * @param string $type
	 * @return Mage_Core_Model_Config
	 */
	public function delete($type)
	{
		$path = 'simpleweb_emailpreview/' . $type;
		return Mage::getConfig()->deleteConfig($path);
	}

	/**
	 * Create simple product
	 *
	 * @param array $data
	 * @param bool $save
	 * @return Mage_Catalog_Model_Product
	 */
	public function createProduct($data = array(), $save = true)
	{
		/* @var $product Mage_Catalog_Model_Product */
		$product = Mage::getModel('catalog/product')
			->setStoreId($this->_storeId)
			->setWebsiteIds(array($this->_websiteId))
			->setAttributeSetId(4)
			->setTypeId('simple')
			->setCreatedAt(strtotime('now'))
			->setUpdatedAt(strtotime('now'))
			->setWeight(1)
			->setPrice(100)
			->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
			->setTaxClassId(0)
			->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
			->setCountryOfManufacture('US')
			->setDescription('This is a long description')
			->setShortDescription('This is a short description')
			->setMediaGallery (array('images' => array(), 'values'=> array()))
			->setStockData(array(
				'use_config_manage_stock' => 0,
				'manage_stock' => 1,
				'min_sale_qty' => 1,
				'max_sale_qty' => 2,
				'is_in_stock' => 1,
				'qty' => 1000,
			));

		// Set custom data
		if (!empty($data))
		{
			$product->addData($data);
		}

		// Return product object
		return $save ? $product->save() : $product;
	}
}