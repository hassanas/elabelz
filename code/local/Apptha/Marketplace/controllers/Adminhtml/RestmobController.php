<?php

class Apptha_Marketplace_Adminhtml_RestmobController extends Mage_Adminhtml_Controller_Action
{

    const CATEGORY_MEDIA_PATH = "/media/catalog/category/";

    public function indexAction()
    {
        $debug = $this->getRequest()->getParam('debug');
        if($debug == 'elab'){
         phpinfo();
     }
     return;
 }

 public function allproductslistAction()
 {
    $fpcModel = Mage::getModel('fpccache/fpc');
    $fpcModel->setControllerObject($this);
    $cacheData = $fpcModel->getData();
    if (!empty($cacheData)) {
        header("Content-Type: application/json");
        echo $cacheData;
        die;
    }
    $ret_arr = array();
    $products = Mage::getSingleton('catalog/product')->getCollection();
    $products->addAttributeToFilter('status', array('eq' => 1))->addAttributeToFilter("visibility", array("eq" => 4))->addAttributeToSelect('id')->addAttributeToSelect('name');;
    $i = 0;
    foreach ($products as $product) {
        $ret_arr[$i]['id'] = $product->getId();
        $ret_arr[$i]['name'] = $product->getName();
        $ret_arr[$i]['sku'] = $product->getSku();
        $i++;
    }
    $fpcModel->setData($ret_arr);
    header("Content-Type: application/json");
    print_r(json_encode($ret_arr));
    die;

}
function categoriestreecronAction()
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
        $data = $this->getCategory($categoryId, $maxDepth, $store);
        if ($categoryId == "" || $categoryId == null) {
            fwrite($f, json_encode($data['children'][0]['children']));
        } else {
            fwrite($f, json_encode($data));
        }
        fclose($f);
        $i++;
    }
}
public function getCategory($categoryId = null, $maxDepth = -1, $currentDepth = 0)
{
    if ($categoryId === null || $categoryId === "tree") {
        $categoryId = $this->getRootCategoryId();
    }
    if (is_a($categoryId, "Mage_Catalog_Model_Category")) {
        $categoryObject = $categoryId;
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
     
    $categoryObject_new = Mage::getModel('catalog/category')
        ->setStoreId(Mage::app()->getStore()->getId())
        ->load($categoryObject->getData('entity_id'));
    $name = $categoryObject->getData('name');
    if (!empty($name)) {
        $name = Mage::helper('core')->__(Mage::helper('core')->htmlEscape($name));
        $category = array();
        $category['id'] = $categoryObject->getData('entity_id');
        $category['title'] = __(htmlspecialchars_decode($name));
        $category['name'] = __(htmlspecialchars_decode($name));
        $category['position'] = $categoryObject->getData('position');
        $category['level'] = $currentDepth;
        $category['MegamenubannerPosition'] = $categoryObject_new->getData('megamenubanner_position');
        $category['Megamenubanner'] = $categoryObject_new->getData('megamenubanner');
        $category['url_key'] = $categoryObject_new->getData('url_key');
        $category['url'] = $categoryObject_new->getUrl();
        $pcats = array();
        foreach ($categoryObject->getParentCategories() as $pc) {
            $pcats[] = __(htmlspecialchars_decode($pc->getName()));
        }
        $category['relation_tree'] = $pcats;

        if ($categoryObject->getImage()) {
            $imageUrl = self::CATEGORY_MEDIA_PATH . $categoryObject->getImage();
        } else {
            $imageUrl = '';
        }
        $category['image'] = $imageUrl;

        $category['include_in_menu'] = (bool)$categoryObject->getData('include_in_menu');

        $category['product_count'] = $productCollection->getSize();

        $category['default_sort_by'] = $categoryObject->getDefaultSortBy();

            // category children
        $category['children'] = array();
        if ($maxDepth < 0 || ($maxDepth > 0 && $maxDepth > $currentDepth)) {
            $children = $this->getChildrenCollectionForCategoryId($categoryObject->getData('entity_id'));
            if ($children->count() > 0) {
                foreach ($children as $child) {
                    $childRepresentation = $this->getCategory($child, $maxDepth, $currentDepth + 1);
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

public function getRootCategoryId()
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

    private function getChildrenCollectionForCategoryId($categoryId = null)
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

    public function categoriestreeAction()
    {
        $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
        $language = $language[0];
        $str = file_get_contents('mobile_json/categories_' . $language . '.json');
        header("Content-Type: application/json");
        echo $str;
        die;

    }

}