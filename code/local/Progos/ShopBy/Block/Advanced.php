<?php

class Progos_ShopBy_Block_Advanced extends Amasty_Shopby_Block_Advanced
{
    /**
     * @param Mage_Catalog_Model_Category $category
     * @param int $level
     * @return string
     */
    public function drawOpenCategoryItem($category, $level = 0)
    {
        if ($this->_isExcluded($category->getId()) || !$category->getIsActive()) {
            return '';
        }

        $cssClass = array(
            'amshopby-cat',
            'level' . $level
        );

        $currentCategory = $this->getDataHelper()->getCurrentCategory();

        if ($currentCategory->getId() == $category->getId()) {
            $cssClass[] = 'active';
        }

        if ($this->isCategoryActive($category)) {
            $cssClass[] = 'parent';
        }

        if ($category->hasChildren()) {
            $cssClass[] = 'has-child';
        }


        $productCount = '';
        if ($this->showProductCount()) {
            $productCount = $category->getProductCount();
            if ($productCount > 0) {
                $productCount = '&nbsp;<span class="count">(' . $productCount . ')</span>';
            } else {
                $productCount = '';
            }
        }

        $html = array();

        /** Begin: Adding Brand URL into category filter */
        $request = $this->getRequest();
        $brandUrl = $request->getRouteName() == 'shopbybrand' ? trim($request->getOriginalPathInfo(), '/') : '';

        //parsing the url to get data from url @RT
        //previous implementation did not consider query params, when sorting, will mess up the url for brand name
        $parseUrl = parse_url($this->getCategoryUrl($category));
        //adding current brand name in the url path section
        $parseUrl['path'] .= $brandUrl;
        //buidling url with brand name
        $url = Mage::getUrl('', ['_direct' => str_replace('/'.Mage::app()->getStore()->getCode().'/','', strtolower($parseUrl['path'])), '_query' => strtolower($parseUrl['query'])]);
        $html[1] = '<a data-category-url="'. $category->getRequestPath() .'" href="' . $url . '">' . $this->__($this->htmlEscape($category->getName())) . '</a>';
        /** End: Adding Brand URL into category filter */

        $showAll = Mage::getStoreConfig('amshopby/advanced_categories/show_all_categories');
        $showDepth = Mage::getStoreConfig('amshopby/advanced_categories/show_all_categories_depth');

        $hasChild = false;

        $inPath = in_array($category->getId(), $currentCategory->getPathIds());
        $showAsAll = $showAll && ($showDepth == 0 || $showDepth > $level + 1);
        if ($inPath || $showAsAll) {
            $childrenIds = $category->getChildren();
            $children = $this->_getCategoryCollection()->addIdFilter($childrenIds);
            $this->_getFilterModel()->addCounts($children);
            $children = $this->asArray($children);

            if ($children && count($children) > 0) {
                $hasChild = true;
                $htmlChildren = '';
                foreach ($children as $child) {
                    $htmlChildren .= $this->drawOpenCategoryItem($child, $level + 1);
                }
                if ($htmlChildren != '') {
                    $cssClass[] = 'expanded';
                    $html[2] = '<ul>' . $this->__($htmlChildren) . '</ul>';
                }
            }
        }

        $html[0] = sprintf('<li class="%s">', implode(" ", $cssClass));
        $html[3] = '</li>';

        ksort($html);

        if ($category->getProductCount() || ($hasChild && $htmlChildren)) {
            $result = implode('', $html);
        } else {
            $result = '';
        }

        return $result;
    }
}