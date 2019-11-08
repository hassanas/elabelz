<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


require_once('../app/Mage.php'); //Path to Magento
umask(0);
Mage::app();

$collection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('*');
if (isset($_GET['visible'])) {
	//only visible products
	$collection->addAttributeToFilter('visibility', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH));
}

$keysArray = array();
$duplicateKeys = array();
foreach ($collection as $item) {
	if ($item->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
		$duplicateKeys[$item->getData('url_key')][$item->getSku()] =  $item->getData('url_key') . "    " . 'NOT_VISIBLE'; //russian magento can't get url key using following $item->getUrlKey()
	} else {
		$duplicateKeys[$item->getData('url_key')][$item->getSku()] =  $item->getData('url_key');
	}
}

foreach ($duplicateKeys as $key => $duplicate) {
	if (count($duplicate) <= 1) {
		unset($duplicateKeys[$key]);
	}
}

echo "<pre>";
print_r($duplicateKeys);
echo "</pre>";