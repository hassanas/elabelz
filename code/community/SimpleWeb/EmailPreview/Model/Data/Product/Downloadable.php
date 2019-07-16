<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Data_Product_Downloadable
	extends SimpleWeb_EmailPreview_Model_Data_Abstract
{
	/**
	 * Create downloadable product
	 *
	 * @param array $data
	 * @return Mage_Catalog_Model_Product
	 */
	public function install($data = array())
	{
		/** @var Mage_Catalog_Model_Product $downloadable */
		$downloadable = $this->createProduct(array(
			'sku' => 'test_product_downloadable',
			'name' => 'Test Product Downloadable',
			'type_id' => 'downloadable',
			'links_title' => 'Download links',
			'links_purchased_separately' => 0,
		), false);

		$downloadable->setStockData(array(
			'use_config_manage_stock' => 0,
			'manage_stock' => 0
		));

		$downloadData = array('links');
		for($i = 1; $i <= 3; $i++)
		{
			$downloadData['link'][] = array(
				'is_delete' => '',
				'link_id' => 0,
				'title' => 'Download #' . $i,
				'price' => '',
				'number_of_downloads' => 0,
				'is_shareable' => '2',
				'sample' => array(
					'file' => '[]',
					'type' => 'url',
					'url' => 'http://www.example.com/files/sample.zip',
				),
				'file' => '[]',
				'type' => 'url',
				'link_url' => 'http://www.example.com/files/file.zip',
				'sort_order' => '',
			);
		}

		$downloadable
			->setDownloadableData($downloadData)
			->save();

		$this->save('product/downloadable', $downloadable->getId());

		return $downloadable;
	}

	/**
	 * Uninstall downloadable product
	 */
	public function uninstall()
	{
		Mage::getModel('catalog/product')
			->load($this->getEntityId('product/downloadable'))
			->delete();
	}
}