<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Product_Simple
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
		$simple = $this->createProduct(array(
			'sku' => 'test_product_simple',
			'name' => 'Test Product Simple',
		));

		// Save product ID
		$this->save('product/simple', $simple->getId());

		return $simple;
	}

	/**
	 * Uninstall simple product
	 */
	public function uninstall()
	{
		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/simple'))
			->delete();
	}
}