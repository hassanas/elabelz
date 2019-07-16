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
class Progos_Page_Block_Page_Html_Header extends Mage_Page_Block_Html_Header
{

    /**
     * @return mixed
     */
    public function getCustomersCollection()
    {
        /** @var  $collection Mage_Customer_Model_Resource_Customer_Collection */
        $collection = Mage::getModel('customer/customer')
                          ->getCollection()
                          ->addAttributeToFilter('customerstatus', 1);

        return $collection;
    }

    /**
     * @param $ids
     * @return array
     */
    public function getSellersCollection($ids)
    {
        return Mage::getModel('marketplace/sellerprofile')->topSellers($ids);
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getBrandCollection($limit = 8)
    {
        $storeId = Mage::app()->getStore()->getId();

        $getBrands = Mage::getResourceModel('shopbybrand/brand_collection')
                         ->setStoreId($storeId)
                         ->setOrder('position_brand','DESC')
                         ->addFieldToFilter('is_featured', array('eq' => 1))
                         ->addFieldToFilter('status', array('eq' => 1))
                         ->setPageSize($limit);

        $getBrands->getSelect()->order(new Zend_Db_Expr('RAND()'));

        $brands = [];
        $temp = [];
        foreach ($getBrands as $row) {
            $temp["id"] = $row->getId();
            $temp["name"] = $row->getName();
            $temp["url"] = $row->getUrlKey();
            $brands[] = $temp;
        }

        return $brands;
    }

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
        $url_category = str_replace("en_ae",$store_code,$category->url);
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
          //getting url of category according to store
            $url_category = str_replace("en_ae",$store_code,$child->url);
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

}