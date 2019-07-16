<?php

/*
@author : Hassan Ali Shahzad
@Date: 17-08-2017
 * */

class Progos_MirasvitSeo_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
     * This function will remove those category Ids from products category array
     * which we dont want to render/link e.g (new arrivals and sale) and we define then in custom variables 'exclude_categories_from_product_url'
     * @$cats Array is all category array e.g. received from $_product->getCategoryIds();
     * return $cat Array
     * */
    public function removeXcludedCategories($cats){
        $excludedCategories = explode(',',Mage::getModel('core/variable')->setStoreId(Mage::app()->getStore()->getId())->loadByCode('exclude_categories_from_product_url')->getValue('text'));
        $toExclude = array();
        foreach($excludedCategories as $excludedCategory){
            $toExclude = $this->retrieveAllChildCategories($excludedCategory);// also get all the child categories from above to remove
            $toExclude = $this->getAllKeysForMultiLevelArrays($toExclude);
            $cats = array_diff($cats,$toExclude);
        }
        $cats = array_diff($cats,$excludedCategories);
        return $cats;
    }

    /**
     * @param null $cateId category id
     * @return Array of child categories
     * This recursive function get all categories at multilevel
     * Problem is Magento function 'getChildrenCategoriesWithInactive' only return first child level inactive
     */
    public function retrieveAllChildCategories($cateId = null) {
        $category = Mage::getModel('catalog/category')
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->getCollection()
                            ->addAttributeToFilter('entity_id', $cateId)
                            ->getFirstItem();
        $allChilds = array();
        $catids = $category->getChildrenCategoriesWithInactive()->getAllIds();
        foreach($catids as $catid){
            $allChilds[$catid] =  $this->retrieveAllChildCategories($catid);
        }
        return $allChilds;
    }

    /**
     * @param array $array
     * @return array
     * This Recursive function triverse all multilevel array and return keys whcih are actually all childs categories including active and inactive
     */
    public function getAllKeysForMultiLevelArrays(array $array)
    {
        $keys = array();
        foreach ($array as $key => $value) {
            $keys[] = $key;
            if (is_array($array[$key])) {
                $keys = array_merge($keys, $this->getAllKeysForMultiLevelArrays($array[$key]));
            }
        }
        return $keys;
    }

}