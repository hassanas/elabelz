<?php

class Progos_SaleProductsCategories_Model_Salecategories extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("saleproductscategories/salecategories");

    }

    public function mapCategory($catId,$destinationRoot) {
        $saleCategoryCollection=Mage::getModel('saleproductscategories/salecategories')->getCollection();
        $categories =  $saleCategoryCollection->getColumnValues('category_id');
        $salecategories = $saleCategoryCollection->getColumnValues('sale_category_id');
        $saleCategoryId=0;
        $todayDate = date('m_d_y');
    	$_resource =Mage::getSingleton('core/resource');
        $write = $_resource->getConnection('core_write');
        $saleProductsCategoriesTable = $_resource->getTableName('sale_products_categories');
    	if ($catId!=$destinationRoot) {
    		$sourceCategory = Mage::getModel('catalog/category')->load($catId);
    		$name= $sourceCategory->getName();
            if (false !== $key = array_search($sourceCategory->getParentId() , $categories)) {
                $saleCategoryId=$salecategories[$key];

            } elseif ( $sourceCategory->getParentId()== Mage::getStoreConfig('catalog/salecategory/sourcerootcategory')) {
                $saleCategoryId = $destinationRoot;
            }
            if ($saleCategoryId!=0) {
                $destinationCategory = Mage::getModel('catalog/category')->load($saleCategoryId);
                $salechildcat = $destinationCategory->getChildrenCategories();
                foreach ($salechildcat as $key => $value) {
                    if ($value->getName() == $name) {
                        $data['category_id'] = $catId;
                        $data['sale_category_id'] = $value->getId();
                        $write->insertOnDuplicate($saleProductsCategoriesTable, $data);
                        Mage::log('Origin Category Id: '.$catId.':: Sale Category Id: '.$value->getId(),null,$todayDate.'_mapCategory.log');
                        $destinationRoot = $value->getId();
                        if (Mage::getStoreConfig('catalog/salecategory/copyproducts')){
                            $this->assignProducts($catId,$destinationRoot);
                        }
                    }
                }
            }
        	$childcat= $sourceCategory->getChildrenCategories();
	        foreach ($childcat as $key => $value) {
	                 $this->mapCategory(
	                     $value->getId(),
	                     $destinationRoot
	                 );
	            }
        }
    }

    public function copyMissingCategory($category,$categories,$destinationRoot)
    {
        $todayDate = date('m_d_y');
        if ($category->getId()!= $destinationRoot) {
            if (in_array($category->getId(), $categories)) {
                $childrens = $category->getChildrenCategories();
                foreach ($childrens as $key => $value) {
                    $this->copyMissingCategory($value, $categories,$destinationRoot);
                }
            } elseif (in_array($category->getParentId(), $categories)) {
                $saleCategory = Mage::getModel('saleproductscategories/salecategories')->load($category->getParentId(), 'category_id')->getData('sale_category_id');
                Mage::log('New category from Source Category Id: '.$category->getId().':: Will be copy Under sale Category Id: '.$saleCategory,null,$todayDate.'_CreateCategory.log');
                if(!Mage::getStoreConfig('catalog/salecategory/testmode')) {
                    $this->copycat($category->getId(), $saleCategory);
                }
            }
    }
    }

    public function assignProducts($categoryId,$destinationCategory) {
        $categoryApi = new Mage_Catalog_Model_Category_Api();
        $alreadyOnSaleProducts = Mage::helper('saleproductscategories')->getAlreadyAssignedProducts($destinationCategory);
        $productModel = Mage::getModel('catalog/product');
        $tdate = date('m_d_y');
        $productsArray = [];
        $productCollection = $productModel->getCollection();
        $productCollection->addAttributeToSelect('name');
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->addAttributeToFilter('status', 1);
        $productCollection
            ->getSelect()
            ->group('entity_id');
        if (!empty($alreadyOnSaleProducts) && isset($alreadyOnSaleProducts) && count($alreadyOnSaleProducts)>0) {
            $productCollection->addAttributeToFilter('entity_id', array('nin' => $alreadyOnSaleProducts));
        }
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        $productCollection
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('in' => $categoryId));
        $productCollection->addPriceData(null, 1);
        $productCollection->getSelect()->where('price_index.final_price < price_index.price');
        foreach ($productCollection as $key => $product) {

            Mage::log("Product: ".$product->getId()." assigns to category ".$destinationCategory, null,$tdate . '_addProduct.log' );
            if(!Mage::getStoreConfig('catalog/salecategory/testmode')) {
                $categoryApi->assignProduct($destinationCategory,$product->getId());
                $productsArray[]=$product->getId();
                }

            }

        if(!Mage::getStoreConfig('catalog/salecategory/testmode')) {
            $partialIndexer = new Progos_Partialindex_Model_Observer();
            $partialIndexer->insertEntityIds($productsArray);
        }
    }

    public function copycat($categoryId,$parentId)
    {
        $sourceCategory = Mage::getModel('catalog/category')->load($categoryId);
        $parentCategory = Mage::getModel('catalog/category')->load($parentId);
        $copyMissingCategories =Mage::getStoreConfig('catalog/salecategory/copymissingcat');
        $childcat= $sourceCategory->getChildrenCategories();
        $name =  $sourceCategory->getName();
        $des = $sourceCategory->getDescription();
        $cpage = $sourceCategory->getMetaTitle();
        $metakeyword = $sourceCategory->getMetaKeywords();
        $metadescription = $sourceCategory->getMetaDescription();
        $_resource =Mage::getSingleton('core/resource');
        $write = $_resource->getConnection('core_write');
        $saleProductsCategoriesTable = $_resource->getTableName('sale_products_categories');
        $categoryData= array(
            'name' => $name,
            'url_key' => $sourceCategory->getUrlKey(),
            'is_active' => $sourceCategory->getIsActive(),
            'position' => $sourceCategory->getPosition(),
            'available_sort_by' => 'position',
            'custom_design' => $sourceCategory->getCustomDesign(),
            'custom_apply_to_products' => $sourceCategory->getCustomApplyToProducts(),
            'custom_design_from' => $sourceCategory->getCustomDesignFrom(),
            'custom_design_to' => $sourceCategory->getCustomDesignTo(),
            'custom_layout_update' => $sourceCategory->getCustomLayoutUpdate(),
            'path' => $parentCategory->getPath(),
            'custom_layout_update' => $sourceCategory->getCustomLayoutUpdate(),
            'default_sort_by' => 'position',
            'description' => $des,
            'display_mode' => $sourceCategory->getDisplayMode(),
            'is_anchor' => $sourceCategory->getIsAnchor(),
            'landing_page' => $sourceCategory->getLandingPage(),
            'meta_description' => $metadescription,
            'meta_keywords' => $metakeyword,
            'meta_title' => $cpage,
            'page_layout' => $sourceCategory->getPageLayout(),
            'include_in_menu' => $sourceCategory->getIncludeInMenu(),
        );
        try {
            $newCategory = Mage::getModel('catalog/category')
                ->setStoreId(0);

            $newCategory ->setAttributeSetId($newCategory->getDefaultAttributeSetId());
            $newCategory->setData($categoryData);
            $newCategory->save();
            $newCategoryId = $newCategory->getId();
            $todayDate = date('m_d_y');
            Mage::log('Origin Category Id: '.$categoryId.':: Sale Category Id: '.$newCategoryId,null,$todayDate.'_newCategory.log');
            $data['category_id'] = $categoryId;
            $data['sale_category_id'] = $newCategoryId;
            $write->insertOnDuplicate($saleProductsCategoriesTable, $data);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError('error '.$e->getMessage());
        }
        if ($copyMissingCategories) {
            foreach ($childcat as $key => $value) {
                $this->copycat(
                    $value->getId(),
                    $newCategoryId
                );
            }

        }

    }

}
	 