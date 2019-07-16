<?php

class Progos_Emapi_ProductsController extends Mage_Core_Controller_Front_Action
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
        $limit = ($this->getRequest()->getParam('limit')) ? Mage::helper('api-list')->getPageLimit((integer)$this->getRequest()->getParam('limit')) : Mage::helper('api-list')->getDefaultPageLimit();
        $this->getRequest()->setParam('limit', $limit);
        //in orderto handle cache for new apps
        $isfloat = Mage::app()->getRequest()->getHeader('isfloat');
        $this->getRequest()->setParam('isfloat', trim($isfloat));

        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData) and $cacheData !== '{"products":[]}' and !isset($_REQUEST['T86'])) { //param T86 added for testing will be removed
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }
        $categoryid = $this->getRequest()->getParam('cid');
        $designid = $this->getRequest()->getParam('manufacturer');
        $sizeid = $this->getRequest()->getParam('size');
        $colorid = $this->getRequest()->getParam('color');
        $pricerange = $this->getRequest()->getParam('price');

        $categoryidArr = explode(',,',$categoryid);
        $categoryid = $categoryidArr[0];

        if($designid == "" && isset($categoryidArr[1]) && $categoryidArr[1] != ""){
            $designid = $categoryidArr[1];
        }


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
            $data =  $this->_getHelper('klevusearch')->filterKlevuSearchKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearch')->getProducts($args),$isfloat);
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
                ->setStore($storeId);
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
            //Mage::getModel('cataloginventory/stock_status')->addIsInStockFilterToCollection($collection);
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
                    $collection->getSelect()->where("price_index.final_price >= ".end($pricesArr));
                } elseif (sizeof($pricesArr) == 1) {
                    $collection->getSelect()->where("price_index.final_price <= ".$pricesArr[0]);
                } else {
                    $collection->getSelect()->where("price_index.final_price >= ".$pricesArr[0]);
                    $collection->getSelect()->where("price_index.final_price <= ".end($pricesArr));
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
            /**
             * Adding condition for sort by salable
             */
            $collection = $this->sortBySalable($collection);
            /**
             * End of sort by salable
             */
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
                    $cdnQueryString = trim(Mage::getStoreConfig('api/emapi/cdn_image_querystring'));
                    if ($cdnQueryString != "") {
                        $smallimg = $smallimg.'?query='.$cdnQueryString;
                        $img_to_show = $img_to_show.'?query='.$cdnQueryString;
                    }
                    $stock = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($product);
                    $cids = $product->getCategoryIds();
                    if (empty($cids)) {
                        continue;
                    }
                    $cidtoshow = "";
                    for($k = sizeof($cids); $k >= 0; $k--) {
                        $category = Mage::getModel('catalog/category')->load($cids[$k]);
                        if ($category->getIsActive()) {
                            $cidtoshow = $cids[$k];
                            break;
                        }
                    }
                    $prod['id'] = $product->getId();
                    $prod['total_pages'] = $total_pages;
                    $prod['name'] = $product->getName();
                    $prod['description'] = $product->getDescription();
                    $prod['type'] = $product->getProductType();
                    $prod['sku'] = $product->getSku();
                    $prod['img'] = $img_to_show;//$image;
                    $prod['img2'] = $smallimg;
                    if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                        $prod['img'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$img_to_show);
                        $prod['img2'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$smallimg);
                    }
                    $prod['default_sort'] = $sort;
                    if($isfloat){
                        $prod['sale_price'] = 0;
                        $prod['price'] = (float)number_format((float)Mage::helper('core')->currency($product->getPrice(), false, false), 2,'.','');
                        $prod['final_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
                        if($prod['price'] != $prod['final_price']){
                            $prod['sale_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
                        }
                    }else{
                        $prod['sale_price'] = '';
                        $prod['price'] = ceil(Mage::helper('core')->currency($product->getPrice(), false, false));
                        $prod['final_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
                        if($prod['price'] != $prod['final_price']){
                            $prod['sale_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
                        }
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

                    $manufacturer = $product->getAttributeText('manufacturer');
                    if ($manufacturer != "" && $manufacturer !== false) {
                        $prod['manufacturer'] = $manufacturer;
                    } else {
                        $prod['manufacturer'] = "";
                    }

                    $data['products'][] = $prod;
                }
            }
            $connection->closeConnection();
        }
        $fpcModel->setData($data);
        header("Content-Type: application/json");
        echo json_encode($data);
        die;
    }


    /*
     * Function to list products for category to handle crash on android
     */
    public function productsfilter2Action()
    {
        $limit = ($this->getRequest()->getParam('limit')) ? Mage::helper('api-list')->getPageLimit((integer)$this->getRequest()->getParam('limit')) : Mage::helper('api-list')->getDefaultPageLimit();
        if(Mage::getStoreConfig('api/emapi/ignore_app_limit')){
            $limit = (int)Mage::getStoreConfig('api/emapi/app_limit_to_use');
        }
        $this->getRequest()->setParam('limit', $limit);
        //in orderto handle cache for new apps
        $isfloat = Mage::app()->getRequest()->getHeader('isfloat');
        $this->getRequest()->setParam('isfloat', trim($isfloat));

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

        $categoryidArr = explode(',,',$categoryid);
        $categoryid = $categoryidArr[0];

        if($designid == "" && $categoryidArr[1] != ""){
            $designid = $categoryidArr[1];
        }


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
                $data =  $this->_getHelper('klevusearch')->filterKlevuSearchKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearch')->getProducts($args),$isfloat);
                $fpcModel->setData($data);
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
                ->setStore($storeId);
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
            //Mage::getModel('cataloginventory/stock_status')->addIsInStockFilterToCollection($collection);
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
                    $collection->getSelect()->where("price_index.final_price >= ".end($pricesArr));
                } elseif (sizeof($pricesArr) == 1) {
                    $collection->getSelect()->where("price_index.final_price <= ".$pricesArr[0]);
                } else {
                    $collection->getSelect()->where("price_index.final_price >= ".$pricesArr[0]);
                    $collection->getSelect()->where("price_index.final_price <= ".end($pricesArr));
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
            /**
             * Adding condition for sort by salable
             */
            $collection = $this->sortBySalable($collection);
            /**
             * End of sort by salable
             */
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
                    $cdnQueryString = trim(Mage::getStoreConfig('api/emapi/cdn_image_querystring'));
                    if ($cdnQueryString != "") {
                        $smallimg = $smallimg.'?query='.$cdnQueryString;
                        $img_to_show = $img_to_show.'?query='.$cdnQueryString;
                    }
                    $stock = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($product);
                    $cids = $product->getCategoryIds();
                    if (empty($cids)) {
                        continue;
                    }
                    $cidtoshow = "";
                    for($k = sizeof($cids); $k >= 0; $k--) {
                        $category = Mage::getModel('catalog/category')->load($cids[$k]);
                        if ($category->getIsActive()) {
                            $cidtoshow = $cids[$k];
                            break;
                        }
                    }
                    $prod['id'] = $product->getId();
                    $prod['total_pages'] = $total_pages;
                    $prod['name'] = $product->getName();
                    $prod['description'] = $product->getDescription();
                    $prod['type'] = $product->getProductType();
                    $prod['sku'] = $product->getSku();

                    $prod['img'] = $img_to_show;//$image;
                    $prod['img2'] = $smallimg;
                    if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                        $prod['img'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$img_to_show);
                        $prod['img2'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$smallimg);
                    }
                    $prod['default_sort'] = $sort;
                    if($isfloat){
                        $prod['sale_price'] = 0;
                        $prod['price'] = (float)number_format((float)Mage::helper('core')->currency($product->getPrice(), false, false), 2,'.','');
                        $prod['final_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
                        if($prod['price'] != $prod['final_price']){
                            $prod['sale_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
                        }
                    }else{
                        $prod['sale_price'] = '';
                        $prod['price'] = ceil(Mage::helper('core')->currency($product->getPrice(), false, false));
                        $prod['final_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
                        if($prod['price'] != $prod['final_price']){
                            $prod['sale_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
                        }
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

                    $manufacturer = $product->getAttributeText('manufacturer');
                    if ($manufacturer != "" && $manufacturer !== false) {
                        $prod['manufacturer'] = $manufacturer;
                    } else {
                        $prod['manufacturer'] = "";
                    }

                    $data['products'][] = $prod;
                }
            }
            $connection->closeConnection();
        }
        $fpcModel->setData($data);
        header("Content-Type: application/json");
        echo json_encode($data);
        die;
    }

    /*
     * Function to list products for category and compress the response
     */
    public function productsfiltercompressedAction()
    {
        $limit = ($this->getRequest()->getParam('limit')) ? (integer)$this->getRequest()->getParam('limit') : 10;
        if(Mage::getStoreConfig('api/emapi/ignore_app_limit')){
            $limit = (int)Mage::getStoreConfig('api/emapi/app_limit_to_use');
        }
        $this->getRequest()->setParam('limit', $limit);
        //in orderto handle cache for new apps
        $isfloat = Mage::app()->getRequest()->getHeader('isfloat');
        $this->getRequest()->setParam('isfloat', trim($isfloat));

        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData) and $cacheData !== '{"products":[]}' and !isset($_REQUEST['T86'])) { //param T86 added for testing will be removed
            if(Mage::getStoreConfig('api/emapi/compress_products_response')){
                $cacheData = gzcompress($cacheData, 9);
                header("Content-Type: gzip");
                echo $cacheData;
            }else {
                header("Content-Type: application/json");
                echo $cacheData;
            }
            die;
        }
        $categoryid = $this->getRequest()->getParam('cid');
        $designid = $this->getRequest()->getParam('manufacturer');
        $sizeid = $this->getRequest()->getParam('size');
        $colorid = $this->getRequest()->getParam('color');
        $pricerange = $this->getRequest()->getParam('price');

        $categoryidArr = explode(',,',$categoryid);
        $categoryid = $categoryidArr[0];

        if($designid == "" && $categoryidArr[1] != ""){
            $designid = $categoryidArr[1];
        }


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
            $data =  $this->_getHelper('klevusearch')->filterKlevuSearchKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearch')->getProducts($args),$isfloat);
            if(Mage::getStoreConfig('api/emapi/compress_products_response')){
                $data = json_encode($data);
                $data = gzcompress($data, 9);
                header("Content-Type: gzip");
                echo $data;
            }else {
                header("Content-Type: application/json");
                echo json_encode($data);
            }
            exit;
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
                ->setStore($storeId);
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
            //Mage::getModel('cataloginventory/stock_status')->addIsInStockFilterToCollection($collection);
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
                    $collection->getSelect()->where("price_index.final_price >= ".end($pricesArr));
                } elseif (sizeof($pricesArr) == 1) {
                    $collection->getSelect()->where("price_index.final_price <= ".$pricesArr[0]);
                } else {
                    $collection->getSelect()->where("price_index.final_price >= ".$pricesArr[0]);
                    $collection->getSelect()->where("price_index.final_price <= ".end($pricesArr));
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
            /**
             * Adding condition for sort by salable
             */
            $collection = $this->sortBySalable($collection);
            /**
             * End of sort by salable
             */
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
                    for($k = sizeof($cids); $k >= 0; $k--) {
                        $category = Mage::getModel('catalog/category')->load($cids[$k]);
                        if ($category->getIsActive()) {
                            $cidtoshow = $cids[$k];
                            break;
                        }
                    }
                    $prod['id'] = $product->getId();
                    $prod['total_pages'] = $total_pages;
                    $prod['name'] = $product->getName();
                    $prod['description'] = $product->getDescription();
                    $prod['type'] = $product->getProductType();
                    $prod['sku'] = $product->getSku();

                    $prod['img'] = $img_to_show;//$image;
                    $prod['img2'] = $smallimg;
                    if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                        $prod['img'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$img_to_show);
                        $prod['img2'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$smallimg);
                    }
                    $prod['default_sort'] = $sort;
                    if($isfloat){
                        $prod['sale_price'] = 0;
                        $prod['price'] = (float)number_format((float)Mage::helper('core')->currency($product->getPrice(), false, false), 2,'.','');
                        $prod['final_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
                        if($prod['price'] != $prod['final_price']){
                            $prod['sale_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
                        }
                    }else{
                        $prod['sale_price'] = '';
                        $prod['price'] = ceil(Mage::helper('core')->currency($product->getPrice(), false, false));
                        $prod['final_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
                        if($prod['price'] != $prod['final_price']){
                            $prod['sale_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
                        }
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

                    $manufacturer = $product->getAttributeText('manufacturer');
                    if ($manufacturer != "" && $manufacturer !== false) {
                        $prod['manufacturer'] = $manufacturer;
                    } else {
                        $prod['manufacturer'] = "";
                    }

                    $data['products'][] = $prod;
                }
            }
            $connection->closeConnection();
        }
        $fpcModel->setData($data);
        if(Mage::getStoreConfig('api/emapi/compress_products_response')){
            $data = json_encode($data);
            $data = gzcompress($data, 9);
            header("Content-Type: gzip");
            echo $data;
        }else {
            header("Content-Type: application/json");
            echo json_encode($data);
        }
        die;
    }


    /**
     * This function applies sort by salable on product collection
     */
    public function sortBySalable($collection){
        $select = $collection->getSelect();
        if (!strpos($select->__toString(), 'cataloginventory_stock_status')) {
            $website = Mage::app()->getWebsite();
            if (Mage::helper('core')->isModuleEnabled('Wyomind_Advancedinventory')) {
                $select->joinLeft(
                    array('stock_status' => Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_status')),
                    'e.entity_id = stock_status.product_id AND stock_status.stock_id=1 AND stock_status.website_id=' . $website->getId(),
                    array('salable' => 'stock_status.stock_status')
                );
            } elseif (Mage::helper('core')->isModuleEnabled('Multiple_CatalogInventory')) {
                $select->joinLeft(
                    array('stock_store' => Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock')),
                    'stock_store.store_id = ' . Mage::app()->getStore()->getId(),
                    array()
                );
                $select->joinLeft(
                    array('stock_status' => Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_status')),
                    'e.entity_id = stock_status.product_id AND stock_status.stock_id = stock_store.stock_id AND stock_status.website_id=' . $website->getId(),
                    array('salable' => 'stock_status.stock_status')
                );
            } else {
                Mage::getResourceModel('cataloginventory/stock_status')->addStockStatusToSelect($select, $website);
            }
        }

        $field = 'salable desc';
        if (Mage::getStoreConfig('amsorting/general/out_of_stock_qty')){
            $field = new Zend_Db_Expr('IF(stock_status.qty > 0, 0, 1)');
        }
        $select->order($field);

        // move to the first position
        $orders = $select->getPart(Zend_Db_Select::ORDER);
        if (count($orders) > 1){
            $last = array_pop($orders);
            array_unshift($orders, $last);
            $select->setPart(Zend_Db_Select::ORDER, $orders);
        }
        return $collection;
    }

    /**
     * parameters:
     * s = search term
     * cid=category:women,category:men,category:kids
     *
     * This function will return json results for app auto complete
     * I had implemented cache on it as well for testing on auto complete
     */
    public function autocompleteAction()
    {
        $args = ['term'=>$this->getRequest()->getParam('s')];
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData) and $cacheData !== '{"products":[]}') {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }

        $data =  $this->_getHelper('klevusearch')->filterKlevuAutoCompleteKeysAsPerRestMobStructure(Mage::getModel('klevusearch/autocomplete')->getProducts($args));
        $fpcModel->setData($data);
        header("Content-Type: application/json");
        echo json_encode($data);exit;
    }

    //product details with associated product
    public function productassoAction()
    {
        $id = $this->getRequest()->getParam('id');
        //in orderto handle cache for new apps
        $isfloat = Mage::app()->getRequest()->getHeader('isfloat');
        $this->getRequest()->setParam('isfloat', trim($isfloat));
        $sku = $this->getRequest()->getParam('sku');
        if (trim($id) == "" && trim($sku) == "") {
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
        if (trim($id) == ""){
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        }else{
            $product = Mage::getSingleton('catalog/product')->load($id);
        }
        if (!$product) {
            return;
        }
        if($product->getTypeId() != "configurable"){return;}
        $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
        $baseImageUrl = $productMediaConfig->getMediaUrl($product->getImage());
        if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
            $baseImageUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$productMediaConfig->getMediaUrl($product->getImage()));
        }
        $cdnQueryString = trim(Mage::getStoreConfig('api/emapi/cdn_image_querystring'));
        if ($cdnQueryString != "") {
            $baseImageUrl = $baseImageUrl . '?query=' . $cdnQueryString;//$image;
        }
        $name = $product->getName();
        $associative_arr = array();
        $associated_products = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
        $a = 0;
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $currency_symbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();
        //get child products associated with this product is
        $child_images = array();
        foreach ($associated_products as $assoc) {
            $assocProduct = Mage::getModel('catalog/product')->load($assoc->getId());
            if($assocProduct->getAttributeText('size') == false || $assocProduct->getAttributeText('color') == false){
                continue;
            }
            $associative_arr[$a]['id'] = $assoc->getId();
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($assocProduct->getId())->getData();
            $image = array();
            $smallImgArr = array();
            $thumbsArr = array();
            foreach ($assocProduct->getMediaGalleryImages() as $imagee) {
                $imageUrl = $imagee->getUrl();
                $thumbUrl = Mage::helper('catalog/image')->init($assocProduct, 'thumbnail',$imagee->getFile())->resize(55,79)->__toString();
                $smallUrl = Mage::helper('catalog/image')->init($assocProduct, 'image',$imagee->getFile())->resize(515,744)->__toString();
                if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                    $imageUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$imageUrl);
                    $thumbUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$thumbUrl);
                    $smallUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$smallUrl);
                }
                if ($cdnQueryString != "") {
                    $imageUrl = $imageUrl . '?query=' . $cdnQueryString;//$image;
                }
                if (!in_array($imageUrl, $image)) {
                    $image[] = $imageUrl;
                }
                if (!in_array($thumbUrl, $thumbsArr)) {
                    $thumbsArr[] = $thumbUrl;
                }
                if (!in_array($smallUrl, $smallImgArr)) {
                    $smallImgArr[] = $smallUrl;
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
            if (empty($thumbsArr)) {
                $associative_arr[$a]['thumbs'] = array_filter(array($baseImageUrl), function ($var) {
                    return !is_null($var);
                });
            } else {
                $associative_arr[$a]['thumbs'] = array_filter($thumbsArr, function ($var) {
                    return !is_null($var);
                });
            }
            if (empty($smallImgArr)) {
                $associative_arr[$a]['small_img'] = array_filter(array($baseImageUrl), function ($var) {
                    return !is_null($var);
                });
            } else {
                $associative_arr[$a]['small_img'] = array_filter($smallImgArr, function ($var) {
                    return !is_null($var);
                });
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
            $associative_arr[$a]['color_id'] = $assocProduct->getData('color');
            if ($assocProduct->getData('size')) {
                $associative_arr[$a]['size'] = $assocProduct->getAttributeText('size');
                $associative_arr[$a]['size_id'] = $assocProduct->getData('size');
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
        $thumbsArr = array();
        $smallImgArr = array();
        foreach ($product->getMediaGalleryImages() as $imagee) {
            $imageUrl = $imagee->getUrl();
            $thumbUrl = Mage::helper('catalog/image')->init($product, 'thumbnail',$imagee->getFile())->resize(55,79)->__toString();
            $smallUrl = Mage::helper('catalog/image')->init($product, 'thumbnail',$imagee->getFile())->resize(515,744)->__toString();
            if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                $imageUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$imageUrl);
                $thumbUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$thumbUrl);
                $smallUrl = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$smallUrl);
            }
            if ($cdnQueryString != "") {
                $imageUrl = $imageUrl . '?query=' . $cdnQueryString;//$image;
            }
            if (!in_array($imageUrl, $image)) {
                $image[] = $imageUrl;
            }
            if (!in_array($thumbUrl, $thumbsArr)) {
                $thumbsArr[] = (string) $thumbUrl;
            }
            if (!in_array($smallUrl, $smallImgArr)) {
                $smallImgArr[] = (string) $smallUrl;
            }
        }
        if (empty($image)) {
            $image[] = $baseImageUrl;
        }
        if (empty($thumbsArr)) {
            $thumbsArr[] = $baseImageUrl;
        }
        if (empty($smallImgArr)) {
            $smallImgArr[] = $baseImageUrl;
        }
        if (empty($image)) {
            $image[] =  Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl(). '/placeholder/' . Mage::getStoreConfig('catalog/placeholder/small_image_placeholder');
        }
        if (empty($thumbsArr)) {
            $thumbsArr[] = Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl(). '/placeholder/' . Mage::getStoreConfig('catalog/placeholder/small_image_placeholder');
        }
        if (empty($smallImgArr)) {
            $smallImgArr[] = Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl(). '/placeholder/' . Mage::getStoreConfig('catalog/placeholder/small_image_placeholder');
        }
        $prod['id'] = $product->getId();
        $prod['type_id'] = $product->getTypeId();
        $prod['name'] = $product->getName();
        $prod['sku'] = $product->getSku();
        $image = $this->array_random($image, 5);
        $thumbsArr = $this->array_random($thumbsArr, 5);
        $smallImgArr = $this->array_random($smallImgArr, 5);
        $prod['img'] = array_filter($image, function ($var) {
            return !is_null($var);
        });
        $prod['thumbs'] = array_filter($thumbsArr, function ($var) {
            return !is_null($var);
        });
        $prod['small_img'] = array_filter($smallImgArr, function ($var) {
            return !is_null($var);
        });
        $prod['description'] = $product->getDescription();
        if($isfloat){
            $prod['sale_price'] = 0;
            $prod['final_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
            $prod['price'] = (float)number_format((float)Mage::helper('core')->currency($product->getPrice(), false, false), 2,'.','');
            if($prod['price'] != $prod['final_price']){
                $prod['sale_price'] = (float)number_format((float)Mage::helper('core')->currency($product->getFinalPrice(), false, false), 2,'.','');
            }
        }else{
            $prod['sale_price'] = '';
            $prod['final_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
            $prod['price'] = ceil(Mage::helper('core')->currency($product->getPrice(), false, false));
            if($prod['price'] != $prod['final_price']){
                $prod['sale_price'] = ceil(Mage::helper('core')->currency($product->getFinalPrice(), false, false));
            }
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

        if ($product->getSpecialPrice() && Mage::getStoreConfig('infotrust/infotrust/specialprice') == '1') {
            //get special price by date magetno way
            $price = Mage::getModel('catalog/product_type_price')->calculatePrice($product->getPrice(), $product->getSpecialPrice(), $product->getSpecialFromDate(), $product->getSpecialToDate(), null, null, null, $product->getId());
            $price = ceil(Mage::helper('core')->currency($price, false, false));
        } else {
            $price = ceil(Mage::helper('core')->currency($product->getPrice(), false, false));
        }
        $prod['datalayer_price'] = $price;
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
        return ($helper==null) ? Mage::helper('emapi') : Mage::helper($helper);
    }

    /*
     * Function to fetch products for sliders
     */
    public function relatedProductsSliderAction()
    {
        $id = $this->getRequest()->getParam('id');
        $sku = $this->getRequest()->getParam('sku');
        $isfloat = Mage::app()->getRequest()->getHeader('isfloat');
        if (trim($id) == "" && trim($sku) == "") {
            return;
        }
        /*
         * Check if data available in cache
         */
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData)) {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }
        //end cache

        $response = array("is_error"=>true,"msg"=>"No products found in upsell","code"=>1);
        $data = array();
        if (trim($id) == ""){
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        }else{
            $product = Mage::getSingleton('catalog/product')->load($id);
        }
        $relatedProducts = $product->getRelatedProductIds();
        $count = count($relatedProducts);
        if(!empty($count)) {
            $i = 0;
            foreach($relatedProducts as $relatedProduct) {
                $related = Mage::getModel('catalog/product')->load($relatedProduct);
                $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
                $currency_symbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();
                if($currency_code == "" || $currency_code == null){
                    $currency_code = $currency_symbol;
                }
                $name = $related->getName();
                $url = $related->getProductUrl();
                $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
                $baseImageUrl = $productMediaConfig->getMediaUrl($related->getImage());
                $cdnQueryString = trim(Mage::getStoreConfig('api/emapi/cdn_image_querystring'));
                if ($cdnQueryString != "") {
                    $baseImageUrl = $baseImageUrl . '?query=' . $cdnQueryString;//$image;
                }
                if (!$baseImageUrl) {
                    $baseImageUrl = "https://d3evg79a6ll1gi.cloudfront.net/catalog/product/cache/1/image/265x/9df78eab33525d08d6e5fb8d27136e95/placeholder/default/base-image_1.png";
                }

                $data[$i]['currency'] = trim($currency_code);
                $data[$i]['url'] = $url;
                if($isfloat){
                    $data[$i]['price'] = (float)number_format((float)Mage::helper('core')->currency($related->getFinalPrice(), false, false), 2,'.','');
                }else{
                    $data[$i]['price'] = ceil(Mage::helper('core')->currency($related->getFinalPrice(), false, false));
                }
                $data[$i]['img_url'] = $baseImageUrl;
                $data[$i]['id'] = $related->getSku();
                if ($related->getAttributeText('manufacturer') != "" && $related->getAttributeText('manufacturer') !== false) {
                    $data[$i]['manufacturer'] = $related->getAttributeText('manufacturer');
                    $data[$i]['name'] = $related->getAttributeText('manufacturer');
                } else {
                    $data[$i]['name'] = $name;
                    $data[$i]['manufacturer'] = "";
                }
                $i++;
            }
            $response['is_error'] = false;
            $response['msg'] = "";
            $response['code'] = 0;
            $response['data'] = $data;
        }
        /*
         * Cache the response
         */
        $fpcModel->setData($response);
        header("Content-Type: application/json");
        print_r(json_encode($response));
        die;
    }

    /*
     * Function to fetch best seller products for app slider
     */
    public function bestsellerProductsSliderAction()
    {
        $enable = Mage::getStoreConfig('bestseller/genneral_setting/enabled');
        $days = Mage::getStoreConfig('bestseller/configuration_setting/product_no');
        $limit = Mage::getStoreConfig('bestseller/configuration_setting/days_no');
        $response = array("is_error"=>true,"msg"=>"No products found for bestsellers","code"=>1);
        if(!$enable){
            header("Content-Type: application/json");
            print_r(json_encode($response));
            die;
        }
        //end cache
        $data = array();

        if($days == "") $days = 30;
        if($limit == "") $limit = 20;

        $toDate = Mage::getModel('core/date')->gmtDate('Y-m-d');
        $fromDate = new Zend_Date(); // $date's timestamp === time()
        // changes $date by adding no of days set from admin
        $fromDate->sub($days, Zend_Date::DAY);
        $storeId = Mage::app()->getStore()->getId();
        $bestsellers = Mage::getResourceModel('bestseller/product_bestseller')
            ->addOrderedQty($fromDate->toString("YYYY-MM-dd"),$toDate)
            ->addAttributeToSelect('id')
            ->addAttributeToSelect(array('name', 'price', 'small_image'))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('status', 1)
            ->joinField('is_in_stock',
                'cataloginventory/stock_item',
                'is_in_stock',
                'product_id=entity_id',
                'is_in_stock=1',
                '{{table}}.stock_id=1',
                'left')
            ->setOrder('ordered_qty', 'desc'); // most best sellers on top
        // getNumProduct
        $bestsellers->setPageSize($limit); // require before foreach
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($bestsellers);

        if ($bestsellers->getSize()){
            $i = 0;
            foreach($bestsellers as $bestseller) {
                $product = Mage::getModel('catalog/product')->load($bestseller->getId());
                $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
                $currency_symbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();
                $name = $product->getName();
                $url = $product->getProductUrl();
                $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
                $baseImageUrl = $productMediaConfig->getMediaUrl($product->getImage());
                if (!$baseImageUrl) {
                    $baseImageUrl = "https://d3evg79a6ll1gi.cloudfront.net/catalog/product/cache/1/image/265x/9df78eab33525d08d6e5fb8d27136e95/placeholder/default/base-image_1.png";
                }

                $data[$i]['currency'] = $currency_symbol;
                $data[$i]['name'] = $name;
                $data[$i]['url'] = $url;
                $data[$i]['price'] = Mage::helper('core')->currency($product->getFinalPrice(), false, false);
                $data[$i]['img_url'] = $baseImageUrl;
                $data[$i]['id'] = $product->getSku();
                if ($product->getAttributeText('manufacturer') != "" && $product->getAttributeText('manufacturer') !== false) {
                    $data[$i]['manufacturer'] = $product->getAttributeText('manufacturer');
                } else {
                    $data[$i]['manufacturer'] = "";
                }
                $i++;
            }
            $response['is_error'] = false;
            $response['msg'] = "";
            $response['code'] = 0;
            $response['data'] = $data;
        }
        
        /*
         * Cache the response
         */
        $fpcModel->setData($response);
        header("Content-Type: application/json");
        print_r(json_encode($response));
        die;
    }

     /*
      * Function to return breadcrumb for desktop
     */
    public function getBreadcrumbAction(){
        $id = $this->getRequest()->getParam('id');
        /*
         * Check if data available in cache
         */
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData)) {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }

        $response = array();
        $i = 0;
        if ($id) {
            $response['home']['label'] = Mage::helper('catalog')->__('Home');
            $response['home']['title'] = Mage::helper('catalog')->__('Go to Home Page');
            $response['home']['link'] = Mage::getBaseUrl();
            $product = Mage::getSingleton('catalog/product')->load($id);
            $currentCatIds = $product->getCategoryIds();
            $categoryLevelCollection = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('name')//2 is actually the first level
                ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
                ->addAttributeToFilter('level',2)
                ->addAttributeToFilter('is_active', 1);
            $counter_category = 0;
            $counter_newarrivals = 0;
            $counter_sales = 0;
            foreach($categoryLevelCollection as $cat):
                if($cat->getId()!= 423 && $cat->getId() != 213):
                    $counter_category = 1;
                    $curen_category_name = $cat->getName();
                elseif($cat->getId() == 213):
                    $counter_newarrivals = 1;
                elseif($cat->getId() == 423):
                    $counter_sales = 1;
                endif;
            endforeach;
            $categoryCollection =
                Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('*')//2 is actually the first level
                    ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
                    ->addAttributeToFilter('is_active', 1)
                    ->addAttributeToSort('level', DESC);
            foreach($categoryCollection as $cat):
                $path_cat = $cat->getPath();
                $ids = explode('/', $path_cat);

                if (isset($ids[2])){
                    $topParent = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($ids[2]);
                }
                else{
                    $topParent = null;//it means you are in one catalog root.
                }
                if($counter_category == 1):
                    if($topParent->getName() == $curen_category_name):
                        $pathInStore = $cat->getPathInStore();
                        $pathIds = array_reverse(explode(',', $pathInStore));

                        $categories = $cat->getParentCategories();

                        // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                            if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                                $response['category'.$categoryId] = array(
                                    'label' => $categories[$categoryId]->getName(),
                                    'link' => $categories[$categoryId]->getUrl()
                                );
                            }
                        }
                        break;
                    endif;
                elseif($counter_category != 1 && $counter_newarrivals == 1):
                    if($topParent->getId() == 213):
                        $pathInStore = $cat->getPathInStore();
                        $pathIds = array_reverse(explode(',', $pathInStore));

                        $categories = $cat->getParentCategories();

                        // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                            if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                                $response['category'.$categoryId] = array(
                                    'label' => $categories[$categoryId]->getName(),
                                    'link' => $categories[$categoryId]->getUrl()
                                );
                            }
                        }
                        break;
                    endif;
                elseif($counter_category != 1 && $counter_newarrivals != 1 && $counter_sales == 1):
                    if($topParent->getId() == 423):
                        $pathInStore = $cat->getPathInStore();
                        $pathIds = array_reverse(explode(',', $pathInStore));

                        $categories = $cat->getParentCategories();

                        // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                            if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                                $response['category'.$categoryId] = array(
                                    'label' => $categories[$categoryId]->getName(),
                                    'link' =>  $categories[$categoryId]->getUrl()
                                );
                            }
                        }
                        break;
                    endif;
                endif;

            endforeach;
           $response['product'] = array('label'=>$product->getName());
        }
        /*
         * Cache the response
         */
        $fpcModel->setData($response);
        header("Content-Type: application/json");
        print_r(json_encode($response));
        die;
    }
}