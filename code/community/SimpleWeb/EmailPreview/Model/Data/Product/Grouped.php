<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Product_Grouped
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Create simple product
	 *
	 * @param array $data
	 * @return Mage_Catalog_Model_Product
	 */
	public function install($data = array())
	{
		/** @var Mage_Catalog_Model_Product $grouped */
		$grouped = $this->createProduct(array(
			'sku' => 'test_product_grouped',
			'name' => 'Test Product Grouped',
			'type_id' => 'grouped',
		));

		/** @var Mage_Catalog_Model_Product $child */
		$child = $this->createProduct(array(
			'sku' => 'test_product_grouped_child',
			'name' => 'Test Product Grouped - Child',
		));

		Mage::getModel('catalog/product_link_api')
			->assign("grouped", $grouped->getId(), $child->getId());

		$this->save('product/grouped', $grouped->getId());
		$this->save('product/grouped_child', $child->getId());

		return $grouped;
	}

	/**
	 * Uninstall grouped product
	 */
	public function uninstall()
	{
		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/grouped'))
			->delete();

		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/grouped_child'))
			->delete();
	}
}