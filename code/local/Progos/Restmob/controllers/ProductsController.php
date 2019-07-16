<?php

class Progos_Restmob_ProductsController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        $this->getLayout()->setArea('adminhtml');
        $this->setFlag('', self::FLAG_NO_START_SESSION, 1); // Do not start standart session
        parent::preDispatch();
        return $this;
    }

    public function indexAction()
    {
        return false;
    }

    public function productsfilterAction()
    {
        $limit = ($this->getRequest()->getParam('limit')) ? (integer)$this->getRequest()->getParam('limit') : 10;
        $this->getRequest()->setParam('limit', $limit);
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData) and $cacheData !== '{"products":[]}') {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }
        $categoryid = $this->getRequest()->getParam('cid');
        $designid = $this->getRequest()->getParam('manufacturer');
        $sizeid = $this->getRequest()->getParam('size');
        $colorid = $this->getRequest()->getParam('color');
        $pricerange = $this->getRequest()->getParam('price');

        $search = trim($this->getRequest()->getParam('s'));

        $page = ($this->getRequest()->getParam('page')) ? (integer)$this->getRequest()->getParam('page') : 1;
        $start = (($page - 1) * $limit);

        $store = Mage::app()->getStore();
        $currency_code = $store->getCurrentCurrencyCode();
        $storeId = $store->getId();
        $this->getRequest()->setParam('q', trim($this->getRequest()->getParam('s')));
        if (!empty($search)) {
                //$collection = $this->_getHelper()->getNativeSearchCollection($search);// old call for reference
                $sort = $this->getRequest()->getParam('sort');
                if($sort==3)
                    $sort = 'lth';
                elseif($sort==4)
                    $sort = 'htl';
                else
                    $sort = 'rel';
                $filters = array();
                if(!empty($categoryid)) $filters['category'] = $categoryid;// $categoryid is actually string
                if(!empty($designid)) $filters['manufacturer'] = $designid;
                if(!empty($sizeid)) $filters['size'] = $sizeid;
                if(!empty($colorid)) $filters['color'] = $colorid;
                if(!empty($pricerange)) $filters['klevu_price'] = $pricerange;
                // we use limit like page=1&limit=10 second page=2&limit=10
                // klevu uses pagination like that: page=0&limit=10 second page=10&limit=10 third page=20&limit=10
                $page = ($page - 1) * $limit; // This formula workes on pagination
                $args = ['term'=>$search,'page'=>$page,'limit'=>$limit,'sort'=>$sort];
                if(!empty($filters)) $args['filters'] = $filters;
                $data =  $this->_getHelper('klevusearch')->filterKlevuSearchKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearch')->getProducts($args));
                header("Content-Type: application/json");
                echo json_encode($data);exit;
        } else {
            $categoryid = (int)$categoryid;
            $layer = Mage::getSingleton('catalog/layer');
            if ($categoryid) {
                $layer->setCurrentCategory($categoryid);
            }
            $collection = $layer->getProductCollection();
        }
        $data['products'] = array();
        if (is_object($collection) && $collection->getSize()) {
            $collection->addAttributeToSelect('id')
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('description')
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('manufacturer')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('special_price')
                ->setStore($storeId)
                ->addAttributeToFilter('status', array('eq' => 1))
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToFilter("visibility", array("eq" => 4));
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
            Mage::getModel('cataloginventory/stock_status')->addIsInStockFilterToCollection($collection);
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_read');
            if ($designid) {
                $attribute = $collection->getResource()->getAttribute('manufacturer');
                $designid = explode(',', $designid);
                $alias = $attribute->getAttributeCode();
                $conditions = array(
                    "{$alias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                    $connection->quoteInto("{$alias}.store_id = ?", $collection->getStoreId()),
                    $connection->quoteInto("{$alias}.value IN(?)", $designid)
                );

                $collection->getSelect()->join(
                    array($alias => 'catalog_product_index_eav'),
                    join(' AND ', $conditions),
                    array()
                );
            }
            if ($sizeid) {
                $attribute = $collection->getResource()->getAttribute('size');
                $sizeid = explode(',', $sizeid);
                $alias = $attribute->getAttributeCode();
                $conditions = array(
                    "{$alias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                    $connection->quoteInto("{$alias}.store_id = ?", $collection->getStoreId()),
                    $connection->quoteInto("{$alias}.value IN(?)", $sizeid)
                );
                $collection->getSelect()->join(
                    array($alias => 'catalog_product_index_eav'),
                    join(' AND ', $conditions),
                    array()
                );
            }
            if ($colorid) {
                $attribute = $collection->getResource()->getAttribute('color');
                $colorid = explode(',', $colorid);
                $alias = $attribute->getAttributeCode();
                $conditions = array(
                    "{$alias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                    $connection->quoteInto("{$alias}.store_id = ?", $collection->getStoreId()),
                    $connection->quoteInto("{$alias}.value IN(?)", $colorid)
                );
                $collection->getSelect()->join(
                    array($alias => 'catalog_product_index_eav'),
                    join(' AND ', $conditions),
                    array()
                );
            }
            if ($pricerange) {
                $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
                $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
                $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
                $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));
                $pricerange = explode(',', $pricerange);
                $pricesArr = array();
                foreach ($pricerange as $pr) {
                    $priceFilter = explode('-', $pr);
                    if (trim($priceFilter[0]) != "") {
                        $pricesArr[] = ceil($priceFilter[0] / $rates[$currentCurrencyCode]);
                    }
                    if (trim($priceFilter[1]) != "") {
                        $pricesArr[] = ceil($priceFilter[1] / $rates[$currentCurrencyCode]);
                    } else {
                        $pricesArr[] = -1;
                    }

                }
                asort($pricesArr);
                $pricesArr = array_values($pricesArr);
                if ($pricesArr[0] == -1) {
                    $collection->addAttributeToFilter('price', array('gt' => $pricesArr[(sizeof($pricesArr) - 1)]));
                } elseif (sizeof($pricesArr) == 1) {
                    $collection->addAttributeToFilter('price', array('lt' => $pricesArr[0]));
                } else {
                    $collection->addAttributeToFilter('price', array('gt' => $pricesArr[0]));
                    $collection->addAttributeToFilter('price', array('lt' => $pricesArr[(sizeof($pricesArr) - 1)]));
                }
            }
            $sortCat = "";
            if ($categoryid) {
                $categoryObj = Mage::getModel('catalog/category')->load($categoryid);
                $sort = $this->getRequest()->getParam('sort');
                $sortCat = $categoryObj->getDefaultSortBy();
            }
            if ($sort == "") {
                if ($sortCat == 'created_at') {
                    $sort = 1;
                } elseif ($sortCat == 'position') {
                    $sort = 2;
                } elseif ($sortCat == 'price') {
                    $sort = 3;
                } elseif ($sortCat == 'bestsellers') {
                    $sort = 5;
                } elseif ($sortCat == 'most_viewed') {
                    $sort = 6;
                }
            }
            if ($sort == 1) {
                $collection->addAttributeToSort('created_at', 'DESC');
            } elseif ($sort == 2) {
                $collection->addAttributeToSort('position', 'ASC');
            } elseif ($sort == 3) {
                $collection->addAttributeToSort('price', 'ASC');
            } elseif ($sort == 4) {
                $collection->addAttributeToSort('price', 'DESC');
            } elseif ($sort == 5) {
                $collection->joinField(
                    'bestsellers', // alias
                    'amsorting/bestsellers', // table
                    'bestsellers', // field
                    'id=entity_id', // bind
                    array('store_id' => Mage::app()->getStore()->getId()), // conditions
                    'left' // join type
                );
                $collection->getSelect()->order('bestsellers DESC');
            } elseif ($sort == 6) {

                $collection->joinField(
                    'most_viewed', // alias
                    'amsorting/most_viewed', // table
                    'most_viewed', // field
                    'id=entity_id', // bind
                    array('store_id' => Mage::app()->getStore()->getId()), // conditions
                    'left' // join type
                );
                $collection->getSelect()->order('most_viewed DESC');
            }
            $collection->getSelect()->group('entity_id');
            $products = $collection->setCurPage($page)->setPageSize($limit);
            $total_pages = $products->getLastPageNumber();
            if ($products->count() > 0 && $total_pages >= $page) {
                foreach ($products as $product) {
                    if (!$product->getIsSalable()) {
                        continue;
                    }
                    $smallimg = $product->getImageUrl();
                    $img_to_show = (string)Mage::helper('catalog/image')->init($product, 'image')->resize(762,1100);

                    $stock = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($product);
                    $cids = $product->getCategoryIds();
                    if (empty($cids)) {
                        continue;
                    }
                    $cidtoshow = "";
                    foreach ($cids as $scid) {
                        $cidtoshow = $scid;
                    }
                    $prod['id'] = $product->getId();
                    $prod['total_pages'] = $total_pages;
                    $prod['name'] = $product->getName();
                    $prod['description'] = $product->getDescription();
                    $prod['type'] = $product->getProductType();
                    $prod['sku'] = $product->getSku();
                    $prod['img'] = $img_to_show;//$image;
                    $prod['img2'] = $smallimg;
                    $prod['default_sort'] = $sort;
                    $prod['price'] = Mage::helper('core')->currency($product->getPrice(), false, false);
                    $prod['final_price'] = Mage::helper('core')->currency($product->getFinalPrice(), false, false);
                    if($prod['price'] == $prod['final_price']){
                        $prod['sale_price'] = '';
                    }else{
                        $prod['sale_price'] = Mage::helper('core')->currency($product->getFinalPrice(), false, false);
                    }
                    $prod['stock_qty'] = $stock['qty'];
                    $prod['stock_qty_min'] = $stock['min_qty'];
                    $prod['stock_qty_min_sales'] = $stock['min_sale_qty'];
                    $prod['status'] = $product->getStatus();
                    $prod['currency'] = __($currency_code);
                    $prod['category_id'] = $cidtoshow; //$categoryId;
                    $prod['start'] = $start;
                    $prod['limit'] = $limit;
                    $prod['type'] = $product->getTypeId();

                    if ($product->getAttributeText('manufacturer') != "" && $product->getAttributeText('manufacturer') !== false) {
                        $prod['manufacturer'] = $product->getAttributeText('manufacturer');
                    } else {
                        $prod['manufacturer'] = "";
                    }

                    $data['products'][] = $prod;
                }
            }
        }
        $fpcModel->setData($data);
        header("Content-Type: application/json");
        echo json_encode($data);
        die;
    }

    /**
     * @return $collection
     * This function return search results from miravist auto-complete
     *
     */
    protected function getSearchCollection()
    {
        $categoryid = (int)$this->getRequest()->getParam('cid');
        if (!empty($categoryid)) {
            $this->getRequest()->setParam('cat', trim($categoryid));
        }
        $query = Mage::helper('catalogsearch')->getQuery();
        $query->setStoreId(Mage::app()->getStore()->getId());
        $finalarr['products'] = array();
        if ($query->getQueryText()) {
            if (Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            } else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity() + 1);
                } else {
                    $query->setPopularity(1);
                }
                $query->prepare();
            }

            $resultBlock = $this->getLayout()->createBlock('searchautocomplete/result');
            if ($this->getRequest()->getParam('cat')) {
                $resultBlock->setCategoryId(intval($this->getRequest()->getParam('cat')));
            }

            if ($this->getRequest()->getParam('index')) {
                $resultBlock->setIndexFilter($this->getRequest()->getParam('index'));
            }

            $resultBlock->init();
            if (count($resultBlock->getIndexes()) > 0):
                foreach ($resultBlock->getIndexes() as $_index => $_label):
                    $collection = $resultBlock->getCollection($_index);
                endforeach;
            endif;
            Mage::helper('catalogsearch')->getQuery()->save();
            return $collection;
        }
    }

    //service for autocomplete search box
    public function autocompleteAction()
    {
        $args = ['term'=>$this->getRequest()->getParam('s')];
        $data =  $this->_getHelper('klevusearch')->filterKlevuAutoCompleteKeysAsPerRestMobStructure(Mage::getModel('klevusearch/autocomplete')->getProducts($args));
        header("Content-Type: application/json");
        echo json_encode($data);exit;
        // will remove following code
        $limit = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : 10;
        $this->getRequest()->setParam('limit', $limit);
        $this->getRequest()->setParam('s', trim($this->getRequest()->getParam('s')));
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData) and $cacheData !== '{"products":[]}') {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }

        $this->getRequest()->setParam('q', trim($this->getRequest()->getParam('s')));
        $query = Mage::helper('catalogsearch')->getQuery();
        $query->setStoreId(Mage::app()->getStore()->getId());
        $finalarr['products'] = array();
        if ($query->getQueryText()) {
            if (Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            } else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity() + 1);
                } else {
                    $query->setPopularity(1);
                }
                $query->prepare();
            }

            $resultBlock = $this->getLayout()->createBlock('searchautocomplete/result');
            if ($this->getRequest()->getParam('cat')) {
                $resultBlock->setCategoryId(intval($this->getRequest()->getParam('cat')));
            }

            if ($this->getRequest()->getParam('index')) {
                $resultBlock->setIndexFilter($this->getRequest()->getParam('index'));
            }

            $resultBlock->init();
            if (count($resultBlock->getIndexes()) > 0):
                foreach ($resultBlock->getIndexes() as $_index => $_label):
                    $_collection = $resultBlock->getCollection($_index);
                    foreach ($_collection as $_item):
                        $finalarr['products'][] = $_item->getName();
                    endforeach;
                endforeach;
            endif;
            Mage::helper('catalogsearch')->getQuery()->save();
        }


        header("Content-Type: application/json");
        $fpcModel->setData($finalarr);
        echo json_encode($finalarr);
        die;
    }

    //product details with associated product
    public function productassoAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (trim($id) == "") {
            return;
        }
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData)) {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }
        $product = Mage::getSingleton('catalog/product')->load($id);
        if($product->getTypeId() != "configurable"){return;}
        $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
        $baseImageUrl = $productMediaConfig->getMediaUrl($product->getImage());
        $name = $product->getName();
        $associative_arr = array();
        $associated_products = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
        $a = 0;
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $currency_symbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();
        //get child products associated with this product is
        $child_images = array();
        foreach ($associated_products as $assoc) {
            $associative_arr[$a]['id'] = $assoc->getId();
            $assocProduct = Mage::getModel('catalog/product')->load($assoc->getId());
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($assocProduct->getId())->getData();
            $image = array();
            foreach ($assocProduct->getMediaGalleryImages() as $imagee) {
                if (!in_array($imagee->getUrl(), $image)) {
                    $image[] = $imagee->getUrl();
                }
            }
            $associative_arr[$a]['type_id'] = $assocProduct->getTypeId();
            $associative_arr[$a]['sku'] = $assocProduct->getSku();
            $associative_arr[$a]['name'] = $name;
            if (empty($image)) {
                $associative_arr[$a]['img'] = array_filter(array($baseImageUrl), function ($var) {
                    return !is_null($var);
                });
                $child_images[] = $baseImageUrl;
            } else {
                $associative_arr[$a]['img'] = array_filter($image, function ($var) {
                    return !is_null($var);
                });
                $child_images[] = $image[0];
            }
            $associative_arr[$a]['price'] = Mage::helper('core')->currency($assocProduct->getPrice(), false, false);
            if ($assocProduct->getSpecialPrice()) {
                $associative_arr[$a]['sale_price'] = Mage::helper('core')->currency($assocProduct->getSpecialPrice(), false, false);
            } else {
                $associative_arr[$a]['sale_price'] = '';
            }
            $associative_arr[$a]['status'] = $assocProduct->getStatus();
            $associative_arr[$a]['stock_qty'] = (int)$stock['qty'];
            $associative_arr[$a]['stock_qty_min'] = $stock['min_qty'];
            $associative_arr[$a]['stock_qty_min_sales'] = $stock['min_sale_qty'];
            $associative_arr[$a]['currency'] = __($currency_code);
            $associative_arr[$a]['currency_symbol'] = __($currency_symbol);
            $attributes = $assocProduct->getAttributes();
            foreach ($attributes as $key => $value) {
                if ($value->getIsVisibleOnFront()) {
                    $val = $value->getFrontend()->getValue($assocProduct);
                    $deniedKeys = array('sizeguidemen', 'shipping_details', 'sizeguidewomen', 'design');
                    if (!in_array($key, $deniedKeys)) {
                        if ($val == null) {
                            $val = "";
                        }
                        $associative_arr[$a][$key] = $val;
                    }
                    if ($key == "shipping_details") {
                        $shipping_details = $val;
                    }
                }
            }
            if ($assocProduct->getData('size')) {
                $associative_arr[$a]['size'] = $assocProduct->getAttributeText('size');
            }
            if ($assocProduct->getData('manufacturer')) {
                $associative_arr[$a]['manufacturer'] = $assocProduct->getAttributeText('manufacturer');
            }
            $a++;
        }
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $currency_symbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();
        $custom_options = array();
        $image = array();
        foreach ($product->getMediaGalleryImages() as $imagee) {
            if (!in_array($imagee->getUrl(), $image)) {
                $image[] = $imagee->getUrl();
            }
        }
        if (empty($iamge)) {
            $image[] = $baseImageUrl;
        }
        if (empty($image)) {
            $image[] = "https://d3evg79a6ll1gi.cloudfront.net/catalog/product/cache/1/image/265x/9df78eab33525d08d6e5fb8d27136e95/placeholder/default/base-image_1.png";
        }
        $prod['id'] = $product->getId();
        $prod['type_id'] = $product->getTypeId();
        $prod['name'] = $product->getName();
        $prod['sku'] = $product->getSku();
        $image = $this->array_random($image, 5);
        $prod['img'] = array_filter($image, function ($var) {
            return !is_null($var);
        });
        $prod['description'] = $product->getDescription();
        $prod['final_price'] = Mage::helper('core')->currency($product->getFinalPrice(), false, false);
        $prod['price'] = Mage::helper('core')->currency($product->getPrice(), false, false);
        if($prod['price'] == $prod['final_price']){
            $prod['sale_price'] = '';
        }else{
            $prod['sale_price'] = Mage::helper('core')->currency($product->getFinalPrice(), false, false);
        }
        $prod['status'] = $product->getStatus();
        $prod['stock_qty'] = (int)$stock->getQty();
        $prod['stock_qty_min'] = $stock->getMinQty();
        $prod['stock_qty_min_sales'] = $stock->getMinSaleQty();
        $prod['currency'] = __($currency_code);
        $prod['currency_symbol'] = __($currency_symbol);
        $prod['custom_options'] = $custom_options;
        $prod['shipping_details'] = $shipping_details;
        if ($product->getAttributeText('manufacturer') != "" && $product->getAttributeText('manufacturer') !== false) {
            $prod['manufacturer'] = $product->getAttributeText('manufacturer');
        } else {
            $prod['manufacturer'] = "";
        }
        if (trim($product->getMaterial()) != "" && $product->getMaterial() !== false) {
            $prod['material'] = $product->getMaterial();
        } else {
            $prod['material'] = "";
        }
        if (trim($product->getSizeShownInImage()) != "" && $product->getSizeShownInImage() !== false) {
            $prod['size_shown_in_image'] = $product->getSizeShownInImage();
        } else {
            $prod['size_shown_in_image'] = "";
        }
        $prod['product_type'] = 'configurable';
        $configurableOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        foreach ($configurableOptions as $option_row) {
            $c = 0;
            foreach ($option_row['values'] as $or) {
                $option_row['values'][$c]['color'] = "";
                $option_row['values'][$c]['image'] = $child_images[$c];
                $c++;
            }
            $configurable_options[$option_row['attribute_code']] = array('id' => $option_row['id'],
                'attribute_id' => $option_row['attribute_id'],
                'code' => $option_row['attribute_code'],
                'label' => $option_row['label'],
                'values' => $option_row['values']);
        }

        $prod['configurable_options'] = $configurable_options;
        $prod['associative'] = $associative_arr;
        $fpcModel->setData($prod);
        header("Content-Type: application/json");
        print_r(json_encode($prod));
        die;
    }


    //getting random from an array
    public function array_random($arr, $num = 1)
    {
        shuffle($arr);

        $r = array();
        for ($i = 0; $i < $num; $i++) {
            $r[] = $arr[$i];
        }
        return $num == 1 ? $r[0] : $r;
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper($helper=null)
    {
        return ($helper==null) ? Mage::helper('restmob') : Mage::helper($helper);
        // return Mage::helper('restmob');
    }
}