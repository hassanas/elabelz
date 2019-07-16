<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     1.2.3
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 *
 */

/**
 * If custom menu module is not enabled the class
 * Previous "Apptha_CustomMenu_Block_Topmenu" will be extended "Apptha_CustomMenu_Block_Navigation"
 */
class Apptha_CustomMenu_Block_Topmenu extends Mage_Page_Block_Html_Topmenu {
    
    /**
     * @param int $parentCategoryId Root Category.
     * @return mixed
     * @author Saroop
     * @desc   Get all categories collection of Mega menu.  
     */
    public function getCategoryCollection($parentCategoryId = 2)
    {
      $storeId = Mage::app()->getStore()->getId();
       /* Return Whole category Which have parent root category. */
       return $cats = Mage::getModel('catalog/category')->setStoreId($storeId)
                ->getCollection(true)
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('level','2')
                ->addIsActiveFilter()
                ->setOrder('position', 'ASC');
    }


    /**
     * @param int category object , int .
     * @return Array
     * @author Saroop
     * @desc   Create html for Child categories.
     */

    public function generateChildLevelCategory( $category , $liCount , $changeColumnAfter , $kidsCount , $navPath ){

        $html = "";

        // If number of li printed equal to 22 then its break it create a new column and reset li index number.
        if( is_array($changeColumnAfter) )
            $changeColumnAfter = $changeColumnAfter[$kidsCount];

        if( $liCount == $changeColumnAfter ){
            $html .= $this->closeHtmlBlock();
            $html .= $this->openHtmlBlock();
            $liCount = 0;

            if( is_array($changeColumnAfter) )
                $kidsCount++;
        }
        $result = array();
        $children = $category->getChildrenCategories();
        if( !$children )
            $children = $category->getChildren();
        foreach ($children as $child) {
            $datalayerUrlVariable = $navPath." | ".$child->getName();
        	$html .= '<li><a onclick="topMenuTrigger(\''.$datalayerUrlVariable.'\')" href="'.$this->getCategoryUrl($child).'">'.$this->__($child->getName()).'</a></li>';
        	$liCount++;

            // If number of li printed equal to 22 then its break it create a new column and reset li index number.
            if( is_array($changeColumnAfter) )
                $changeColumnAfter = $changeColumnAfter[$kidsCount];

            if( $liCount == $changeColumnAfter ){
                $html .= $this->closeHtmlBlock();
                $html .= $this->openHtmlBlock();
                $liCount = 0;

                if( is_array($changeColumnAfter) )
                    $kidsCount++;
            }
        }


        $result['html'] = $html;
        $result['liCount'] = $liCount;
        return $result;
    }

    

    public function generateChildLevelCategoryDesktop( $category , $liCount , $changeColumnAfter , $kidsCount , $navPath ){
        $html = "";
        $store_code = Mage::app()->getStore()->getCode();
        // If number of li printed equal to 22 then its break it create a new column and reset li index number.
        if( is_array($changeColumnAfter) )
            $changeColumnAfter = $changeColumnAfter[$kidsCount];

        if( $liCount == $changeColumnAfter ){
            $html .= $this->closeHtmlBlock();
            $html .= $this->openHtmlBlock();
            $liCount = 0;

            if( is_array($changeColumnAfter) )
                $kidsCount++;
        }
        $result = array();

        $children = $category->children;
        if( !$children )
            $children = $category->children;
        foreach ($children as $child) {
            if ($child->include_in_menu==false) { continue; }
            $datalayerUrlVariable = $navPath." | ".$child->title;
            //getting url of category according to store
            $url = $child->url;
            if( $child->landing_page_link != "" )
                $url = Mage::getUrl($child->landing_page_link);
            $url_category = str_replace("en_ae",$store_code,$url);
            $html .= '<li><a onclick="topMenuTrigger(\''.$datalayerUrlVariable.'\')" href="'.$url_category.'">'.$this->__($child->name).'</a></li>';
            $liCount++;

            // If number of li printed equal to 22 then its break it create a new column and reset li index number.
            if( is_array($changeColumnAfter) )
                $changeColumnAfter = $changeColumnAfter[$kidsCount];

            if( $liCount == $changeColumnAfter ){
                $html .= $this->closeHtmlBlock();
                $html .= $this->openHtmlBlock();
                $liCount = 0;

                if( is_array($changeColumnAfter) )
                    $kidsCount++;
            }
        }


        $result['html'] = $html;
        $result['liCount'] = $liCount;
        return $result;
    }

