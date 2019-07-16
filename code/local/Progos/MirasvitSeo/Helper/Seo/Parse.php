<?php

/*
@author : Hassan Ali Shahzad
@Date: 29-03-2017
 * */

class Progos_MirasvitSeo_Helper_Seo_Parse extends Mirasvit_Seo_Helper_Parse
{
    /*
     * This function is extended because we want to add base category name and parent category name in the url
     * start on line 105
     *
     * */
    public function parse($str, $objects, $additional = array(), $storeId = false)
    {
        if (trim($str) == '') {
            return null;
        }

        // here check [progos_url] template is attached removed it from $str and set flag true to attach utrl work at the end of the string
        // Because due to that work this custom work attached on all templates like
        $progos_url = false;
        if(strpos($str,'[progos_url]') !== false ){
            $str = str_replace('[progos_url]','',$str);
            $progos_url = true;
        }
        $b1Open  = '[ZZZZZ';
        $b1Close = 'ZZZZZ]';
        $b2Open  = '{WWWWW';
        $b2Close = 'WWWWW}';

        $str = str_replace('[', $b1Open, $str);
        $str = str_replace(']', $b1Close, $str);
        $str = str_replace('{', $b2Open, $str);
        $str = str_replace('}', $b2Close, $str);

        $pattern = '/\[ZZZZZ[^ZZZZZ\]]*ZZZZZ\]/';

        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);

        $vars = array();
        foreach ($matches as $matche) {
            $vars[$matche[0]] = $matche[0];
        }

        foreach ($objects as $key => $object) {
            $data = $object->getData();
            if (isset($additional[$key])) {
                $data = array_merge($data, $additional[$key]);
            }

            foreach ($data as $dataKey => $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }

                $k1   = $b2Open.$key.'_'.$dataKey.$b2Close;
                $k2   = $b1Open.$key.'_'.$dataKey.$b1Close;
                $skip = true;

                foreach ($vars as $k =>$v) {
                    if (stripos($v, $k1) !== false || stripos($v, $k2) !== false) {
                        $skip = false;
                        break;
                    }
                }

                if ($skip) {
                    continue;
                }

                $value = $this->checkForConvert($object, $key, $dataKey, $value, $storeId);
                foreach ($vars as $k =>$v) {
                    if ($value == '') {
                        if (stripos($v, $k1) !== false || stripos($v, $k2) !== false) {
                            $vars[$k] = '';
                            continue;
                        }
                    }

                    $v = str_replace($k1, $value, $v);
                    $v = str_replace($k2, $value, $v);
                    $vars[$k] = $v;
                }
            }
        }

        foreach ($vars as $k => $v) {
            //if no attibute like [product_nonexists]
            if ($v == $k) {
                $v = '';
            }

            //remove start and end symbols from the string (trim)
            if (substr($v, 0, strlen($b1Open)) == $b1Open) {
                $v = substr($v, strlen($b1Open), strlen($v));
            }

            if (strpos($v, $b1Close) === strlen($v)-strlen($b1Close)) {
                $v = substr($v, 0, strlen($v)-strlen($b1Close));
            }

            //if no attibute like [buy it {product_nonexists} !]
            if (stripos($v, $b2Open) !== false || stripos($v, $b1Open) !== false) {
                $v = '';
            }

            $str = str_replace($k, $v, $str);
        }
        // here to go my work add base category name, parent category name, and id
        if($progos_url){
            // Exclude categories which we dont want to render/include
            $cats = Mage::helper('mirasvitseo')->removeXcludedCategories($objects['product']->getCategoryIds());
            $targetedCat = array();
            $str .= " ";
            if(count($cats)>0){
                $targetedCat[] = reset($cats);
                $targetedCat[] = end($cats);
                $categories = Mage::getResourceModel('catalog/category_collection')
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('entity_id',array('in'=>$targetedCat));
                $str .= "for ";
                foreach($categories as $category)
                    $str .= $category->getName()." ";
            }

            $str = $str.$objects['product']->getId();

        }
        return $str;
    }

    /*
     * This function is overrided due to Arabic name of the product in arabic website
     * Place check if string is in Arabic get attribute value from base store
     * Line: 150
     * */
    protected function checkForConvert($object, $key, $dataKey, $value, $storeId)
    {
        if ($key == 'product' || $key == 'category') {
            if ($key == 'product') {
                $attribute = Mage::getSingleton('catalog/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $dataKey);
            } else {
                $attribute = Mage::getSingleton('catalog/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, $dataKey);
            }

            if ($storeId) {
                $attribute->setStoreId($storeId);
            }
            if ($attribute->getId() > 0) {
                try {
                    $valueId = $object->getDataUsingMethod($dataKey);
                    $value = $attribute->getFrontend()->getValue($object);
                    if ($dataKey=='name')
                    {
                        //store id according to current store in the object @RT
                        //as the object is already loaded, so no need to getAttributeRawValue the object
                        $value = $object->getResource()->getAttribute($dataKey)->getFrontend()->getValue($object);
                    }
                    if ($dataKey=='manufacturer')
                    {
                        $value = $object->getResource()->getAttribute($dataKey)->getFrontend()->getValue($object);
                    }

                } catch(Exception $e) {//possible that some extension is removed, but we have it attribute with source in database
                    $value = '';
                }
                if (!$value) { //need for manufacturer
                    try {
                        $value = $object->getResource()->getAttribute($dataKey)->getFrontend()->getValue($object);
                    } catch(Exception $e) {
                        $value = '';
                    }
                }
                if ((strtolower($value) == 'no'|| strtolower($value) == 'nein' || strtolower($value) == 'nie') && $valueId == '') {
                    $value = '';
                }

                switch ($dataKey) {
                    case 'price':
                        $value = Mage::helper('core')->currency($value, true, false);
                        break;
                    case 'special_price':
                        $value = Mage::helper('core')->currency($value, true, false);
                        break;
                }
            } else {
                switch ($dataKey) {
                    case 'final_price':
                        $value = Mage::helper('core')->currency($value, true, false);
                        break;
                }
            }
        }

        if (is_array($value)) {
            if (isset($value['label'])) {
                $value = $value['label'];
            } else {
                $value = '';
            }
        }

        return $value;
    }
}