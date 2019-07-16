<?php
/**
 * Block class for custom requirement
 * on error page, A disabled product or category
 * should set datalayer pageTitle with PDP or PLP respectively
 * 404 only if no record exists
 * @author RT <rafay.tahir@progos.org>
 */
class Progos_Infotrust_Block_Infotrusterror extends Mage_Core_Block_Template
{
	/**
	 * check if product exists
	 * @param int $storeId
	 * @return bool $isExistProduct
	**/
	public function isExistProduct($storeId = null)
	{
		$urlKey = '';
		$isExistProduct = false;
		if ($storeId == null) {
			$storeId = Mage::app()->getStore()->getId();
		}
		$urlKey = $this->getUrlKey();
		if ($urlKey == '') {
			return false;
		}
		$prodSuffix = Mage::helper('catalog/product')->getProductUrlSuffix();
		$urlKeySuffix = $this->getUrlKey(true);
		//check if the url is product page
		if (strpos($urlKeySuffix, $prodSuffix) !== FALSE) {
			$product = Mage::getModel('catalog/product')->getCollection()
				->addStoreFilter($storeId)
				->addAttributeToFilter('url_key', ['eq' => $urlKey])
				->addAttributeToFilter('type_id', 'configurable')
				->setPageSize(1)
				->load()
				->getFirstItem();
			if (count($product->getData()) > 0) {
				$isExistProduct = true;
			}
		}

		return $isExistProduct;
	}
	/**
	 * check if category exists
	 * @param int $storeId
	 * @return bool $isExistCategory
	**/
	public function isExistCategory()
	{
		$isExistCategory = false;
		$urlKey = $this->getUrlKey();
		if ($urlKey == '') {
			return false;
		}
		$urlKeySuffix = $this->getUrlKey(true);
		// $categorySuffix = Mage::helper('catalog/category')->getCategoryUrlSuffix();
		$category = Mage::getModel('catalog/category')->getCollection()
			->addAttributeToFilter('url_key', ['eq' => $urlKey])
			->setCurPage(1)
			->setPageSize(1)
			->load()
			->getFirstItem();
		if (count($category->getData()) > 0) {
			$isExistCategory = true;
		}

		return $isExistCategory;
	}
	/**
	 * part url for further execution
	 * @param int $storeId
	 * @return mix 
	**/
	public function getUrlKey($withSuffix = false)
	{
		$currentUrl = Mage::helper('core/url')->getCurrentUrl();
		$url = Mage::getSingleton('core/url')->parseUrl($currentUrl);
		$path = rtrim($url->getPath(), '/'); //we don't need trailing slashes
		$urlParts = explode('/', $path); 
		$urlKey = '';
		if (!empty($urlParts)) {
			reset($urlParts);
			$urlKeySuffix = end($urlParts);
			if ($withSuffix) {
				return $urlKeySuffix;
			}
			$prodSuffix = Mage::helper('catalog/product')->getProductUrlSuffix();
			$categorySuffix = Mage::helper('catalog/category')->getCategoryUrlSuffix();
			$urlKey = str_replace([$prodSuffix, $categorySuffix], '', $urlKeySuffix);
		}
		return $urlKey;
	}
}