    public function getBrandHtml( $liCount , $limit = 8 ){

        $html = "";

        // If number of li printed equal to 22 then its break it create a new column and reset li index number.
        if( $liCount == 22 ){
            $html .= $this->closeHtmlBlock();
            $html .= $this->openHtmlBlock();
            $liCount = 0;
        }

        $html .= '<li><a href="#">'.$this->__("ShOP BY BRANDS").'</a></li>';
        $liCount++;

        $storeId = Mage::app()->getStore()->getId();

        $getBrands = Mage::getResourceModel('shopbybrand/brand_collection')
                         ->setStoreId($storeId)
                         ->setOrder('position_brand','DESC')
                         ->addFieldToFilter('is_featured', array('eq' => 1))
                         ->addFieldToFilter('status', array('eq' => 1))
                         ->setPageSize($limit);

        $getBrands->getSelect()->order(new Zend_Db_Expr('RAND()'));

        foreach ($getBrands as $row) {

            $html .= '<li><a href="'.Mage::getUrl($row->getUrlKey()).'">'.$this->__($row->getName()).'</a></li>';
            $liCount++;

            // If number of li printed equal to 22 then its break it create a new column and reset li index number.
            if( $liCount == 22 ){
                $html .= $this->closeHtmlBlock();
                $html .= $this->openHtmlBlock();
                $liCount = 0;
            }
        }
        $html .= '<li><a href="'.Mage::getUrl('brand').'">'.$this->__('See All Brands').'</a></li>';
        return $html;

    }


    /**
     * @param int category object , int .
     * @return Array
     * @author Saroop
     * @desc   Create html for Child categories of New Arrivals.
     */

    public function generateChildLevelCategoryNewArrival( $category , $liCount , $changeColumnAfter , $kidsCount , $navPath ){
        $html = "";
        // If number of li printed equal to 22 then its break it create a new column and reset li index number.
        if( is_array($changeColumnAfter) )
            $changeColumnAfter = $changeColumnAfter[$kidsCount];

        if( $liCount == $changeColumnAfter ){
            $html .= $this->closeHtmlBlock();
            $html .= $this->openHtmlBlock();
            $liCount = 0;

            if( is_array($changeColumnAfter) )
                $kidsCount++;
        }

        $result = array();
        $children = $category->getChildrenCategories();
        if( !$children )
            $children = $category->getChildren();

        foreach ($children as $child) {
            $datalayerUrlVariable = $navPath." | ".$child->getName();
            $html .= '<li><a  onclick="topMenuTrigger(\''.$datalayerUrlVariable.'\')"  href="'.$this->getCategoryUrl($child).'">'.$this->__( "Just In ".$child->getName() ).'</a></li>';
            $liCount++;

            // If number of li printed equal to 22 then its break it create a new column and reset li index number.
            if( is_array($changeColumnAfter) )
                $changeColumnAfter = $changeColumnAfter[$kidsCount];

            if( $liCount == $changeColumnAfter ){
                $html .= $this->closeHtmlBlock();
                $html .= $this->openHtmlBlock();
                $liCount = 0;

                if( is_array($changeColumnAfter) )
                    $kidsCount++;
            }
        }
        $result['html'] = $html;
        $result['liCount'] = $liCount;
        return $result;
    }


    public function generateChildLevelCategoryNewArrivalDesktop( $category , $liCount , $changeColumnAfter , $kidsCount , $navPath ){

        $html = "";
        $store_code = Mage::app()->getStore()->getCode();
        // If number of li printed equal to 22 then its break it create a new column and reset li index number.
        if( is_array($changeColumnAfter) )
            $changeColumnAfter = $changeColumnAfter[$kidsCount];

        if( $liCount == $changeColumnAfter ){
            $html .= $this->closeHtmlBlock();
            $html .= $this->openHtmlBlock();
            $liCount = 0;

            if( is_array($changeColumnAfter) )
                $kidsCount++;
        }

        $result = array();
        $children = $category->children;
        if( !$children )
            $children = $category->children;

        foreach ($children as $child) {
            if ($child->include_in_menu==false) { continue; }
            $datalayerUrlVariable = $navPath." | ".$child->title;
            //getting url of category according to store
            $url = $child->url;
            if( $child->landing_page_link != "" )
                $url = Mage::getUrl($child->landing_page_link);
            $url_category = str_replace("en_ae",$store_code,$url);
            $html .= '<li><a  onclick="topMenuTrigger(\''.$datalayerUrlVariable.'\')"  href="'.$url_category.'">'.$this->__("Just In ".$child->name ).'</a></li>';
            $liCount++;

            // If number of li printed equal to 22 then its break it create a new column and reset li index number.
            if( is_array($changeColumnAfter) )
                $changeColumnAfter = $changeColumnAfter[$kidsCount];

            if( $liCount == $changeColumnAfter ){
                $html .= $this->closeHtmlBlock();
                $html .= $this->openHtmlBlock();
                $liCount = 0;

                if( is_array($changeColumnAfter) )
                    $kidsCount++;
            }
        }
        $result['html'] = $html;
        $result['liCount'] = $liCount;
        return $result;
    }


