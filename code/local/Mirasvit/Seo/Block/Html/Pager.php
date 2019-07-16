<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Block_Html_Pager extends Mage_Page_Block_Html_Pager
{
    protected $_fullActionCode;

    protected function getSeoReviewUrl($page, $limit) {
        $frontControllerRequest = Mage::app()->getFrontController()->getRequest();
        $params = $frontControllerRequest->getQuery();
        if ($page && $page == 1) {
            unset($params['p']);
        } elseif ($page) {
             $params['p'] = $page;
        }
        if ($limit) {
            $params['limit'] = $limit;
        }
        $originalPath = $frontControllerRequest->getOriginalPathInfo();
        $reviewPath = (substr($originalPath, 0, 1) == '/') ? substr($originalPath, 1) : $originalPath;

        return Mage::getUrl('',
            array(
                '_direct' => $reviewPath,
                '_query' => $params
            )
        );
    }

    public function getPageUrl($page)
    {
        //support of Mana_Filters
        if (Mage::helper('core')->isModuleEnabled('Mana_Filters') && Mage::helper('seo')->getFullActionCode() == 'catalog_category_view') {
            return parent::getPageUrl($page);
        }

        if(Mage::helper('seo')->getFullActionCode() == 'marketplace_order_manage'){
            return parent::getPageUrl($page);
        }

        //support for Amasty Landing Pages
        if ($identifier = Mage::helper('seo')->isOnLandingPage()) {
            return $this->getParentBlock()->getPagerUrl(array($this->getPageVarName()=>$page));
        }

        //support of TM_Attributepages, Fishpig_AttributeSplash and Magestore_Shopbybrand
        $excludedActions = array('attributepages_page_view', 'attributesplash_page_view', 'splash_page_view', 'brand_index_view', 'shopbybrand_index_view');
        if (in_array(Mage::helper('seo')->getFullActionCode(), $excludedActions)) {
            return parent::getPageUrl($page);
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter')
            && Mage::registry('current_category')
            && Mage::helper('seo')->getFullActionCode() != 'review_product_list'
            && Mage::helper('seo')->getFullActionCode() != 'tag_product_list') {
            if (Mage::getModel('seofilter/config')->isEnabled()) {
                if ($page == 1) {
                    $url = Mage::getModel('seofilter/catalog_layer_filter_item')->getSpeakingFilterUrl(FALSE, TRUE, array());

                    return $url;
                } else {
                    $url = Mage::getModel('seofilter/catalog_layer_filter_item')->getSpeakingFilterUrl(FALSE, TRUE, array($this->getPageVarName()=>$page));

                    return $url;
                }
            }
        }

        if (Mage::helper('seo')->getFullActionCode() == 'review_product_list' || Mage::helper('seo')->getFullActionCode() == 'tag_product_list') {
            return $this->getSeoReviewUrl($page, false);
        } else {
            if ($page == 1) {
                $params = Mage::app()->getFrontController()->getRequest()->getQuery();
                unset($params['p']);

                $urlParams['_use_rewrite'] = true;
                $urlParams['_escape']      = true;
                $urlParams['_query']       = $params;

                return $this->getUrl('*/*/*', $urlParams);
            } else {
                return $this->getPagerUrl(array($this->getPageVarName()=>$page));
            }
        }
    }

    public function getLimitUrl($limit)
    {
        if (Mage::helper('seo')->getFullActionCode() == 'review_product_list') {
            return $this->getSeoReviewUrl(false, $limit);
        } else {
            return parent::getLimitUrl($limit);
        }
    }
    public function addingNewPage($url){
        $new_url = explode("/",$url);
        $new = end($new_url);
        if(strpos($new, "?p=") !== false || strpos($new, "&p=") !== false ):
          if(strpos($new, "?p=") !== false):
            array_pop($new_url);
            return implode('/',$new_url);
          elseif(strpos($new, "&p=") !== false):
             $part = explode("&",$new);
             array_pop($part);
             $part = implode("&",$part);
             array_pop($new_url);
             $url = implode("/",$new_url)."/".$part;
             return $url;
          endif;
        else:    
        return implode("/",$new_url) ;
        endif;

    }
}
