<?php
/**
 *
 * @category       Progos
 * @package        Progos_Emapi
 * @copyright      Progos Tech (c) 2018
 * @Author         Hassan Ali Shahzad
 * @date           03-03-2017 03:03 AM
 *
 */

class Progos_Emapi_Model_Filters
{

    protected $_prefix;

    public function __construct()
    {
        $this->_prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix');
    }

    /**
     * This function will return filters to klevu search
     * @param $dependencyObject
     * @return mixed
     */

    public function klevuSearchFilters($dependencyObject)
    {

        $page = ($dependencyObject->getRequest()->getParam('page')) ? (integer)$dependencyObject->getRequest()->getParam('page') : 1;
        $limit = ($dependencyObject->getRequest()->getParam('limit')) ? (integer)$dependencyObject->getRequest()->getParam('limit') : 20;
        $search = $dependencyObject->getRequest()->getParam('s');
        $filters = array();
        if (!empty($dependencyObject->getRequest()->getParam('cid'))) $filters['category'] = $dependencyObject->getRequest()->getParam('cid');// $categoryid is actually string
        if (!empty($dependencyObject->getRequest()->getParam('manufacturer'))) $filters['manufacturer'] = $dependencyObject->getRequest()->getParam('manufacturer');
        if (!empty($dependencyObject->getRequest()->getParam('size'))) $filters['size'] = $dependencyObject->getRequest()->getParam('size');
        if (!empty($dependencyObject->getRequest()->getParam('color'))) $filters['color'] = $dependencyObject->getRequest()->getParam('color');
        if (!empty($dependencyObject->getRequest()->getParam('price'))) $filters['klevu_price'] = $dependencyObject->getRequest()->getParam('price');
        $sort = $dependencyObject->getRequest()->getParam('sort');
        if ($sort == 3)
            $sort = 'lth';
        elseif ($sort == 4)
            $sort = 'htl';
        else
            $sort = 'rel';

        $page = ($page - 1) * $limit;
        $args = ['term' => $search, 'page' => $page, 'limit' => $limit, 'sort' => $sort];
        if (!empty($filters)) $args['filters'] = $filters;

        return $this->_getHelper('klevusearch')->filterKlevuLayeredNavKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearchfilters')->getFilters($args));
    }

    /**
     * Filters old implementations
     * @param $dependencyObject
     * @return mixed
     */

