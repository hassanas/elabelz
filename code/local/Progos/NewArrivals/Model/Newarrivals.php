<?php

class Progos_NewArrivals_Model_Newarrivals extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("newarrivals/newarrivals");

    }
    public function mapCategory($catId,$destinationRoot) {
        $newArrivalsCollection=Mage::getModel('newarrivals/newarrivals')->getCollection();
        $categories =  $newArrivalsCollection->getColumnValues('category_id');
        $newArrivalsCategories = $newArrivalsCollection->getColumnValues('new_arrivals_category_id');
        $newArrivalsCategoryId=0;
        $todayDate = date('m_d_y');
        $_resource =Mage::getSingleton('core/resource');
        $write = $_resource->getConnection('core_write');
        $newArrivalsCategoriesTable = $_resource->getTableName('newarrivals_categories');
        if ($catId!=$destinationRoot) {
            $sourceCategory = Mage::getModel('catalog/category')->load($catId);
            $name= $sourceCategory->getName();
            if (false !== $key = array_search($sourceCategory->getParentId() , $categories)) {
                $newArrivalsCategoryId=$newArrivalsCategories[$key];

            } elseif ( $sourceCategory->getParentId()== Mage::getStoreConfig('catalog/newarrivals/root_category')) {
                $newArrivalsCategoryId = $destinationRoot;
            }
            if ($newArrivalsCategoryId!=0) {
                $destinationCategory = Mage::getModel('catalog/category')->load($newArrivalsCategoryId);
                $newArrivalsChildCat = $destinationCategory->getChildrenCategories();
                foreach ($newArrivalsChildCat as $key => $value) {
                    if ($value->getName() == $name) {
                        $data['category_id'] = $catId;
                        $data['new_arrivals_category_id'] = $value->getId();
                        $write->insertOnDuplicate($newArrivalsCategoriesTable, $data);
                        Mage::log('Origin Category Id: '.$catId.':: New Arrival Category Id: '.$value->getId(),null,$todayDate.'_mapCategory.log');
                        $destinationRoot = $value->getId();
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
                $newArrivalsCategory = Mage::getModel('newarrivals/newarrivals')->load($category->getParentId(), 'category_id')->getData('new_arrivals_category_id');
                Mage::log('New category from Source Category Id: '.$category->getId().':: Will be copy Under sale Category Id: '.$newArrivalsCategory,null,$todayDate.'_CreateCategory.log');
                if(!Mage::getStoreConfig('catalog/newarrivals/testmode')) {
                    $this->copycat($category->getId(), $newArrivalsCategory);
                }
            }
        }
    }

    public function assignProducts($categoryId,$destinationCategory) {
        $categoryApi = new Mage_Catalog_Model_Category_Api();
        $todayDate = date('d_m_y');
        $productsArray = [];
        $newFromDate = date("Y-m-d hh:mm:ss", strtotime('-30 days'));
        $alreadyAssignedProducts = Mage::helper('newarrivals')->getAlreadyAssignedProducts($destinationCategory);
        $category= Mage::getModel('catalog/category')->load($categoryId);
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection();
        $productCollection->addAttributeToFilter('type_id', 'configurable');
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        $productCollection->addCategoryFilter($category);
        if (!empty($alreadyAssignedProducts) && isset($alreadyAssignedProducts) && count($alreadyAssignedProducts)>0) {
            $productCollection->addAttributeToFilter('entity_id', array('nin' => $alreadyAssignedProducts));
        }
        $productCollection->addFieldToFilter('created_at', array('gt' => $newFromDate));

        foreach($productCollection as $product){
            Mage::log('From category '.$categoryId.', Assigned new '.$product->getId().' to category '.$destinationCategory,null,$todayDate . '_newarrivalsproducts.log');
            if (!Mage::getStoreConfig('catalog/newarrivals/testmode')) {
                $categoryApi->assignProduct($destinationCategory, $product->getId());
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
        $copyMissingCategories =Mage::getStoreConfig('catalog/newarrivals/copymissingcat');
        $childcat= $sourceCategory->getChildrenCategories();
        $name =  $sourceCategory->getName();
        $des = $sourceCategory->getDescription();
        $cpage = $sourceCategory->getMetaTitle();
        $metakeyword = $sourceCategory->getMetaKeywords();
        $metadescription = $sourceCategory->getMetaDescription();
        $_resource =Mage::getSingleton('core/resource');
        $write = $_resource->getConnection('core_write');
        $newArrivalsCategoriesTable = $_resource->getTableName('newarrivals_categories');
        $categoryData= array(
            'name' => $name,
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
            Mage::log('Origin Category Id: '.$categoryId.':: New Arrivals Category Id: '.$newCategoryId,null,$todayDate.'_newCategory.log');
            $data['category_id'] = $categoryId;
            $data['new_arrivals_category_id'] = $newCategoryId;
            $write->insertOnDuplicate($newArrivalsCategoriesTable, $data);
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
	 