    /**
     * @return HTML
     * @author Saroop
     * @desc   Star block structure of rows for menu.
     */

    public function openHtmlBlock(){
        $html = '';
        $html .= '<div class="col-sm-2">';
        $html .= '<ul class="multi-column-dropdown">';
        return $html;
    }


    /**
     * @return HTML
     * @author Saroop
     * @desc   End block structure of rows for menu.
     */

    public function closeHtmlBlock(){
        $html = '';
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @param  object
     * @return string
     * @author Saroop
     * @desc   Return Url for each category.
     */

    public function getCategoryUrl($child){
        $childUrl = $child->getUrl();
        if( empty($childUrl)  )
            $childUrl = Mage::getUrl($child->getUrlKey());
        return $childUrl;
    }

    /**
     * @param  object
     * @return object
     * @author Saroop
     * @desc   Get thumbnail Image of category to show in menu and the position of the image.
     */

    public function getBannerImage( $child ){

        $resutl =  Mage::getResourceModel('catalog/category_collection')
                    ->setStore(Mage::app()->getStore())
                    ->addAttributeToSelect('megamenubanner_position')
                    ->addAttributeToSelect('megamenubanner')
                    ->addFieldToFilter('entity_id', $child->getEntityId())
                    ->addFieldToFilter('is_active', 1)
                    ->getFirstItem();

        return $resutl;
    }

    public function categoryTree(){
        if(Mage::getStoreConfig('custom_menu/general/database_top_menu_mega')) {
            $tabel_exist = Mage::getSingleton('core/resource')
                ->getConnection('core_write')
                ->isTableExists(trim('progos_custommenu_menu'), null);
            $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
            $language = $language[0];
            if ($tabel_exist) {
                $collection = Mage::getModel("progos_custommenu/menu")->getCollection();
                $collection->addFieldToFilter('id', 1);
                if ($collection && $collection->getSize() >= 1) {
                    foreach ($collection as $col) {
                        if ($col->getId()) {
                            if ($language == "en") {
                                $str = $col->getCategoriesEn();
                            } elseif ($language == "ar") {
                                $str = $col->getCategoriesAr();
                            }
                        }
                    }
                    return json_decode($str);
                }
            }
        }else{
            $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
            $language = $language[0];
            $str = file_get_contents('media/mobile_json/categories_' . $language . '.json');
            return json_decode($str);
        }

    }
    
    public function categoryArabicTree(){
        $str = file_get_contents('mobile_json/categories_ar.json');
        return json_decode($str);
    }

    public function categoryEnglishTree(){
        $str = file_get_contents('mobile_json/categories_en.json');
        return json_decode($str);
    }

    /*
     * block cache
     */
    public function _construct()
    {
        if(Mage::getStoreConfig('custom_menu/general/database_top_menu_mega')) {
            $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
            $language = $language[0];
            $store = Mage::app()->getStore();
            $store = explode('_', $store->getCode());
            $store = $store[1];
            if ($language == "en") {
                $this->addData(array(
                        'cache_key' => 'CustomMenu_Mega_EN_' . $store, // can be static or dynamic
                        'cache_lifetime' => 86400,
                        'cache_tags' => array(
                            Mage_Core_Model_Store::CACHE_TAG,
                            Mage_Cms_Model_Block::CACHE_TAG,
                            'menu'),
                    )
                );
            } elseif ($language == "ar") {
                $this->addData(array(
                        'cache_key' => 'CustomMenu_Mega_AR_' . $store, // can be static or dynamic
                        'cache_lifetime' => 86400,
                        'cache_tags' => array(
                            Mage_Core_Model_Store::CACHE_TAG,
                            Mage_Cms_Model_Block::CACHE_TAG,
                            'menu'),
                    )
                );
            }
        }
    }
}
