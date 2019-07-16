<?php
class Progos_ShopBy_Model_Shopby_Url_Builder extends Amasty_Shopby_Model_Url_Builder
{
    protected function getParamPart()
    {
        if (Mage::getStoreConfig('amshopby/seo/urls')==Progos_ShopBy_Model_Shopby_Source_Url_Mode::MODE_CUSTOM){
            $this->mode =0;
        }
        $seoParts = array();
        $query = array();
        // add attributes as keys, not as ids
        if ($this->mode && !$this->isSomeSearch()) {
            $options = $this->getUrlHelper()->getAllFilterableOptionsAsHash();
            foreach ($this->effectiveQuery as $origAttrCode => $ids)
            {
                $attrCode = str_replace(array('_', '-'), Mage::getStoreConfig('amshopby/seo/special_char'), $origAttrCode);

                if (isset($options[$attrCode]) && $options[$attrCode]){ // it is filterable attribute
                    if ($this->mode == Amasty_Shopby_Model_Source_Url_Mode::MODE_SHORT) {
                        $part = $this->getUrlHelper()->_formatAttributePartShort($attrCode, $ids);
                    } else {
                        $part = $this->getUrlHelper()->_formatAttributePartMultilevel($attrCode, $ids);
                    }

                    if (strlen($part)) {
                        $seoParts[] = $part;
                    }
                }
                else {
                    $query[$origAttrCode] = $ids; // it is pager or smth else
                }
            }
        } else {
            if (Mage::getStoreConfig('amshopby/seo/urls')==Progos_ShopBy_Model_Shopby_Source_Url_Mode::MODE_CUSTOM)
            {
                $options = $this->getUrlHelper()->getAllFilterableOptionsAsHash();
                foreach ($this->effectiveQuery as $origAttrCode => $ids)
                {
                    $attrCode = str_replace(array('_', '-'), Mage::getStoreConfig('amshopby/seo/special_char'), $origAttrCode);
                    if ($attrCode=='manufacturer') {
                        if (isset($options[$attrCode]) && $options[$attrCode]) { // it is filterable attribute
                            if (Mage::getStoreConfig('amshopby/seo/urls')==Progos_ShopBy_Model_Shopby_Source_Url_Mode::MODE_CUSTOM) {
                                $part = $this->getUrlHelper()->_formatAttributePartShort($attrCode, $ids);

                            }

                            if (strlen($part)) {
                                $seoParts[] = $part;
                            }
                        } else {
                            $query[$origAttrCode] = $ids; // it is pager or smth else
                        }
                    }

                }

            }
            $query = $this->effectiveQuery;


        }

        $glue = ($this->mode == Amasty_Shopby_Model_Source_Url_Mode::MODE_SHORT) ? Mage::getStoreConfig('amshopby/seo/option_char') : '/';
        $result = implode($glue, $seoParts);
        if (strlen($result)) {
            $result = $this->getUrlHelper()->checkAddSuffix($result);
        }
        if (Mage::getStoreConfig('amshopby/seo/urls')==Progos_ShopBy_Model_Shopby_Source_Url_Mode::MODE_CUSTOM) {

            if (isset($this->effectiveQuery['manufacturer'])) {
                Mage::log($query,null,'layer.log');
                unset($query['manufacturer']);
            }

        }

        // add other params as query string if any
        $query = http_build_query($query);
        if (strlen($query)){
            $result .= '?' . $query;
        }

        return $result;
    }

    protected function getBasePart($paramPart)
    {
        $rootId = (int) Mage::app()->getStore()->getRootCategoryId();
        $reservedKey = Mage::getStoreConfig('amshopby/seo/key');
        $seoAttributePartExist = strlen($paramPart) && strpos($paramPart, '?') !== 0;

        $isSecure = Mage::app()->getStore()->isCurrentlySecure();
        $base = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $isSecure);

        if ($this->isCatalogSearch()){
            $url = $base . 'catalogsearch/result/';
        }
        elseif ($this->isNewOrSale()) {
            $url = $base . $this->moduleName;
        }
        elseif ($this->getCurrentLandingKey()) {
            $url = $base . $this->getCurrentLandingKey();

            if ($seoAttributePartExist) {
                $url.= '/';
            } else {
                $url = $this->getUrlHelper()->checkAddSuffix($url);
            }
        }
        elseif ($this->isCategorySearch()) {
            $url = $base . 'categorysearch/categorysearch/search/';
        }
        elseif ($this->moduleName == 'cms' && $this->getCategoryId() == $rootId) { // homepage,
            $hasFilter = false;
            if (Mage::getStoreConfig('amshopby/block/ajax')) {
                $hasFilter = true;
            }
            if (!$hasFilter) {
                foreach (array_keys($this->query) as $k){
                    if (!in_array($k, array('p','mode','order','dir','limit')) && false === strpos('__', $k)){
                        $hasFilter = true;
                        break;
                    }
                }
            }

            // homepage filter links
            if ($this->isUrlKeyMode() && $hasFilter){
                $url = $base . $reservedKey . '/';
            }
            // homepage sorting/paging url
            else {
                $url = $base;
            }
        }
        elseif ($this->getCategoryId() == $rootId) {
            $url = $base;

            switch ($this->mode) {
                case Amasty_Shopby_Model_Source_Url_Mode::MODE_DISABLED:
                    $needUrlKey = true;
                    break;
                case Amasty_Shopby_Model_Source_Url_Mode::MODE_MULTILEVEL:
                    $needUrlKey = !$this->isBrandPage();
                    break;
                case Amasty_Shopby_Model_Source_Url_Mode::MODE_SHORT:
                    $needUrlKey = !$seoAttributePartExist;
                    break;
                case Progos_ShopBy_Model_Shopby_Source_Url_Mode::MODE_CUSTOM:
                    $needUrlKey = !$seoAttributePartExist;
                    break;
                default:
                    $needUrlKey = true;
            }
            if ($needUrlKey) {
                $url.= $reservedKey;
                if ($seoAttributePartExist) {
                    $url .=  '/';
                }
            }
        }
        else { // we have a valid category
            $url = $this->getCategoryObject()->getUrl();
            $pos = strpos($url,'?');
            $url = $pos ? substr($url, 0, $pos) : $url;

            if ($seoAttributePartExist) {
                $url = $this->getUrlHelper()->checkRemoveSuffix($url);
                if ($this->isUrlKeyMode()) {
                    $url .= '/' . $reservedKey;
                }
                $url.= '/';
            }

        }

        return $url;
    }
    protected function isUrlKeyMode()
    {
        if (Mage::getStoreConfig('amshopby/seo/urls') == Amasty_Shopby_Model_Source_Url_Mode::MODE_MULTILEVEL || Mage::getStoreConfig('amshopby/seo/urls') == Amasty_Shopby_Model_Source_Url_Mode::MODE_DISABLED)
        {
            return true;
        } elseif (Mage::getStoreConfig('amshopby/seo/urls') == Progos_ShopBy_Model_Shopby_Source_Url_Mode::MODE_CUSTOM){
            return false;
        }

    }
}
		