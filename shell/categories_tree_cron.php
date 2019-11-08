<?php
/**
 * User: Naveed
 * Date: 6/21/17
 * Time: 11:38 PM
 */
/**
 * This file shall run every day to update categories tree json files
 */
require_once __DIR__ . '/../app/Mage.php';
error_reporting(E_ERROR);
ini_set('display_errors', '1');
Mage::app();
categoriestreecron();

function categoriestreecron()
{
	$languages = array("en","ar");
	$locales = array("en_US","ar_SA");
	$i=0;
	foreach($languages as $language){
		$locale = $locales[$i];
		Mage::app()->getLocale()->setLocaleCode($locale);
		Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
		$request = Mage::app()->getRequest();
		$f = fopen('mobile_json/categories_' . $language . '.json', 'w+');
		$categoryId = $request->getParam('id');
		$maxDepth = $request->getParam('max_depth');
		if ($maxDepth === null) {
			$maxDepth = -1;
		}
		$categories = null;
		$data = getCategory($categoryId, $maxDepth, $store);
		if ($categoryId == "" || $categoryId == null) {
			fwrite($f, json_encode($data['children'][0]['children']));
		} else {
			fwrite($f, json_encode($data));
		}
		fclose($f);
		$i++;
	}
}

function getCategory($categoryId = null, $maxDepth = -1, $currentDepth = 0)
{
	if ($categoryId === null || $categoryId === "tree") {
		$categoryId = getRootCategoryId();
	}
	if (is_a($categoryId, "Mage_Catalog_Model_Category")) {
		$categoryObject = Mage::getModel('catalog/category')
        ->setStoreId(Mage::app()->getStore()->getId())
        ->load($categoryId->getId());
	} else {
		$categoryObject = Mage::getModel('catalog/category')
			->setStoreId(Mage::app()->getStore()->getId())
			->load($categoryId);
	}
	$productCollection = $categoryObject->getProductCollection()
		->addAttributeToFilter('visibility', array('in' => array(
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
		);

	$name = $categoryObject->getData('name');
	if (!empty($name)) {
		$name = Mage::helper('core')->__(Mage::helper('core')->htmlEscape($name));
		$category = array();
		$category['id'] = $categoryObject->getData('entity_id');
		$category['name'] = __(htmlspecialchars_decode($name));
		$category['title'] = __(htmlspecialchars_decode($name));
		$category['position'] = $categoryObject->getData('position');
		$category['level'] = $currentDepth;
		$pcats = array();
		foreach ($categoryObject->getParentCategories() as $pc) {
			$pcats[] = __(htmlspecialchars_decode($pc->getName()));
		}
		$category['relation_tree'] = $pcats;

		$imageUrl = '';
		$category['image'] = $imageUrl;

		$category['include_in_menu'] = (bool)$categoryObject->getData('include_in_menu');

		$category['product_count'] = $productCollection->getSize();

		$category['default_sort_by'] = $categoryObject->getDefaultSortBy();

		$category['MegamenubannerPosition'] = $categoryObject->getData('megamenubanner_position');
        
        $category['Megamenubanner'] = $categoryObject->getData('megamenubanner');
        
        if (strpos($categoryObject->getData('url_key'), '-sale') !== false) {
        $category['url_key'] = "sale/".$categoryObject->getData('url_key');
        }
        else{
          $category['url_key'] = $categoryObject->getData('url_key');  
        }

		// category children
		$category['children'] = array();
		if ($maxDepth < 0 || ($maxDepth > 0 && $maxDepth > $currentDepth)) {
			$children = getChildrenCollectionForCategoryId($categoryObject->getData('entity_id'));
			if ($children->count() > 0) {
				foreach ($children as $child) {
					$childRepresentation = getCategory($child, $maxDepth, $currentDepth + 1);
					if (is_array($childRepresentation)) {
						array_push($category['children'], $childRepresentation);
					}
				}
			}
		}

		return $category;
	} else {
		return false;
	}
}
function getRootCategoryId()
{
	$categories = Mage::getModel('catalog/category')->getCollection();
	$categIds = $categories->getAllIds();
	asort($categIds);
	foreach ($categIds as $k => $catId) {
		$category = Mage::getModel('catalog/category')->load($catId);
		if ($category->name) {
			return $catId;
		}
	}
}
function getChildrenCollectionForCategoryId($categoryId = null)
{
	if ($categoryId === null) {
		return null;
	}


	$children = Mage::getModel('catalog/category')->getCollection()->setStoreId(Mage::app()->getStore()->getId());
	$children->addAttributeToSelect(array('entity_id', 'name', 'image', 'level', 'include_in_menu', 'position'))// Only get nescecary attributes from the table
	->addAttributeToFilter('parent_id', $categoryId)
		->addAttributeToSort('position')
		->addAttributeToFilter('is_active', 1);


	return $children;
}