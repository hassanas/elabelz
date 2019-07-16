<?php

class Progos_Emapi_IndexController extends Mage_Core_Controller_Front_Action
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
        $data = $this->getCategory($categoryId, $maxDepth, $currentDepth, $language);
        if ($categoryId == "" || $categoryId == null) {
            fwrite($f, json_encode($data['children'][0]['children']));
        } else {
            fwrite($f, json_encode($data));
        }
        fclose($f);
        $i++;
    }
}
public function getCategory($categoryId = null, $maxDepth = -1, $currentDepth = 0,$language)
{
    
    if($language == "ar"){
        $store_id = 2;
    }
    elseif($language == "en"){
        $store_id = 1;
    }
    
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
    
    $categoryObject_name = Mage::getModel('catalog/category')
    ->setStoreId($store_id)
    ->load($categoryObject->getData('entity_id'));

    $name = $categoryObject->getData('name');
    if (!empty($name)) {
        $name_new = $categoryObject_name->getData('name');
        $name = Mage::helper('core')->__(Mage::helper('core')->htmlEscape($name_new));
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
                    $childRepresentation = $this->getCategory($child, $maxDepth, $currentDepth + 1,$language);
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
    function brandsforstudioAction()
    {
        $categoryid = (int)$this->getRequest()->getParam('cid');
        if ($categoryid == "" || $categoryid == null) {
            $categoryid = Mage::app()->getStore()->getRootCategoryId();
        }
        $store = Mage::app()->getStore()->getId();
        $array = array('name');
        $layer = Mage::getSingleton('catalog/layer');
        if ($categoryid) {
            $layer->setCurrentCategory($categoryid);
        }
        $productCollection = $layer->getProductCollection()
            ->addAttributeToSelect('manufacturer')
            ->addAttributeToFilter('manufacturer', array('neq'=>""))
            ->addAttributeToFilter('status', array('in'=>Mage::getSingleton('catalog/product_status')->getVisibleStatusIds()))
            ->addAttributeToFilter('visibility', array('in'=>Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds()));
        $manufacturer = array_column($productCollection->getData(), 'manufacturer');
        $manufacturer = array_unique($manufacturer);
        $brandData = Mage::getSingleton('shopbybrand/brand')->getCollection()
            ->setStoreId($store, $array)
            ->setOrder('position_brand','DESC')
            ->setOrder('name','ASC')
            ->addFieldToSelect ( '*' )
            ->addFieldToFilter('name', array('neq' => ''))
            ->addFieldToFilter('status',array('eq'=>1))
            ->addFieldToFilter ( 'option_id', array ('in' => $manufacturer));
        $j = 0;
        $data = array();
        foreach ($brandData as $brand) {
            $data[$j]['id'] = $brand->getData('option_id');
            $data[$j]['name'] = $brand->getData('name');
            $data[$j]['url_key'] = $brand->getData('url_key');
            $j++;
        }
        header("Content-Type: application/json");
        print_r(json_encode($data));
        die;
    }

    /*
     * function to get all active store
     */
    public function getAllStoresAction(){
        $stores = Mage::app()->getStores();
        $response = array();
        $i = 0;
        $j = 0;
        foreach ($stores as $store) {
            $store_arr = explode('_',$store->getCode());
            if($store_arr[0] == "en") {
                $response['en'][$i]['name'] = $store->getName();
                $response['en'][$i]['code'] = $store->getCode();
                $response['en'][$i]['country_code'] = strtoupper($store_arr[1]);
                $i++;
            }else {
                $response['ar'][$j]['name'] = $store->getName();
                $response['ar'][$j]['code'] = $store->getCode();
                $response['ar'][$j]['country_code'] = strtoupper($store_arr[1]);
                $j++;
            }
        }
        header("Content-Type: application/json");
        print_r(json_encode($response));
        die;
    }

    /*
     * Function to fetch allowed countries based on store
     */
    public function allowedCountriesAction()
    {

        $currentCode = Mage::app()->getStore()->getCode();;
        $currentCodeArr = explode("_",$currentCode);
        $currentCode = $currentCodeArr[0];
        $stores = Mage::app()->getStores();
        $response = array("status" => "0", "countries" => array());
        $countries = array();
        foreach ($stores as $store) {
            $store_arr = explode('_',$store->getCode());
            $_countries = Mage::getResourceModel('directory/country_collection')
                ->loadByStore($store)
                ->toOptionArray(false);
            $allowed = explode(',',Mage::getStoreConfig('general/country/allow'));
            if (count($_countries) > 0) {
                $i = 0;
                $j = 0;
                foreach ($_countries as $_country) {
                    if(!in_array($_country['value'],$allowed)){
                        continue;
                    }
                    if ($store_arr[0] == "en" && $currentCode == "en") {
                        $countries[$store->getCode()][$i]['shortCode'] = $_country['value'];
                        $countries[$store->getCode()][$i]['countryName'] = $_country['label'];
                        $i++;
                    } else if ($store_arr[0] == "ar" && $currentCode == "ar") {
                        $countries[$store->getCode()][$j]['shortCode'] = $_country['value'];
                        $countries[$store->getCode()][$j]['countryName'] = $_country['label'];
                        $j++;
                    }
                }
            }
        }
        if(!empty($countries)) {
            $response = array("status" => "1", "countries" => $countries);
        }
        header("Content-Type: application/json");
        print_r(json_encode($response));
        die;
    }

    /*
     * Function to fetch allowed countries based on store
     */
    public function allowedCountriesAndRegionsAction()
    {

        $currentCode = Mage::app()->getStore()->getCode();;
        $currentCodeArr = explode("_",$currentCode);
        $currentCode = $currentCodeArr[0];
        $stores = Mage::app()->getStores();
        $response = array("status" => "0", "countries" => array());
        $countries = array();
        foreach ($stores as $store) {
            $store_arr = explode('_',$store->getCode());
            $_countries = Mage::getResourceModel('directory/country_collection')
                ->loadByStore($store)
                ->toOptionArray(false);
            $allowed = explode(',',Mage::getStoreConfig('general/country/allow'));
            if (count($_countries) > 0) {
                $j = 0;
                foreach ($_countries as $_country) {
                    if(!in_array($_country['value'],$allowed)){
                        continue;
                    }
                    $countries[$j]['name'] = $_country['label'];
                    $countries[$j]['short_name'] = $_country['value'];
                    $regionsCollection = Mage::getResourceModel('directory/region_collection')->addCountryFilter($_country['value'])->load();
                    $i=0;
                    $states = array();
                    foreach($regionsCollection as $region){
                       if(trim($region->getName()) != "" && trim($region->getName()) != null) {
                           $states[$i]['name'] = $region->getName();
                           $states[$i]['id'] = $region->getRegionId();
                       }else{
                           $states[$i]['name'] = $region->getDefaultName();
                           $states[$i]['id'] = $region->getRegionId();
                       }
                       $i++;
                    }
                    $countries[$j]['states'] = $states;
                    $j++;
                }
            }
        }
        if(!empty($countries)) {
            $response = array("status" => "1", "countries" => $countries);
        }
        header("Content-Type: application/json");
        print_r(json_encode($response));
        die;
    }

    /*
     * Function to fetch brands from cms blocks
     */
    public function getTextBetweenAll($start, $end ,$string)
    {
        $pattern = "/$start(.*?)$end/s";
        preg_match_all($pattern, $string, $matches);
        return $matches[1];
    }
    public function getTextBetween($start, $end ,$string)
    {
        $pattern = "/$start(.*?)$end/s";
        $matchCount = preg_match($pattern, $string, $matches);
        if($matchCount){
            return $matches[1];
        }else{
            return 0;
        }

    }
    public function getSelectedBrandsAction()
    {
        $type = $this->getRequest()->getParam('type');
        if($type=='men'){
            $id = 'mega-menu-desktop-men';
        }elseif($type=='women'){
            $id = 'mega-menu-desktop-women';
        }elseif($type=='kids'){
            $id = 'mega-menu-desktop-kids';
        }else{
            die;
        }

        $blockCollection = Mage::getModel('cms/block')->getCollection()
            ->addFieldToFilter('identifier', $id);
        $brands = array();
        foreach ($blockCollection as $block) {
            if($block->getTitle() == $id.'-english'){
                $block_content = $this->getTextBetweenAll("<ul class=\"multi-column-dropdown\"","<\/ul>",$block->getContent());
                $lis_arr = $this->getTextBetweenAll("<li","<\/li>",$block_content[1]);
                $i=0;
                foreach ($lis_arr as $li_single){
                    preg_match_all('/<a .*?>(.*?)<\/a>/',$li_single,$matches);
                    $data_id = $this->getTextBetween("data-id=\"","\"",$li_single);
                    $brands['en'][$i]['name'] = $matches[1][0];
                    $brands['en'][$i]['id'] = $data_id;
                    $i++;
                }
                $brands_men_en = $brands;
            }else{
                $block_content = $this->getTextBetweenAll("<ul class=\"multi-column-dropdown\"","<\/ul>",$block->getContent());
                $lis_arr = $this->getTextBetweenAll("<li","<\/li>",$block_content[1]);
                $i=0;
                foreach ($lis_arr as $li_single){
                    preg_match_all('/<a .*?>(.*?)<\/a>/',$li_single,$matches);
                    $data_id = $this->getTextBetween("data-id=\"","\"",$li_single);
                    $brands['ar'][$i]['name'] = $matches[1][0];
                    $brands['ar'][$i]['id'] = $data_id;
                    $i++;
                }
                $brands_men_ar = $brands;
            }
        }
        $final_arr = array_merge($brands_men_en,$brands_men_ar);
        header("Content-Type: application/json");
        print_r(json_encode($final_arr));
        die;
    }
}