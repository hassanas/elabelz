<?php
class Progos_Xlanding_Model_Xlanding_Page extends Amasty_Xlanding_Model_Page
{
    protected $_pageProductInstance = null;
    protected function _beforeSave(){
        $this->setUpdated(1);
        return parent::_beforeSave();
    }
    public function getProductsData()
    {
        $allCatIds = $this->_digCategories(explode(",", $this->getCategory()));
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->addAttributeToSelect('price');
        $productCollection->addAttributeToSelect('final_price');
        $productCollection->addAttributeToSelect('created_at');
        $productCollection->addAttributeToSelect('news_from_date');
        $productCollection->addAttributeToSelect('news_to_date');
        $productCollection
            ->getSelect()
            ->group('entity_id');
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        $productCollection
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('in' => $allCatIds));

        $productCollection->distinct(true);

        $productCollection->addStoreFilter();

        $this->applyAttributesFilter($productCollection);

        $this->applyStockStatusFilter($productCollection);

        $this->applyNewCriteriaFilter($productCollection);

        $this->applyIsSaleByRuleFilter($productCollection);
        return $productCollection->getAllIds();
    }

    public function getPageProducts($categoryId = null)
    {
        $deepCategories = $this->_digCategories(explode(",", $this->getCategory()));
        if ($categoryId ==null ) {
            $categoryId = Mage::app()->getStore()->getRootCategoryId();
        }

        if(count($deepCategories) == 1) {
            $categoryId = array_shift($deepCategories);
        }
        $dir = Mage::getStoreConfig('amlanding/xlanding/sort_direction');
        if ($dir == 1 ) {
            $dir = 'ASC';
        } else {
            $dir = 'DESC';
        }
        $_resource =Mage::getSingleton('core/resource');
        $read = $_resource->getConnection('core_read');
        $table = $_resource->getTableName('am_landing_page_products');
        $query = 'SELECT product_id FROM ' . $table .' where page_id='.$this->getId().' order by position '.$dir;
        $productIds = $read->fetchCol($query);

        $category = Mage::getModel('catalog/category')->load($categoryId);
        $layer = Mage::getSingleton('catalog/layer');
        $layer->setCurrentCategory($category);



        $products = Mage::getModel('catalog/product')->getCollection();
        $products->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
        $products->addFinalPrice();
        $products->addUrlRewrite();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
        $products->addAttributeToFilter('entity_id', array('in' => $productIds));
        $products->getSelect()->order("find_in_set(e.entity_id,'".implode(',',$productIds)."')");

        return $products;

    }
    public function applyPageRules($categoryId = null)
    {
        $deepCategories = $this->_digCategories(explode(",", $this->getCategory()));
        if ($categoryId ==null ) {
            $categoryId = Mage::app()->getStore()->getRootCategoryId();
        }
        if(count($deepCategories) == 1) {
            $categoryId = array_shift($deepCategories);
        }
        $pageProducts = Mage::getModel('xlanding/product'); // new Amasty_Xlanding_Model_Product();
//        $pageProducts = new Progos_Xlanding_Model_Product();
        $pageProducts =$pageProducts->getProductCollection($this)->setOrder('position','DESC')->getData();
        foreach ($pageProducts as $pageProduct) {
            $productIds[] = $pageProduct['entity_id'];
        }

        $category = Mage::getModel('catalog/category')->load($categoryId);
        $layer = Mage::getSingleton('catalog/layer');
        $layer->setCurrentCategory($category);
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter('entity_id', array('in' => $productIds));
        $products->getSelect()->order("find_in_set(entity_id,'".implode(',',$productIds)."')");
        if (Mage::app()->getRequest()->getParam('xlanding_debug_page')) {
            Mage::helper('ambase/utils')->_echo($layer->getProductCollection()->getSelect());
        }

    }
    function applyIsSaleByRuleFilter(&$collection){
        if ($sale = $this->getIsSaleByRule()){
            $collection->addPriceData(null,1);
            if ($sale == self::ON_SALE_BY_RULE_YES) {
                $collection->getSelect()->where('price_index.final_price < price_index.price');
            } else if ($sale == self::ON_SALE_BY_RULE_NO) {
                $collection->getSelect()->where('price_index.final_price >= price_index.price');
            }
        }
    }
    protected function _afterSave()
    {
        $page = Mage::getModel('amlanding/page')->load($this->getId());
        if (Mage::getStoreConfig('amlanding/xlanding/onsave')) {
            $this->getPageProductInstance()->savePageRelation($this);
        }
        return parent::_afterSave();
    }

    public function getPageProductInstance()
    {
        if (!$this->_pageProductInstance) {
            $this->_pageProductInstance = Mage::getSingleton('xlanding/product');
        }
        return $this->_pageProductInstance;
    }


    public function getSelectedProducts()
    {
        if (!$this->hasSelectedProducts()) {
            $products = array();
            foreach ($this->getSelectedProductsCollection() as $product) {
                $products[] = $product;
            }
            $this->setSelectedProducts($products);
        }
        return $this->getData('selected_products');
    }


    public function getSelectedProductsCollection()
    {
        $collection = $this->getPageProductInstance()->getProductCollection($this);
        return $collection;
    }
}
		