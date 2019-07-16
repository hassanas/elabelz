<?php
/**
 * Progos_Page.
 *
 * @category Progos
 *
 */

/**
 * Class Progos_Page_Block_Page_Html_Header
 */
class Progos_Mobilemenu_Block_Menu extends Mage_Page_Block_Html_Header
{
    /**
     * @param int $parentCategoryId
     * @return mixed
     */
    public function getCategoryCollection($parentCategoryId = 2)
    {
        return Mage::getModel('catalog/category')->getCategories($parentCategoryId);
    }

    /**
     * @param $category
     * @param null $title
     * @return string
     */
    public function generateParentLevel($category, $title = null,$gender)
    {
        //$mainUrl = Mage::getUrl($category->getUrlKey());
        $mainUrl = $category->getUrl();
        if( empty($mainUrl)  )
            $mainUrl = Mage::getUrl($category->getUrlKey());

        $menu = '<li class="level0 parent">';

        if (!is_null($title)) {
            $menu .= '<a onclick="topMenuTrigger(\''.$title.'\')" href="' . $mainUrl . '" class="next">';
            $menu .= '<span>' . $this->__($title) . '</span>';
        } else {
            $menu .= '<a onclick="topMenuTrigger(\''.$gender.' | '.$category->getName().'\')" href="' . $mainUrl . '" class="next">';
            $menu .= '<span>' . $this->__($category->getName()) . '</span>';
        }
        $menu .= '</a>';
        $menu .= '<ul>';

        if (!is_null($title)) {
            $menu .= '<li class="level1"><a onclick="topMenuTrigger(\''.$title.' | '.$category->getName().' | View All \')" href="' . $mainUrl . '">' . $this->__('View All')
                . '</a></li>';
        }else{
            $menu .= '<li class="level1"><a onclick="topMenuTrigger(\''.$gender.' | '.$category->getName().' | View All \')" href="' . $mainUrl . '">' . $this->__('View All')
                . '</a></li>';
        }
        $children = $category->getChildrenCategories();
        if( !$children )
            $children = $category->getChildren();

        foreach ($children as $child) {
            $childUrl = $child->getUrl();
            if( empty($childUrl)  )
                $childUrl = Mage::getUrl($child->getUrlKey());
            $datalayerUrlVariable = $gender.' | '.$category->getName().' | '.$child->getName();
            $menu .= '<li class="level1"><a onclick="topMenuTrigger(\''.$datalayerUrlVariable.'\')" href="' . $childUrl  . '">'
                . $this->__($child->getName()) . '</a></li>';
        }
        $menu .= '</ul>';

        return $menu;
    }

    public function generateParentLevelmobile($category, $title = null,$gender)
    {
        //$mainUrl = Mage::getUrl($category->getUrlKey());
        $store_code = Mage::app()->getStore()->getCode();
        //getting url of category according to store
        $url = $category->url;
        if( $category->landing_page_link != "" )
            $url = Mage::getUrl($category->landing_page_link);
        $url_category = str_replace("en_ae",$store_code,$url);
        $mainUrl = $url_category;
        if( empty($mainUrl)  )
            $mainUrl = Mage::getUrl($category->getUrlKey());

        $menu = '<li class="level0 parent">';

        if (!is_null($title)) {
            $menu .= '<a onclick="topMenuTrigger(\''.$title.'\')" href="' . $mainUrl . '" class="next">';
            $menu .= '<span>' . $this->__($title) . '</span>';
        } else {
            $menu .= '<a onclick="topMenuTrigger(\''.$gender.' | '.$category->title.'\')" href="' . $mainUrl . '" class="next">';
            $menu .= '<span>' . $this->__($category->title) . '</span>';
        }
        $menu .= '</a>';
        $menu .= '<ul>';

        if (!is_null($title)) {
            $menu .= '<li class="level1"><a onclick="topMenuTrigger(\''.$title.' | '.$category->title.' | View All \')" href="' . $mainUrl . '">' . $this->__('View All')
                . '</a></li>';
        }else{
            $menu .= '<li class="level1"><a onclick="topMenuTrigger(\''.$gender.' | '.$category->title.' | View All \')" href="' . $mainUrl . '">' . $this->__('View All')
                . '</a></li>';
        }
        $children = $category->children;
        if( !$children )
            $children = $category->children;

        foreach ($children as $child) {
            if ($child->include_in_menu==false) { continue; }
            //getting url of category according to store
            $url = $child->url;
            if( $child->landing_page_link != "" )
                $url = Mage::getUrl($child->landing_page_link);
            $url_category = str_replace("en_ae",$store_code,$url);
            $childUrl = $url_category;
            if( empty($childUrl)  )
                $childUrl = $url_category;
            $datalayerUrlVariable = $gender.' | '.$category->title.' | '.$child->title;
            $menu .= '<li class="level1"><a onclick="topMenuTrigger(\''.$datalayerUrlVariable.'\')" href="' . $childUrl  . '">'
                . $this->__($child->title) . '</a></li>';
        }
        $menu .= '</ul>';

        return $menu;
    }

    public function categoryTree(){
        if(Mage::getStoreConfig('custom_menu/general/database_top_menu_mobile')) {
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

    /*
    * block cache
    */
    public function _construct()
    {
        if(Mage::getStoreConfig('custom_menu/general/database_top_menu_mobile')) {
            $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
            $language = $language[0];
            $store = Mage::app()->getStore();
            $store = explode('_', $store->getCode());
            $store = $store[1];
            if ($language == "en") {
                $this->addData(array(
                        'cache_key' => 'CustomMenu_Mobile_EN_' . $store, // can be static or dynamic
                        'cache_lifetime' => 86400,
                        'cache_tags' => array(
                            Mage_Core_Model_Store::CACHE_TAG,
                            Mage_Cms_Model_Block::CACHE_TAG,
                            'menu'),
                    )
                );
            } elseif ($language == "ar") {
                $this->addData(array(
                        'cache_key' => 'CustomMenu_Mobile_AR_' . $store, // can be static or dynamic
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