    public function oldFilters($dependencyObject)
    {
        $categoryid = (int)$dependencyObject->getRequest()->getParam('cid');
        $designid = $dependencyObject->getRequest()->getParam('manufacturer');
        $sizeid = $dependencyObject->getRequest()->getParam('size');
        $colorid = $dependencyObject->getRequest()->getParam('color');

        $categoryidArr = explode(',,', $categoryid);
        $categoryid = $categoryidArr[0];

        if ($designid == "" && $categoryidArr[1] != "") {
            $designid = $categoryidArr[1];
        }

        if ($categoryid == "" || $categoryid == null || $categoryid == 0) {
            $categoryid = Mage::app()->getStore()->getRootCategoryId();
        }

        if (Mage::getStoreConfig('api/emapi/filterLogs')) {
            Mage::log('filter design = ' . $designid, Null, 'filters_debug.log');
            Mage::log('filter size = ' . $sizeid, Null, 'filters_debug.log');
            Mage::log('filter color = ' . $colorid, Null, 'filters_debug.log');
        }

        $storeId = Mage::app()->getStore()->getId();
        $attrs["design"]['label'] = __("Brands");
        $attributeCode = Mage::getStoreConfig('shopbybrand/general/attribute_code', $storeId);
        $attrCode = $attributeCode ? $attributeCode : 'manufacturer';
        $attrs["design"]['code'] = $attrCode;
        $attrs["design"]['sort'] = 1;
        $attrs["design"]['options'] = array();

        $attrs["color"]['label'] = __("Color");
        $attrs["color"]['code'] = __("color");
        $attrs["color"]['sort'] = 2;
        $attrs["color"]['options'] = array();

        $attrs["size"]['label'] = __("Size");
        $attrs["size"]['code'] = __("size");
        $attrs["size"]['sort'] = 3;
        $attrs["size"]['options'] = array();

        $attrs["price"]['label'] = __("Price");
        $attrs["price"]['code'] = __("price");
        $attrs["price"]['sort'] = 4;
        $attrs["price"]['options'] = array();


        $layer = Mage::getSingleton("catalog/layer");
        $layer->setCurrentCategory($categoryid);
        $attributes = $layer->getFilterableAttributes();

        $i = 0;
        $attributeCollection = array();
        foreach ($attributes as $attribute) {
            if ($attribute->getAttributeCode() == 'price') {
                $filterBlockName = 'catalog/layer_filter_price';
            } elseif ($attribute->getBackendType() == 'decimal') {
                $filterBlockName = 'catalog/layer_filter_decimal';
            } else {
                $filterBlockName = 'catalog/layer_filter_attribute';
            }
            $result = Mage::app()->getLayout()->createBlock($filterBlockName)->setLayer($layer)->setAttributeModel($attribute)->init();
            $attributeCollection[$i]['Code'] = $attribute->getAttributeCode();
            $attributeCollection[$i]['Label'] = $attribute->getStoreLabel();
            $j = 0;
            foreach ($result->getItems() as $option) {

                if ($attribute->getAttributeCode() == 'price') {
                    $attrs["price"]['options'][$j]['label'] = strip_tags($option->getLabel());
                    $attrs["price"]['options'][$j]['count'] = $option->getCount();
                    $attrs["price"]['options'][$j]['value'] = $option->getValue();
                } elseif ($attribute->getAttributeCode() == 'color') {
                    $attrs["color"]['options'][$j]['label'] = $option->getLabel();
                    $attrs["color"]['options'][$j]['count'] = $option->getCount();
                    $attrs["color"]['options'][$j]['value'] = $option->getoptionId();
                } elseif ($attribute->getAttributeCode() == 'size') {
                    $attrs["size"]['options'][$j]['label'] = $option->getLabel();
                    $attrs["size"]['options'][$j]['count'] = $option->getCount();
                    $attrs["size"]['options'][$j]['value'] = $option->getoptionId();
                } elseif ($attribute->getAttributeCode() == 'manufacturer') {
                    $attrs["design"]['options'][$j]['label'] = $option->getLabel();
                    $attrs["design"]['options'][$j]['count'] = $option->getCount();
                    $attrs["design"]['options'][$j]['value'] = $option->getoptionId();
                }
                $j++;
            }
            $i++;
        }
        // currency symbol added for arabic store as in layer nav price come without currency symbol
        $symbol = (Mage::app()->getStore()->getCurrentCurrencyCode() == "USD") ? "$" : Mage::app()->getStore()->getCurrentCurrencyCode();
        foreach ($attrs["price"]["options"] as $key => $value) {
            if (strpos($value['label'], $symbol) !== false) {
                continue;
            }
            $attrs["price"]["options"][$key]["label"] = $symbol . " " . $attrs["price"]["options"][$key]["label"] . " " . $symbol;
        }
        if (Mage::registry('filter_products_ids_for_app')) {
            $productIds = Mage::registry('filter_products_ids_for_app');

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $productIds));
            $brandsInUse = array();
            $priceInUse = array();
            $sizesInUse = array();
            $colorsInUse = array();
            $_coreHelper = $this->_getHelper('core');
            foreach ($collection as $product) {
                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                $attributeOptions = array();
                foreach ($productAttributeOptions as $productAttribute) {
                    foreach ($productAttribute['values'] as $attribute) {
                        $attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
                    }
                }
                foreach ($attributeOptions['Size'] as $key => $size) $sizesInUse[] = $key;
                foreach ($attributeOptions['Color'] as $key => $size) $colorsInUse[] = $key;
                // if both of these param are empty then no need to filter brands as per products
                if (!empty($sizeid) or !empty($colorid))
                    $brandsInUse[] = $product->getManufacturer();
                $priceInUse[] = (integer)$_coreHelper->currency($product->getFinalPrice(), false);
            }
            asort($priceInUse);
            $priceInUse = array_unique($priceInUse);
            $priceInUse = array_values($priceInUse);
            $brandsInUse = array_unique($brandsInUse);
            $sizesInUse = array_unique($sizesInUse);
            $colorsInUse = array_unique($colorsInUse);
            // $brandsInUse if not-empty it means need to filter brands as per product so remove extra brands
            if (!empty($brandsInUse)) {
                foreach ($attrs["design"]["options"] as $key => $value) {
                    if (in_array($value['value'], $brandsInUse)) continue;
                    unset($attrs["design"]["options"][$key]);
                }
                $attrs["design"]["options"] = array_values($attrs["design"]["options"]);
            }

