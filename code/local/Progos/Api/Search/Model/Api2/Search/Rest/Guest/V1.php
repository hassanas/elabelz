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
class Progos_Api_Search_Model_Api2_Search_Rest_Guest_V1 extends Progos_Api_Search_Model_Api2_Filters_Rest_Guest_V1
{
     /**
     *
     * @var int
     */
    protected $_limit = 10;
    
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
     */
    public function __construct() 
    {
        parent::__construct();
    }
    
    /**
     * @ApiDescription(section="Search", description="Get Search Filters Base on Search Term")
     * @ApiMethod(type="get")
     * @ApiRoute(name="/product/search")
     * @ApiParams(name="q", type="mixed", nullable=false, description="search term", sample="{'q/':'creo'}")
     * @ApiParams(name="category", type="string", nullable=true, description="category", sample="{'category/':'category:t-shirts'}")
     * @ApiParams(name="store", type="string", description="store code if store not given then default value is en_ae", nullable=false, sample="{'store':'en_ae'}")
     * @ApiParams(name="page", type="integer", nullable=true, description="current page number default is 1", sample="{'page':'2'}")
     * @ApiParams(name="limit", type="integer", nullable=true, description="page limit default is 10", sample="{'limit':'20'}")
     * @ApiParams(name="size", type="string", nullable=true, description="size", sample="{'size':'size:m,size:xs,size:5-6y,size:xxl'}")
     * @ApiParams(name="color", type="string", nullable=true, description="color", sample="{'color':'color:beige,color:blue,color:cream,color:pink,color:red,color:white'}")
     * @ApiParams(name="manufacturer", type="string", nullable=true, description="manufacture name", sample="{'manufacturer':'manufacturer:anotah'}")
     * @ApiParams(name="price", type="string", nullable=true, description="price range", sample="{'price':'klevu_price:20-60'}")
     * @ApiParams(name="sort", type="string", nullable=true, description="sort order default is rel", sample="{'sort':'lth'}")
     * @ApiReturnHeaders(sample="HTTP 200 OK")
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
}
