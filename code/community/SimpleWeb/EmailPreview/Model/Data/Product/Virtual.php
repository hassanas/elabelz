<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Product_Virtual
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Create virtual product
	 *
	 * @param array $data
	 * @return Mage_Catalog_Model_Product
	 */
	public function install($data = array())
	{
		$virtual = $this->createProduct(array(
			'sku' => 'test_product_virtual',
			'name' => 'Test Product Virtual',
			'type_id' => 'virtual',
			'setWeight' => false,
		));

		$this->save('product/virtual', $virtual->getId());

		return $virtual;
	}

	/**
	 * Uninstall virtual product
	 */
	public function uninstall()
	{
		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/virtual'))
			->delete();
	}
}