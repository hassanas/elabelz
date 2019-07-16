<?php

class Progos_Api_List_Model_Api2_List_Rest_Admin_V1 extends Progos_Api_List_Model_Api2_List
{
    /**
     *
     * @var int
     */
    protected $_limit = 10;
    /**
     *
     * @var int
     */
    protected $_page = 1;

    /**
     *
     * @var int
     */
    protected $_categoryId;

    /**
     *
     * @var array
     */
    protected $_categorySortValues = array('1' => 'created_at', '2' => 'position', '3' => 'price', '5' => 'bestsellers', '6' => 'most_viewed');

    /**
     *
     * @var int
     */
    protected $_sortBy = 2;

    /**
     *
     * @var string
     */
    protected $_groupByField = 'entity_id';

    /**
     *
     * @var boolean
     */
    protected $_isFloat = true;

    /**
     * @var int Description
     */
    protected $_start;

    protected $_productCollection;
    /**
     *
     * @var array
     */
    protected $_fieldToSelect = array('id', 'name', 'description', 'sku', 'price', 'manufacturer', 'image', 'special_price');
    
    /**
     *
     * @var type 
     */
    protected $_select;
    
    /**
     *
     * @var array 
     */
    protected $_activeCategoryIds = array();
    
    /**
     *
     * @var array 
     */
    protected $_disableCategoryIds = array();
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $request = Mage::app()->getRequest();
        $this->setCategoryId($request->getParam('cid'));
        $this->setLimit($request->getParam('limit'));
        $this->setPage($request->getParam('page'));
        $this->setIsFloat($request->getParam('isfloat'));
        $this->setStart(($this->_page - 1) * $this->_limit);
        $this->setCurrentCategoryInLayer($this->getCategoryId());
        $this->setSortBy($request->getParam('sort'));
        $this->setSelectForSalable();
    }

    /**
     *
     * @param int|null $categoryId
     */
    public function setCategoryId($categoryId = null)
    {
        $this->_categoryId = is_null($categoryId) ? $this->getLayer()->getCurrentStore()->getRootCategoryId() : $categoryId;
    }

    /**
     *
     * @return int
     */
    public function getCategoryId()
    {
        return (int)$this->_categoryId;
    }

    /**
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        return $this->getLayer()->getCurrentCategory();
    }

    /**
     *
     * @return string
     */
    public function getCategoryDefaultOrder()
    {
        return $this->getLayer()->getCurrentCategory()->getDefaultSortBy();
    }

    /**
     *
     * @return int
     */
    public function getdefaultSortNumber()
    {
        $categoryDefaultSortOrder = $this->getCategoryDefaultOrder();
        $result = array_search($categoryDefaultSortOrder, $this->_categorySortValues);
        return $result > 0 ? $result : 2;
    }

    /**
     *
     * @param int $categoryId
     */
    public function setCurrentCategoryInLayer($categoryId)
    {
        $this->getLayer()->setCurrentCategory($categoryId);
    }
    /**
     *
     * @param int $limit
     */
    public function setLimit($limit = null)
    {
        $this->_limit = is_null($limit) ? $this->_limit : $limit;
    }

    /**
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->_limit;
    }


    /**
     *
     * @param int $page
     */
    public function setPage($page = null)
    {
        $this->_page = is_null($page) ? $this->_page : $page;
    }

    /**
     *
     * @return int
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     *
     * @param type $sortBy
     */
    public function setSortBy($sortBy = '')
    {
        $this->_sortBy = empty($sortBy) ? $this->getdefaultSortNumber() : $sortBy;
    }

    /**
     *
     * @return int
     */
    public function getSortBy()
    {
        return $this->_sortBy;
    }

    /**
     *
     * @param boolean $isFloat
     */
    public function setIsFloat($isFloat = null)
    {
        $this->_isFloat = is_null($isFloat) ? $this->_isFloat : $isFloat;
    }

    /**
     *
     * @return boolean
     */
    public function getIsFloat()
    {
        return $this->_isFloat;
    }

    /**
     *
     * @param int $start
     */
    public function setStart(int $start)
    {
        $this->_start = $start;
    }

    /**
     *
     * @return int
     */
    public function getStart()
    {
        return $this->_start;
    }

    /**
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer()
    {
        return Mage::getSingleton('catalog/layer');
    }

    /**
     *
     * @param int $value
     */
    public function setCurrentPageInCollection($value)
    {
        $this->_productCollection->setCurPage($value);
    }

    /**
     *
     * @param int $value
     */
    public function setPageSizeInCollection($value)
    {
        $this->_productCollection->setPageSize($value);
    }

    public function applyGroupByInCollection()
    {
        $this->_productCollection->groupByAttribute($this->_groupByField);
    }

    public function setSortByInCollection($value)
    {
        switch ($value) {
            case 1:
                $this->_productCollection->addAttributeToSort('created_at', 'DESC');
                break;
            case 2:
                $this->_productCollection->addAttributeToSort('position', 'ASC');
                break;
            case 3:
                $this->_productCollection->addAttributeToSort('price', 'ASC');
                break;
            case 4:
                $this->_productCollection->addAttributeToSort('price', 'DESC');
                break;
            case 5:
                $this->_productCollection->joinField(

                    'bestsellers', // alias
                    'amsorting/bestsellers', // table
                    'bestsellers', // field
                    'id=entity_id', // bind
                    array('store_id' => Mage::app()->getStore()->getId()), // conditions
                    'left' // join type
                );
                $this->_productCollection->getSelect()->order('bestsellers DESC');
                break;
            case 6:
                $this->_productCollection->joinField(
                    'most_viewed', // alias
                    'amsorting/most_viewed', // table
                    'most_viewed', // field
                    'id=entity_id', // bind
                    array('store_id' => Mage::app()->getStore()->getId()), // conditions
                    'left' // join type
                );
                $this->_productCollection->getSelect()->order('most_viewed DESC');
        }
    }


    /**
     * Get all fiterable attributes of current category
     *
     * @return array
     */
    protected function _getFilterableAttributes()
    {
        $attributes = $this->getLayer()->getFilterableAttributes();
        return $attributes;
    }

    /**
     *
     */
    protected function _prepapeLayout()
    {
        $layer = $this->getLayer();
        $filterableAttributes = $this->_getFilterableAttributes();
        $attributeFilter = Mage::getModel('api-list/layer_attribute');
        $listApiHelper=  Mage::helper('api-list');
        foreach ($filterableAttributes as $attribute) {
            if ($attribute->getBackendType() == 'decimal' && $attribute->getAttributeCode() != 'price') {
                // To Do In Future
            } elseif ($attribute->getAttributeCode() != 'price') {
                $currentVals =$listApiHelper->getRequestValues($attribute->getAttributeCode());
                if ($currentVals) {
                    $attributeFilter->setAttributeModel($attribute);
                    $attributeFilter->setLayer($layer);
                    $attributeFilter->setRequestVar($attribute->getAttributeCode());
                    $attributeFilter->apply($currentVals);
                }
            } elseif ($attribute->getAttributeCode() == 'price') {

                $filter = Mage::getModel('api-list/layer_price');
                $filter->setAttributeModel($attribute);
                $filter->setLayer($layer);
                $filter->setRequestVar($attribute->getAttributeCode());
                $filter->apply();
            }
        }
        $this->_productCollection = $layer->getProductCollection();
    }

    public function sortBySalable()
    {
        $select = $this->_productCollection->getSelect();
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
    }

    /**
     * @ApiDescription(section="Category", description="Get Product List Base on Category Id")
     * @ApiMethod(type="get")
     * @ApiRoute(name="/product/list")
     * @ApiParams(name="cid", type="integer", nullable=false, description="category id", sample="{'cid/':'1'}")
     * @ApiParams(name="store", type="string", description="store code if store not given then default value is en_ae", nullable=false, sample="{'store':'en_ae'}")
     * @ApiParams(name="page", type="integer", nullable=true, description="current page number default is 1", sample="{'page':'2'}")
     * @ApiParams(name="limit", type="integer", nullable=true, description="page limit default is 10", sample="{'limit':'20'}")
     * @ApiParams(name="size", type="integer", nullable=true, description="size", sample="{'size':'2'}")
     * @ApiParams(name="color", type="integer", nullable=true, description="color", sample="{'color':'20'}")
     * @ApiParams(name="manufacturer", type="string", nullable=true, description="manufacture name", sample="{'manufacturer':'creo'}")
     * @ApiParams(name="price", type="string", nullable=true, description="price range", sample="{'price':'20-60'}")
     * @ApiParams(name="sort", type="int", nullable=true, description="sort default value 2", sample="{'sort':'2'}")
     * @ApiParams(name="isfloat", type="boolean", nullable=true, description="isfloat, default is false", sample="{'isfloat':'true'}")
     * @ApiReturnHeaders(sample="HTTP 200 OK")
     */
    protected function _retrieveCollection()
    {
        try {
            Varien_Profiler::start('PRODUCT_LIST_API');
            $data = new Varien_Object();
            $this->_prepapeLayout();
            $this->_productCollection->addAttributeToSelect($this->_fieldToSelect)
                ->setStore(Mage::app()->getStore()->getId());
            $this->sortBySalable();
            $this->setSortByInCollection($this->getSortBy());
            $this->applyGroupByInCollection();
            $this->setCurrentPageInCollection($this->getPage());
            $this->setPageSizeInCollection($this->getLimit());
            $data->setData('products', $this->prepareResponse());
            Varien_Profiler::stop('PRODUCT_LIST_API');
            return $data->getData();
        } catch (Exception $ex) {
            $error = new Varien_Object();
            $error->setData('error', true);
            $error->setData('message', $ex->getMessage());
            return $error->getData();
        }
    }

    /**
     *
     * @param array $categoriesIds
     * @return string|int
     */
    public function getOnlyOneActiveCategoryId(array $categoriesIds)
    {
        $ids = implode(',', $categoriesIds);
        $sql = "select DISTINCT cp.category_id from catalog_category_product as cp"
            . " inner join catalog_category_entity_int as ci on cp.category_id = ci.entity_id and ci.attribute_id = 42 and ci.entity_type_id = 3"
            . " where cp.category_id IN ({$ids}) and ci.store_id IN ({$this->_defaultStoreId}, {$this->_currentStoreId}) ORDER BY  cp.category_id DESC limit 1";
        $result = $this->_readConnection->fetchOne($sql);
        return $result > 0 ? $result : "";
    }



    public function prepareResponse()
    {
        $data = array();
        Varien_Profiler::start('GET_TOTAL_PAGE');
        $total_pages = $this->_productCollection->getLastPageNumber();
        if($total_pages < $this->getPage() ) {
            return $data;
        }
        Varien_Profiler::stop('GET_TOTAL_PAGE');
        $imageHelper = Mage::helper('catalog/image');
        $coreHelper = Mage::helper('core');
        $cdnUrl = trim(Mage::getStoreConfig('api/emapi/cdn_url'));
        $cdnUrlFlag = Mage::getStoreConfigFlag('api/emapi/cdn_url');
        $mediaUrl =trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA));
        $categoryModel=Mage::getModel('catalog/category');
        foreach ($this->_productCollection as $product) {
            Varien_Profiler::start('PRODUCT_LIST_API_PREPARE_RESPONCE');
            
            if (!$this->checkProductIsSalable($product) || empty($cids = $product->getCategoryIds())) {
                continue;
            }
            $stock = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($product);
            $prod['id'] = $product->getId();
            $prod['name'] = $product->getName();
            $prod['description'] = $product->getDescription();
            $prod['type'] = $product->getProductType();
            $prod['sku'] = $product->getSku();
            Varien_Profiler::start('IMAGE_LOAD');
            if ($cdnUrlFlag) {
                $prod['img'] = str_replace($mediaUrl,$cdnUrl,$imageHelper->init($product, 'image')->resize(762,1100)->__toString());
                $prod['img2'] = str_replace($mediaUrl,$cdnUrl,$product->getImageUrl());
            } else {
                $prod['img'] = $imageHelper->init($product, 'image')->__toString();
                $prod['img2'] = $product->getImageUrl();
            }
            Varien_Profiler::stop('IMAGE_LOAD');
            $prod['status'] = $product->getStatus();
            Varien_Profiler::start('CATEGORY_LOAD');
            $prod['category_id'] = "";
            for($k = sizeof($cids); $k >= 0; $k--) {
                if(in_array($cids[$k], $this->_activeCategoryIds)) {
                    $prod['category_id'] = $cids[$k];
                    break;
                } else if(in_array($cids[$k], $this->_disableCategoryIds)) {
                    continue;
                } else {
                    $category = $categoryModel->load($cids[$k]);
                    if ($category->getIsActive()) {
                        $this->_activeCategoryIds[] = $prod['category_id'] = $cids[$k];
                        break;
                    } else {
                        $this->_disableCategoryIds[] = $cids[$k];
                    }
                }
            }
            Varien_Profiler::stop('CATEGORY_LOAD');
            $prod['total_pages'] = $total_pages;
            $prod['type'] = $product->getTypeId();
            $prod['stock_qty'] = $stock['qty'];
            $prod['stock_qty_min'] = $stock['min_qty'];
            $prod['stock_qty_min_sales'] = $stock['min_sale_qty'];
            $prod['manufacturer'] = $product->getAttributeText('manufacturer') != "" && $product->getAttributeText('manufacturer') !== false ? $product->getAttributeText('manufacturer') : "";
            $prod['default_sort'] = $this->_sortBy;
            if($this->_isFloat){
                $prod['sale_price'] = 0;
                $prod['price'] = (float)number_format((float)$coreHelper->currency($product->getPrice(), false, false), 2,'.','') ;
                $prod['final_price'] =  (float)number_format((float)$coreHelper->currency($product->getFinalPrice(), false, false), 2,'.','') ;

            }else {
                $prod['sale_price'] = 0;
                $prod['price'] = ceil($coreHelper->currency($product->getPrice(), false, false));
                $prod['final_price'] = ceil($coreHelper->currency($product->getFinalPrice(), false, false));
            }
            if($prod['price'] != $prod['final_price']) {
                $prod['sale_price'] = $prod['final_price'];
            }
            $prod['currency'] = $this->_cuurencyCode;
            $prod['start'] = $this->_start;
            $prod['limit'] = $this->_limit;
            $data[] = $prod;
            Varien_Profiler::stop('PRODUCT_LIST_API_PREPARE_RESPONCE');
        }
        return $data;
    }

    public function getCollection()
    {
        return $this->_retrieveCollection();
    }
    
    /**
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    public function checkProductIsSalable(&$product)
    {
        if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            Varien_Profiler::start('PRODUCT_LIST_API_PREPARE_CUSTOM_SALABLE');
            $salable = $this->isSalable($product);
            if ($salable !== false) {
                $salable = false;
                if($this->checkAnyConfigurableChildItemIsSalable($product->getId()))
                {
                    $salable = true;
                }
            }
            Varien_Profiler::stop('PRODUCT_LIST_API_PREPARE_CUSTOM_SALABLE');
            return $salable;
        }
        return $product->getIsSalable();
    }
    
    /**
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    public function isSalable(&$product)
    {
        $salable = $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        if ($salable && $product->hasData('is_salable')) {
            $salable = $product->getData('is_salable');
        }
        elseif ($salable) {
            $salable = null;
        }

        return (boolean) (int) $salable;
    }
    
    /**
     * 
     * @param int $productId
     * @return int
     */
    public function checkAnyConfigurableChildItemIsSalable($productId)
    {
        $bind = array(':website_id' => $this->_websiteId, ':product_id' => $productId);
        return $this->_readConnection->fetchOne($this->_select, $bind);
    }
    
    /**
     * 
     */
    public function setSelectForSalable()
    {
        $select = $this->_readConnection->select();
        $select->from(array('main_table' => 'catalog_product_entity'),
                array('count(*)')
                );
        $select->joinInner(array('link_table' => 'catalog_product_super_link'),
                'link_table.product_id = main_table.entity_id',
                array()
                );
        $select->joinInner(array('product_website' => 'catalog_product_website'),
                'product_website.product_id = main_table.entity_id',
                array()
                );
        $select->joinInner(array('stock' => 'cataloginventory_stock_status'),
                'stock.product_id = main_table.entity_id AND stock.website_id = :website_id',
                array()
                );
        $select->where('link_table.parent_id = :product_id');
        $select->where('main_table.required_options != 1 OR main_table.required_options IS NULL');
        $select->where('stock.stock_status = 1 ');
        $select->limit(1);
        $this->_select = $select;
    }
}