            foreach ($attrs["color"]["options"] as $key => $value) {
                if (in_array($value['value'], $colorsInUse)) continue;
                unset($attrs["color"]["options"][$key]);
            }
            $attrs["color"]["options"] = array_values($attrs["color"]["options"]);
            foreach ($attrs["size"]["options"] as $key => $value) {
                if (in_array($value['value'], $sizesInUse)) continue;
                unset($attrs["size"]["options"][$key]);
            }
            $attrs["size"]["options"] = array_values($attrs["size"]["options"]);
            //Create custom price ranges from current products prices on filtration layernav is not returning correct ranges
            $customPriceRanges = array_chunk($priceInUse, 5);
            unset($attrs["price"]["options"]);
            foreach ($customPriceRanges as $key => $value) {
                $lv = ((integer)$value[0]) - 1;
                $hv = ((integer)end($value)) + 1;
                $attrs["price"]["options"][$key]['label'] = $symbol . " " . $lv . " - " . $symbol . " " . $hv;
                $attrs["price"]["options"][$key]['count'] = 0;
                $attrs["price"]["options"][$key]['value'] = $lv . "-" . $hv;
            }
            mage::unregister('filter_products_ids_for_app');
        }
        return $attrs;
    }

    /**
     * This is the new implementation for filters
     * @param $dependencyObject
     * @return mixed
     */
    public function newFilters($dependencyObject)
    {
        $categoryid = (int)$dependencyObject->getRequest()->getParam('cid');
        $designid = $dependencyObject->getRequest()->getParam('manufacturer');
        $sizeid = $dependencyObject->getRequest()->getParam('size');
        $colorid = $dependencyObject->getRequest()->getParam('color');

        $categoryidArr = explode(',,', $categoryid);
        $categoryid = $categoryidArr[0];

        if ($designid == "" && $categoryidArr[1] != "") {
            $designid = $categoryidArr[1];
        }

        if ($categoryid == "" || $categoryid == null) {
            $categoryid = Mage::app()->getStore()->getRootCategoryId();
        }

        if (Mage::getStoreConfig('api/emapi/filterLogs')) {
            Mage::log('filter design = ' . $designid, Null, 'filters_debug.log');
            Mage::log('filter size = ' . $sizeid, Null, 'filters_debug.log');
            Mage::log('filter color = ' . $colorid, Null, 'filters_debug.log');
        }

        $storeId = Mage::app()->getStore()->getId();
        $attrs["design"]['label'] = __("Brands");
        $attributeCode = Mage::getStoreConfig('shopbybrand/general/attribute_code', $storeId);
        $attrCode = $attributeCode ? $attributeCode : 'manufacturer';
        $attrs["design"]['code'] = $attrCode;
        $attrs["design"]['sort'] = 1;
        $attrs["design"]['options'] = array();

        $attrs["color"]['label'] = __("Color");
        $attrs["color"]['code'] = __("color");
        $attrs["color"]['sort'] = 2;
        $attrs["color"]['options'] = array();

        $attrs["size"]['label'] = __("Size");
        $attrs["size"]['code'] = __("size");
        $attrs["size"]['sort'] = 3;
        $attrs["size"]['options'] = array();

        $attrs["price"]['label'] = __("Price");
        $attrs["price"]['code'] = __("price");
        $attrs["price"]['sort'] = 4;
        $attrs["price"]['options'] = array();


        $layer = Mage::getSingleton("catalog/layer");
        $layer->setCurrentCategory($categoryid);
        $collection = $layer->getProductCollection()
            ->setStoreId($storeId)
            ->addAttributeToSelect('*');
        if (!empty($designid)) {
            $designid = explode(',', $designid);
            $collection->addAttributeToFilter('manufacturer', array('in' => $designid));
        }
        $collection1Ids = array();
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array('entity_id'));
        $collection1Ids[] = $collection->getAllIds();
        $collection1Ids = $collection1Ids[0];

        $inUseFiltersOptionIds = array();
        if (sizeof($collection1Ids)) {
            $inUseFiltersOptionIds = $this->getCatalogProductIndexEAVFilters($collection1Ids, $storeId, $sizeid, $colorid);
        }
        // for manufacturer
        $j = 0;
        foreach ($inUseFiltersOptionIds['manufacturer'] as $optionId => $label) {
            $attrs["design"]['options'][$j]['label'] = $label;
            $attrs["design"]['options'][$j]['count'] = 0;
            $attrs["design"]['options'][$j]['value'] = (string)$optionId;
            $j++;
        }
        // for color
        $j = 0;
        foreach ($inUseFiltersOptionIds['color'] as $optionId => $label) {
            $attrs["color"]['options'][$j]['label'] = $label;
            $attrs["color"]['options'][$j]['count'] = 0;
            $attrs["color"]['options'][$j]['value'] = (string)$optionId;
            $j++;
        }
        // for size
        $j = 0;
        foreach ($inUseFiltersOptionIds['size'] as $optionId => $label) {
            $attrs["size"]['options'][$j]['label'] = $label;
            $attrs["size"]['options'][$j]['count'] = 0;
            $attrs["size"]['options'][$j]['value'] = (string)$optionId;
            $j++;
        }

        // for price
        //Create custom price ranges from current products prices on filtration layernav is not returning correct ranges
        // currency symbol added for arabic store as in layer nav price come without currency symbol
        $symbol = (Mage::app()->getStore()->getCurrentCurrencyCode() == "USD") ? "$" : Mage::app()->getStore()->getCurrentCurrencyCode();
        $customPriceRanges = $this->_getHelper()->getCustomPriceRanges($inUseFiltersOptionIds['prices']);
        foreach ($customPriceRanges as $key => $value) {
            $lv = ((integer)$value[0]) - 1;
            $hv = ((integer)end($value)) + 1;
            $attrs["price"]["options"][$key]['label'] = $symbol . " " . $lv . " - " . $symbol . " " . $hv;
            $attrs["price"]["options"][$key]['count'] = 0;
            $attrs["price"]["options"][$key]['value'] = $lv . "-" . $hv;
        }
        return $attrs;
    }

    /**
     * This function will return all attributes(manufacturer,color,size,price) which are in used
     * @param $productIds
     * @param $storeId
     * @return array
     */
    public function getCatalogProductIndexEAVFilters($productIds, $storeId, $sizeId = NULL, $colorId = NULL)
    {
        if (is_array($productIds)) {
            $pIds = implode(',', $productIds);
        } else {
            $pIds = $productIds;
        }

        if ($sizeId != NULL or $colorId != NULL)
            $pIds = $this->filterProductIds($pIds, $storeId, $sizeId, $colorId);
        $combineOptionIds = array();
        $manufacturer = array();
        $color = array();
        $size = array();
        $prices = array();
        // get option Ids
        if (!empty($pIds)) {
            $results = $this->readCustomQuery("SELECT * FROM " . $this->_prefix . "catalog_product_index_eav WHERE entity_id IN ({$pIds}) AND store_id = {$storeId}");
            foreach ($results as $result) {
                if ($result['attribute_id'] == 81) {
                    if (!array_key_exists($result['value'], $manufacturer)) {
                        $manufacturer[$result['value']] = "*";
                        $combineOptionIds[] = $result['value'];
                    }
                } elseif ($result['attribute_id'] == 92) {
                    if (!array_key_exists($result['value'], $color)) {
                        $color[$result['value']] = "*";
                        $combineOptionIds[] = $result['value'];
                    }
                } elseif ($result['attribute_id'] == 148) {
                    if (!array_key_exists($result['value'], $size)) {
                        $size[$result['value']] = "*";
                        $combineOptionIds[] = $result['value'];
                    }
                }
            }
            // get option labels
            if (sizeof($combineOptionIds)) {
                $combineOptionIds = implode(',', $combineOptionIds);
                $results = $this->readCustomQuery("SELECT * FROM " . $this->_prefix . "eav_attribute_option_value WHERE option_id IN ({$combineOptionIds}) AND store_id IN (0,{$storeId}) order by store_id desc");
            }
            // assign labelz to manufacturers option ids
            foreach ($manufacturer as $key => $val) {
                foreach ($results as $result) {
                    if ($key != $result['option_id']) continue;
                    $manufacturer[$key] = $result['value'];
                    break;
                }
            }
            // assign labelz to $color option ids
            foreach ($color as $key => $val) {
                foreach ($results as $result) {
                    if ($key != $result['option_id']) continue;
                    $color[$key] = $result['value'];
                    break;
                }
            }

            // assign labelz to $size option ids
            foreach ($size as $key => $val) {
                foreach ($results as $result) {
                    if ($key != $result['option_id']) continue;
                    $size[$key] = $result['value'];
                    break;
                }
            }


            // Now prices from Table catalog_product_index_price
            if (sizeof($pIds))
                $results = $this->readCustomQuery("SELECT final_price FROM " . $this->_prefix . "catalog_product_index_price WHERE entity_id IN ({$pIds}) AND customer_group_id = 1");
            foreach ($results as $result) {
                if (!in_array((float)$result['final_price'], $prices, true)) {
                    array_push($prices, (float)$result['final_price']);
                }
            }
            asort($prices);
            $prices = array_values($prices);
            $fromCurrency = 'AED';
            $toCurrency = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();

            if ($fromCurrency != $toCurrency) {
                foreach ($prices as $key => $price) {
                    $prices[$key] = Mage::helper('directory')->currencyConvert($price, $fromCurrency, $toCurrency);
                }
            }
        }
        return array('manufacturer' => $manufacturer, 'color' => $color, 'size' => $size, 'prices' => $prices);
    }

    /**
     * This function will filter those products ids which are not present in provided filters (color & size)
     * @param $productIds
     * @param $storeId
     * @param $sizeId
     * @param $colorId
     * @return mixed
     */
    public function filterProductIds($productIds, $storeId, $sizeId, $colorId)
    {
        $sizeQuery = "";
        $colorQuery = "";

        if ($sizeId != NULL) {
            $sizeQuery = "SELECT distinct entity_id FROM " . $this->_prefix . "catalog_product_index_eav WHERE entity_id IN ({$productIds}) AND store_id = {$storeId} AND attribute_id = 148  AND value IN ({$sizeId})";
        }
        if ($colorId != NULL) {
            $colorQuery = "SELECT distinct entity_id FROM " . $this->_prefix . "catalog_product_index_eav WHERE entity_id IN ({$productIds}) AND store_id = {$storeId} AND attribute_id = 92  AND value IN ({$colorId})";
        }
        if ($sizeId != NULL && $colorId != NULL) {
            $result = $this->readCustomQuery($sizeQuery . " AND entity_id IN ( " . $colorQuery . ")");
        } else {
            $result = $this->readCustomQuery($sizeQuery . "" . $colorQuery);
        }

        foreach ($result as $key => $value)
            $result[$key] = (integer)$value['entity_id'];

        return implode(',', $result);
    }

    /**
     * This function will return in use products attributes combination like :
     *  "manufacturerId:ManufacturerLabel~colorId:color:Label~sizeId:sizeLabel~PriceMin:PriceMax"
     *  "1135:Havaianas~477:Black~1091:39-40~20.25:37.8"
     *    "1162:Viamarte~1100:Preto~856:36~24.3:24.3"
     * @param $dependencyObject
     * @return array
     */

    public function filterCombinations($dependencyObject)
    {

        $catId = (int)$dependencyObject->getRequest()->getParam('cid');
        if(empty($catId)) $catId = 2;
        $storeId = Mage::app()->getStore()->getId();

        $path = Mage::getBaseDir('media') . "/app_filters/";
        $fileName = $storeId . "_" . $catId . '.json';

        if (file_exists($path . $fileName)) {
            return file_get_contents($path . $fileName);
        } else {
            if ($this->generateFiltersJson($catId)) {
                return file_get_contents($path . $fileName);
            }
        }
    }

    /**
     * This function will run the process to generate filters json files
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function runAppFilters($logs,$targetedCat = NULL)
    {
        try {
            $activeCategories = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToFilter('is_active', 1);
            if($targetedCat != NULL){
                $activeCategories->addAttributeToFilter('entity_id',array('in'=>$targetedCat));
            }
            $activeCategories = $activeCategories->getColumnValues('entity_id');
            asort($activeCategories);
            $activeCategories = array_values($activeCategories);
            foreach ($activeCategories as $catId) {
                $flag = $this->generateFiltersJson($catId);
                if ($logs) {
                    $status = ($flag == true) ? 'true' : 'false';
                    Mage::log('Category Id : ' . $catId . " Finished. Status = " . $status, null, "app_new_filters.log");
                }
            }
            return $flag;
        }
        catch (Exception $e){
            Mage::log('In Filters Model runAppFilters : ' . $e->getMessage(), null, "app_new_filters.log");
            return false;
        }
    }

    /**
     * Get category Id and store id to generate the json files for all stores
     * @param $catId
     * @param $storeId
     * @return mixed
     */
    public function generateFiltersJson($catId, $storeId = 1)
    {
        try {
            $layerCustomQuery = "SELECT 
                                `e`.`entity_id`,`price_index`.`final_price`
                            FROM
                                `catalog_product_entity` AS `e`
                                    INNER JOIN
                                `catalog_category_product_index` AS `cat_index` ON cat_index.product_id = e.entity_id
                                    AND cat_index.store_id = {$storeId}
                                    AND cat_index.visibility IN (2 , 4)
                                    AND cat_index.category_id = '{$catId}'
                                    INNER JOIN
                                `catalog_product_index_price` AS `price_index` ON price_index.entity_id = e.entity_id
                                    AND price_index.customer_group_id = 1
                            ORDER BY `entity_id` ASC";

            $layerCcollection = $this->readCustomQuery($layerCustomQuery);

            if (sizeof($layerCcollection)) {
                $combinationPrices = array();
                $collection1Ids = array();

                foreach ($layerCcollection as $key => $value) {
                    $collection1Ids[] = $value["entity_id"];
                    $combinationPrices[$value['entity_id']] = (float)$value["final_price"];
                }
                $results = $this->getCatalogProductIndexEAVAllFilters($collection1Ids, $storeId, $combinationPrices);
            } else {
                $results = array();
            }
            return $this->generateCategoryFilesForAllStores($results, $catId);
        }
        catch (Exception $e){
            Mage::log('In Filters Model generateFiltersJson : ' . $e->getMessage(), null, "app_new_filters.log");
            return false;
        }
    }

    /**
     * This function will receive data in english/arabic and price in AED
     * It will generate file in one call for all stores for one category.
     * @param $results
     *      array (size=37)
     * 0 =>
     * array (size=3)
     * 'en' => string '1148:883 Police~486:Grey~856:36' (length=31)
     * 'ar' => string '1148:883 بوليس~486:رمادي~856:36' (length=41)
     * 'p' => string '155:165' (length=7)
     * @param $catId
     * @throws Mage_Core_Model_Store_Exception
     *
     */
    public function generateCategoryFilesForAllStores($results, $catId)
    {
        try{
        $allStores = Mage::app()->getStores();
        $storeCountry = "";
        $flag = false;
        foreach ($allStores as $_eachStoreId => $val) {
            $storeId = Mage::app()->getStore($_eachStoreId)->getId();
            $storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
            $store = explode('_', $storeCode);
            $storeLanguage = $store[0];
            if ($storeCountry != $store[1]) {
                $storeCountry = $store[1];
                unset($priceConvertedCache);
                $priceConvertedCache[$storeCountry] = array();
            }
            $storeData = array();
            if(!empty($results)){
                foreach ($results as $k=>$result) {
                    $price = '0:0';
                    if ($storeCountry == 'ae') {
                        $price = $result['p'];
                    } else {
                        $combinationPrices = explode(':', $result['p']);
                        $fromCurrency = 'AED';
                        $toCurrency = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();

                        if (empty($priceConvertedCache[$storeCountry][$combinationPrices[0]])) {
                            $priceConvertedCache[$storeCountry][$combinationPrices[0]] = (string)Mage::helper('directory')->currencyConvert($combinationPrices[0], $fromCurrency, $toCurrency);
                        }
                        if (empty($priceConvertedCache[$storeCountry][$combinationPrices[1]])) {
                            $priceConvertedCache[$storeCountry][$combinationPrices[1]] = (string)Mage::helper('directory')->currencyConvert($combinationPrices[1], $fromCurrency, $toCurrency);
                        }
                        $price = $priceConvertedCache[$storeCountry][$combinationPrices[0]] . ":" . $priceConvertedCache[$storeCountry][$combinationPrices[1]];
                    }
                    if ($storeLanguage == "en") {
                        $storeData[] = $result['en'] . "~" . $price;
                    } else {
                        $storeData[] = $result['ar'] . "~" . $price;
                    }
                }
            }
            if (empty($storeData)) $storeData[] = "";
            $path = Mage::getBaseDir('media') . "/app_filters/";
            $fileName = $storeId . "_" . $catId;
            $flag = $this->_getHelper()->generateJsonFile($storeData, $path, $fileName);
        }
        return $flag;
        }
        catch (Exception $e){
            Mage::log('In Filters Model generateCategoryFilesForAllStores : ' . $e->getMessage(), null, "app_new_filters.log");
        }
    }

    /**
     * This function is actually responsible to collect attribues data from tables
     * @param $collection1Ids
     * @return array
     */

    public function getCatalogProductIndexEAVAllFilters($collection1Ids, $storeId, $combinationPrices)
    {

        if (is_array($collection1Ids)) {
            $pIds = implode(',', $collection1Ids);
        } else {
            $pIds = $collection1Ids;
        }
        unset($collection1Ids);// free memory not in further use
        // get option Ids
        if (!empty($pIds)) {
            $q = "SELECT * FROM " . $this->_prefix . "catalog_product_index_eav WHERE entity_id IN ({$pIds}) AND attribute_id IN(81,92,148) AND store_id = {$storeId} order by entity_id,attribute_id";
            $results = $this->readCustomQuery($q);
            unset($q);
        }
        // Now following prodIds are filtered those options which are removed(disabled,outOfStocked etc)
        $prodIds = array_values(array_unique(array_column($results, 'entity_id')));
        $combineOptionIds = array_unique(array_column($results, 'value')); // extracted to get their labels like 1135:British Knights
        asort($combineOptionIds);
        $combineOptionIds = array_values($combineOptionIds);
        $attrIdsWithLabels = $this->getAttrIdsWithLabels($combineOptionIds);
        unset($combineOptionIds);     // free memory not in further use
        unset($pIds);     // free memory not in further use
        unset($prodIds); // free memory not in further use

        $productWiseData = array();
        foreach ($results as $key => $result) {
            if ($result['attribute_id'] == 81)
                $productWiseData[$result['entity_id']]['manufacturer'][] = $result['value'];
            elseif ($result['attribute_id'] == 92)
                $productWiseData[$result['entity_id']]['color'][] = $result['value'];
            elseif ($result['attribute_id'] == 148)
                $productWiseData[$result['entity_id']]['size'][] = $result['value'];
        }
        $combinations = array();
        // following process is to replace *** with missing value
        foreach ($productWiseData as $pid => $prodAttrs) {
            // keep replacements in order manufacturer,color,size
            // if manufacturer not exist add value *** for that at start
            if (!array_key_exists('manufacturer', $prodAttrs)) {
                continue;
            }
            // if manufacturer not exist add value *** for that at end
            if (!array_key_exists('size', $prodAttrs)) {
                continue;
            }
            // if color not exist add value *** for that at middle
            if (!array_key_exists('color', $prodAttrs)) {
                continue;
            }
            $productCombinations = $this->buildCombination($prodAttrs);
            foreach ($productCombinations as $key => $productCombination) {
                $combinations[$pid][$key]['c'] = implode('~', $productCombination);
                $combinations[$pid][$key]['p'] = $combinationPrices[$pid] . ":" . $combinationPrices[$pid];
            }
        }
        $combinations = $this->removeRedundentCombinations($combinations);
        // Now need to attach attribute labels with attribute ids
        return $this->addTranslationsInCombinations($combinations, $attrIdsWithLabels);
    }

    /**
     * This function crete combination in the form of cartesian product which we actually required
     *
     * @param $set
     * @return array
     */

    public function buildCombination($set)
    {
        if (!$set) {
            return array(array());
        }
        $subset = array_shift($set);
        $cartesianSubset = self::buildCombination($set);
        $result = array();
        foreach ($subset as $value) {
            foreach ($cartesianSubset as $p) {
                array_unshift($p, $value);
                $result[] = $p;
            }
        }
        return $result;
    }

    /**
     * This function will Triverse on $combinations and will remove matching combinations and update the price range in parent matching combination
     *
     * @param $combinations
     * @return mixed
     */
    public function removeRedundentCombinations($combinations)
    {
        foreach ($combinations AS $pid1 => $combination1) {
            foreach ($combinations as $pid2 => $combination2) {
                if ($pid2 <= $pid1) continue;
                foreach ($combination1 as $entrykey1 => $entry1) {
                    foreach ($combination2 as $entrykey2 => $entry2) {
                        if ($entry1["c"] != $entry2["c"]) continue;
                        $combinations[$pid1][$entrykey1]["p"] = $this->_getNewRange(array_unique(array_merge(explode(':', $combinations[$pid1][$entrykey1]["p"]), explode(':', $entry2['p']))));
                        unset($combinations[$pid2][$entrykey2]);
                    }
                }
            }
        }
        // now remove empty values in associated array
        $c = function ($v) {
            return array_filter($v) != array();
        };
        return array_filter($combinations, $c);
    }

    /**
     * This function will return attributes ids & their labels both in English & Arabic
     *
     * @param $combineOptionIds
     * @return array
     */
    public function getAttrIdsWithLabels($combineOptionIds, $storeId = 2)
    {
        $combineOptionIdsforQuery = implode(',', $combineOptionIds);
        $res = $this->readCustomQuery("SELECT option_id, GROUP_CONCAT(value ORDER BY store_id SEPARATOR '~~') as label FROM " . $this->_prefix . "eav_attribute_option_value WHERE option_id IN ({$combineOptionIdsforQuery}) AND store_id IN (0,{$storeId}) GROUP BY option_id");
        unset($combineOptionIdsforQuery);
        $attrIdsWithLabels = array();
        foreach ($res as $key => $val) {
            $attrIdsWithLabels[$val['option_id']] = $val['label'];
        }
        return $attrIdsWithLabels;
    }

    /**
     * This function will add labels translations and replace c with en and add ar in combination values
     *  2709 =>
     * array (size=1)
     * 0 =>
     * array (size=2)
     * 'c' => string '1143~478~926' (length=13) To 'en' => string '1143:883 Police~478:Blue~926:XL' (length=13), 'ar' => string '1143:ARABICSTRING~478:ARABICSTRING~926:ARABICSTRING' (length=13)
     * 'p' => string '145:145' (length=7)
     * @param $combinations
     * @param $attrIdsWithLabels
     * @return array
     */
    public function addTranslationsInCombinations($combinations, $attrIdsWithLabels)
    {
        $finalCombinations = array();
        foreach ($combinations as $pid => $combination) {
            foreach ($combination as $key => $values) {
                $attrIdsValues = explode('~', $values["c"]);
                $string_en = "";
                $string_ar = "";
                $subfinalCombinations = array();
                $skipCombination = false;
                foreach ($attrIdsValues as $attrIdsValue) {
                    // purpose of this check is to skip that combination which had attribute id present in simple product  but actually deleted like: 'c' => string '1135~1111~1091' in which 1111 color deleted but selected in products thats why apresent in catalog_product_index_eav  Table
                    if(!array_key_exists($attrIdsValue,$attrIdsWithLabels)){ $skipCombination = true;break; }
                    $translation = explode('~~', $attrIdsWithLabels[$attrIdsValue]);
                    $en = $translation[0];
                    $ar = (array_key_exists('1',$translation)) ? $translation[1] : $translation[0];
                    $string_en = $string_en . $attrIdsValue . ":" . $en . "~";
                    $string_ar = $string_ar . $attrIdsValue . ":" . $ar . "~";
                }
                if($skipCombination) continue;
                $subfinalCombinations['en'] = rtrim($string_en, '~');
                $subfinalCombinations['ar'] = rtrim($string_ar, '~');
                $subfinalCombinations['p'] = rtrim($values["p"], '~');
                $finalCombinations[] = $subfinalCombinations;
            }
        }
        return $finalCombinations;
    }

    /**
     * Function created to execute query received and return results
     * @param $query
     * @return array
     */
    public function readCustomQuery($query)
    {
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $selectQry = $read->query($query);
        return $selectQry->fetchAll();
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    public function _getHelper($helper = null)
    {
        return ($helper == null) ? Mage::helper('emapi') : Mage::helper($helper);
    }

    /**
     * This function will receive price range from two combinations and return new range
     * Like 123,145,156,189
     * return 123:189
     * @param $R1
     * @return string
     */
    protected function _getNewRange($R1)
    {
        asort($R1);
        $R1 = array_values($R1);
        return $R1[0] . ":" . end($R1);
    }

}