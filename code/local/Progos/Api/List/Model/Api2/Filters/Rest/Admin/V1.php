<?php

class Progos_Api_List_Model_Api2_Filters_Rest_Admin_V1 extends Progos_Api_List_Model_Api2_Filters
{  
    protected $_manufacturer;
    
    protected $_size;
    
    protected $_color;
    
    protected $_price;
    
    /**
     *
     * @var array 
     */
    protected $_filters = array();
    
    /**
     *
     * @var array 
     */
    protected $_filtersAttributes = ['color', 'size', 'price', 'manufacturer'];
    
    /**
     *
     * @var int
     */
    protected $_count = 0;

    /**
     * 
     */
    public function __construct() 
    {
       parent::__construct();
       $request = Mage::app()->getRequest();
       $this->setManufacturer($request->getParam('manufacturer'));
       $this->setSize($request->getParam('size'));
       $this->setColor($request->getParam('color'));
       $this->setPrice($request->getParam('price'));
       
       $this->pushAttributesToFilters('Brands', $this->getBrandCode(), 1, 'design');
       $this->pushAttributesToFilters('Color', 'color', 2, 'color');
       $this->pushAttributesToFilters('Size', 'size', 3, 'size');
       $this->pushAttributesToFilters('Price', 'price', 4, 'price');
    }
    
    public function setManufacturer($manufacturer)
    {
        $this->_manufacturer = $manufacturer;
    }
    
    public function getManufacture()
    {
        return $this->_manufacturer;
    }
    
    public function setSize($size)
    {
        $this->_size = $size;
    }
    
    public function getSize()
    {
        return $this->_size;
    }
    
    
    
    public function setColor($color)
    {
        $this->_color = $color;
    }
    
    public function getColor()
    {
        return $this->_color;
    }
    
    
    public function setPrice($price)
    {
        $this->_price = $price;
    }
    
    public function getPrice()
    {
        $this->_price;
    }
    
    /**
     * 
     * @return string
     */
    public function getBrandCode()
    {
        $attributeCode = Mage::getStoreConfig('shopbybrand/general/attribute_code', $this->_currentStoreId);        
        return $attributeCode ? $attributeCode : 'manufacturer';
    }
    
    
    /**
     * 
     * @param string $label
     * @param string $code
     * @param int $sort
     */
    public function pushAttributesToFilters(string $label, string $code, int $sort, $key)
    {
        $this->_filters[$key]['label'] = __($label);
        $this->_filters[$key]['code'] = $label == 'Brands' ? $code :  __($code);
        $this->_filters[$key]['sort'] = $sort;
        $this->_filters[$key]['options'] = array();
    }
    
    public function prepareLayout()
    {
        $layer = $this->getLayer();
        $attributes = $layer->getFilterableAttributes();
        foreach ($attributes as $attribute) {
            if($attribute->getAttributeCode() == 'price') {
                $filterModel = Mage::getModel('api-list/filter_price');
            } elseif($attribute->getBackendType() == 'decimal') {
                // TODO
                continue;
            }else {
                $filterModel = Mage::getModel('api-list/filter_attribute');
            }
            $layerObject = $filterModel->setLayer($layer)->setAttributeModel($attribute)->init();
            $this->addAttributesToFilters($layerObject, $attribute->getAttributeCode());
        }
    }
    
    
    public function addAttributesToFilters($layerObject, $attributeCode)
    {
        $this->_count = 0;
        if(in_array($attributeCode, $this->_filtersAttributes)) {
            $attributeName = $attributeCode == 'manufacturer' ? 'design' : $attributeCode;
            foreach($layerObject->getItems() as $option) {
                $this->_filters[$attributeName]['options'][$this->_count]['label'] = strip_tags($option->getLabel());
                $this->_filters[$attributeName]['options'][$this->_count]['count'] = $option->getCount();
                $this->_filters[$attributeName]['options'][$this->_count]['value'] = $attributeCode == 'price' ? $option->getValue() : $option->getoptionId();
                $this->_count++;
            }
        }
    }
    
    /**
     * @ApiDescription(section="Category", description="Get Product Filters Base on Category Id")
     * @ApiMethod(type="get")
     * @ApiRoute(name="/product/filters")
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
            Varien_Profiler::start('PRODUCT_FILTER_API');
            $this->setCurrentCategoryInLayer($this->getCategoryId());
            $this->prepareLayout();
            //$this->_filters['design']['options'] = $this->array_orderby($this->_filters['design']['options'], 'label', SORT_ASC);
            Varien_Profiler::stop('PRODUCT_FILTER_API');
            return $this->_filters;
        } catch (Exception $ex) {
            $error = new Varien_Object();
            $error->setData('error', true);
            $error->setData('message', $ex->getMessage());
            return $error->getData();
        }
    }
    
    public function getCollection()
    {
        return $this->_retrieveCollection();
    }
}