<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of V1
 *
 * @author 
 */
class Progos_Api_Search_Model_Api2_Search_Rest_Admin_V1 extends Progos_Api_List_Model_Api2_List
{
    /**
     *
     * @var int
     */
    protected $_limit = 20;
    /**
     *
     * @var int
     */
    protected $_page = 1;
    
    /**
     *
     * @var mixed 
     */
    protected $_q;
    
    /**
     *
     * @var array 
     */
    protected $_filters = array();
    /**
     *
     * @var array 
     */
    protected $_arguments = array();
    
    /**
     *
     * @var boolean
     */
    protected $_isFloat = true;
    
    
    /**
     * 
     */
    public function __construct() 
    {
        parent::__construct();
        $request = Mage::app()->getRequest();
        $this->setQuery($request->getParam('q'));
        $this->setSortBy($request->getParam('sort'));
        $this->setLimit($request->getParam('limit'));
        $this->setPage($request->getParam('page'));
        $this->setIsFloat($request->getParam('isfloat'));
    }
    
    public function setQuery($query)
    {
        $this->_q = $query;
    }
    
    public function getQuery()
    {
        return $this->_q;
    }
    
    public function collectFilters()
    {
        $this->_request->getParam('category') ? $this->pushFilter('category', $this->_request->getParam('category')) : '';
        $this->_request->getParam('manufacturer') ? $this->pushFilter('manufacturer', $this->_request->getParam('manufacturer')) : '';
        $this->_request->getParam('size') ? $this->pushFilter('size', $this->_request->getParam('size')) : '';
        $this->_request->getParam('color') ? $this->pushFilter('color', $this->_request->getParam('color')) : '';
        $this->_request->getParam('price') ? $this->pushFilter('klevu_price', $this->_request->getParam('price')) : '';
        
    }
    
    public function pushFilter($key, $value)
    {
        $this->_filters[$key] = $value;
    }

    public function collectArguments()
    {
        $this->_arguments['term'] = $this->_q;
        $this->_arguments['page'] = ($this->_page - 1) * $this->_limit;
        $this->_arguments['limit'] = $this->_limit;
        $this->_arguments['sort'] = $this->_sortBy;
        if(!empty($this->_filters)) $this->_arguments['filters'] = $this->_filters;
        
    }

    /**
     * 
     * @param int $sortBy
     */
    public function setSortBy($sortBy = 0)
    {
        switch ($sortBy) {
            case 3:
                $this->_sortBy = 'lth';
                break;
            case 4:
                $this->_sortBy = 'htl';
                break;
            default :
                $this->_sortBy = 'rel';
        }
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
     * @ApiDescription(section="Search", description="Get Search Result Base on Search Term")
     * @ApiMethod(type="get")
     * @ApiRoute(name="/product/search/")
     * @ApiParams(name="q", type="mixed", nullable=false, description="search term", sample="{'q/':'creo'}")
     * @ApiParams(name="store", type="string", description="store code if store not given then default value is en_ae", nullable=false, sample="{'store':'en_ae'}")
     * @ApiParams(name="page", type="integer", nullable=true, description="current page number default is 1", sample="{'page':'2'}")
     * @ApiParams(name="limit", type="integer", nullable=true, description="page limit default is 10", sample="{'limit':'20'}")
     * @ApiParams(name="size", type="integer", nullable=true, description="size", sample="{'size':'2'}")
     * @ApiParams(name="color", type="integer", nullable=true, description="color", sample="{'color':'20'}")
     * @ApiParams(name="manufacturer", type="string", nullable=true, description="manufacture name", sample="{'manufacturer':'creo'}")
     * @ApiParams(name="price", type="string", nullable=true, description="price range", sample="{'price':'20-60'}")
     * @ApiParams(name="sort", type="string", nullable=true, description="price range", sample="{'price':'20-60'}")
     * @ApiParams(name="isfloat", type="boolean", nullable=true, description="isfloat, default is false", sample="{'isfloat':'true'}")
     * @ApiReturnHeaders(sample="HTTP 200 OK")
     * @ApiReturn(type="object", sample="{
     *  'topcategory':'int'
     * }")
     */
    protected function _retrieveCollection() 
    {
        try {
            Varien_Profiler::start('PRODUCT_SEARCH_LIST_API');
            $this->collectFilters();
            $this->collectArguments();
            $data = Mage::helper('klevusearch')->filterKlevuSearchKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearch')->getProducts($this->_arguments), $this->getIsFloat());
            Varien_Profiler::stop('PRODUCT_SEARCH_LIST_API');
            return $data;
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
}
