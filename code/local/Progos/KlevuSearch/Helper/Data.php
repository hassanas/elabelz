<?php

/**
 * This Module is created for Desktop and Mobile App search from klevu
 * @category     Progos
 * @package      Progos_KlevuSearch
 * @copyright    Progos TechCopyright (c) 06-09-2017
 * @author       Hassan Ali Shahzad
 *
 */
class Progos_KlevuSearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param $klevuResultKeys receive klevu keys
     * @return array return as per current restmob
     */
    public function filterKlevuSearchKeysAsPerRestMobStructure($klevuResultKeys,$isfloat=null)
    {
        $modifiedResult['products'] = array();
        if (count($klevuResultKeys) <= 0 or $klevuResultKeys == NULL) {
            return $modifiedResult;
        }
        $storeCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();

        foreach ($klevuResultKeys as $klevuResultKey) {

            $ids = explode("-",$klevuResultKey['id']);
            $prod['id'] = $ids[0];
            $categoryIds = explode(";",$klevuResultKey['category_ids']);

            for($k = sizeof($categoryIds)-1; $k >= 0; $k--) {
                $category = Mage::getModel('catalog/category')->load($categoryIds[$k]);
                if ($category->getIsActive()) {
                    $cidtoshow = $categoryIds[$k];
                    break;
                }
            }
            $prod['category_id'] = $cidtoshow;
            $prod['name'] = $klevuResultKey['name'];
            $prod['description'] = $klevuResultKey['shortDesc'];
            $prod['sku'] = $klevuResultKey['sku'];
            $prod['img'] = $klevuResultKey['imageUrl'];
            $prod['img2'] = $klevuResultKey['imageUrl'];
            if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                $prod['img'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$klevuResultKey['imageUrl']);
                $prod['img2'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$klevuResultKey['imageUrl']);
            }
            if($isfloat){
                $prod['sale_price'] = 0;
                $prod['price'] = (float)number_format((float)$klevuResultKey['price'], 2,'.','');
                if (isset($klevuResultKey['salePrice']) && $klevuResultKey['salePrice'] != $klevuResultKey['price']) {
                    $prod['sale_price'] = (float)number_format((float)$klevuResultKey['salePrice'], 2,'.','');
                }
            }else {
                $prod['sale_price'] = '';
                $prod['price'] = ceil($klevuResultKey['price']);
                if (isset($klevuResultKey['salePrice']) && $klevuResultKey['salePrice'] != $klevuResultKey['price']) {
                    $prod['sale_price'] = ceil($klevuResultKey['salePrice']);
                }
            }
            $prod['inStock'] = $klevuResultKey['inStock'];
            $prod['currency'] = $storeCurrency;
            if (isset($klevuResultKey['manufacturer'])) {
                $prod['manufacturer'] = $klevuResultKey['manufacturer'];
            } else {
                $prod['manufacturer'] = "";
            }
            $modifiedResult['products'][] = $prod;
        }
        return $modifiedResult;
    }

    /**
     * @param $klevuResultKeys receive klevu keys
     * @return array return as per current restmob
     */
    public function filterKlevuAutoCompleteKeysAsPerRestMobStructure($klevuResultKeys)
    {
        $modifiedResult['products'] = array();
        if (count($klevuResultKeys) <= 0 or $klevuResultKeys == NULL) {
            return $modifiedResult;
        }
        foreach ($klevuResultKeys as $klevuResultKey) {
            $modifiedResult['products'][] = $klevuResultKey['name'];
        }
        return $modifiedResult;
    }

    /**
     * @param $klevuResultKeys
     * @return array return as per current restmob
     */
    public function filterKlevuLayeredNavKeysAsPerRestMobStructure($klevuResultKeys)
    {

        $attrs["category"]['label'] = __("Category");
        $attrs["category"]['code'] = 'category';
        $attrs["category"]['sort'] = 5;
        $attrs["category"]['options'] = array();

        $attrs["design"]['label'] = __("Brands");
        $attrs["design"]['code'] = 'manufacturer';
        $attrs["design"]['sort'] = 1;
        $attrs["design"]['options'] = array();

        $attrs["color"]['label'] = __("Color");
        $attrs["color"]['code'] = 'color';
        $attrs["color"]['sort'] = 2;
        $attrs["color"]['options'] = array();

        $attrs["size"]['label'] = __("Size");
        $attrs["size"]['code'] = 'size';
        $attrs["size"]['sort'] = 3;
        $attrs["size"]['options'] = array();

        $attrs["price"]['label'] = __("Price");
        $attrs["price"]['code'] = 'price';
        $attrs["price"]['sort'] = 4;
        $attrs["price"]['options'] = array();

        if (count($klevuResultKeys) <= 0 || $klevuResultKeys == NULL) {
            return $attrs;
        }
        foreach ($klevuResultKeys as $klevuResultKey) {

            switch ($klevuResultKey['key']) {
                case 'category':
                    foreach ($klevuResultKey['options'] as $key => $options) {
                        if(trim($options['value']) == 'category:men' || trim($options['value'])=='category:women' || trim($options['value'])=='category:kids') continue; // need to exclude base categories
                        $attrs["category"]['options'][$key]['label'] = $options['name'];
                        $attrs["category"]['options'][$key]['count'] = $options['count'];
                        $attrs["category"]['options'][$key]['value'] = trim($options['value']);
                        $attrs["category"]['options'][$key]['selected'] = $options['selected'];
                    }
                    $attrs["category"]['options'] = array_values($attrs["category"]['options']);
                    break;
                case 'manufacturer':
                    foreach ($klevuResultKey['options'] as $key => $options) {
                        $attrs["design"]['options'][$key]['label'] = $options['name'];
                        $attrs["design"]['options'][$key]['count'] = $options['count'];
                        $attrs["design"]['options'][$key]['value'] = trim($options['value']);
                        $attrs["design"]['options'][$key]['selected'] = $options['selected'];
                    }
                    $attrs["design"]['options'] = array_values($attrs["design"]['options']);
                    break;
                case 'color':
                    foreach ($klevuResultKey['options'] as $key => $options) {
                        $attrs["color"]['options'][$key]['label'] = $options['name'];
                        $attrs["color"]['options'][$key]['count'] = $options['count'];
                        $attrs["color"]['options'][$key]['value'] = trim($options['value']);
                        $attrs["color"]['options'][$key]['selected'] = $options['selected'];
                    }
                    $attrs["color"]['options'] = array_values($attrs["color"]['options']);
                    break;
                case 'size':
                    foreach ($klevuResultKey['options'] as $key => $options) {
                        $attrs["size"]['options'][$key]['label'] = $options['name'];
                        $attrs["size"]['options'][$key]['count'] = $options['count'];
                        $attrs["size"]['options'][$key]['value'] = trim($options['value']);
                        $attrs["size"]['options'][$key]['selected'] = $options['selected'];
                    }
                    $attrs["size"]['options'] = array_values($attrs["size"]['options']);
                    break;
                case 'Price Range':
                    foreach ($klevuResultKey['options'] as $key => $options) {
                        $attrs["price"]['options'][$key]['label'] = $options['name'];
                        $attrs["price"]['options'][$key]['count'] = $options['count'];
                        $attrs["price"]['options'][$key]['value'] = trim($options['value']);
                        $attrs["price"]['options'][$key]['selected'] = $options['selected'];
                    }
                    $attrs["price"]['options'] = array_values($attrs["price"]['options']);
                    break;
            }
        }
        return $attrs;
    }
}