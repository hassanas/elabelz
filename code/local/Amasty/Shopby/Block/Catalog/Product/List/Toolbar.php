<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
class Amasty_Shopby_Block_Catalog_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
    protected $_pagerAlias = 'product_list_toolbar_pager';

    public function getPagerUrl($params=array())
    {
        if ($this->skip())
            return parent::getPagerUrl($params);

        $url = Mage::helper('amshopby/url')->getFullUrl($params);

        if(strpos($url,'&dup=true')){
            return $url;
        }else if(strpos($url,'shopby') || strpos($url,'brand') ){
            // If trailing Slash is not added into end of url then add trailing slash.
            $tempUrl = explode('?',$url);
            $extension = substr(strrchr($tempUrl[0], '.'), 1);
            if (substr($tempUrl[0], -1) != '/' ) {
                if (!in_array($extension, array('html', 'htm', 'php', 'xml', 'rss'))) {
                    $tempUrl[0] = $tempUrl[0].'/';
                }
            }

            $url = Mage::helper('amshopby/url')->getCurrentUrl();

            // If trailing Slash is not added into end of url then add trailing slash.
            $tempUrlAgain = explode('?',$url);//Avoid Repeating the url. Reason for doing this again due to brand url comes in this section again and again.
            $extensionnexturl = substr(strrchr($tempUrlAgain[0], '.'), 1);
            if (substr($tempUrlAgain[0], -1) != '/' ) {
                if (!in_array($extensionnexturl, array('html', 'htm', 'php', 'xml', 'rss'))) {
                    $tempUrlAgain[0] = $tempUrlAgain[0].'/';
                }
            }

            if(strpos($url,'?p=')){
                if( strpos($tempUrl[0],'brand')  ){
                    $url = $tempUrlAgain[0].'?'.$tempUrl[1];
                }else{
                    $url = $tempUrl[0].'?p='.$params['p'];
                }
            }else{
                $url = $tempUrlAgain[0].'?'.$tempUrl[1];
            }

        }

        return $url;
    }

    public function replacePager()
    {
        if ($this->skip()) {
            return;
        }

        $pager = $this->getChild($this->_pagerAlias);
        if (!is_object($pager)) {
            return;
        }
        $template = $pager->getTemplate();

        /** @var Amasty_Shopby_Block_Catalog_Pager $newPager */
        $newPager = $this->getLayout()->createBlock('amshopby/catalog_pager', $this->_pagerAlias);
        $newPager->setTemplate($template);
        $newPager->setAvailableLimit($this->getAvailableLimit());

        $newPager->assign('_type', 'html')
            ->assign('_section', 'body');

        $this->setChild($this->_pagerAlias, $newPager);

        // Fix for limit = all and not set directly in request
        $newPager->setLimit($this->getLimit());

        $newPager->setupCollection();
        $newPager->handlePrevNextTags();
    }

    private function skip()
    {
        $r = Mage::app()->getRequest();
        if (in_array($r->getModuleName(), array('supermenu', 'supermenuadmin', 'catalogsearch','tag', 'catalogsale','catalognew', 'highlight')))
            return true;

        return false;
    }

    public function setChild($alias, $block)
    {
        if ($alias == $this->_pagerAlias && $this->getChild($this->_pagerAlias) instanceof Amasty_Shopby_Block_Catalog_Pager) {
            return $this;
        }
        return parent::setChild($alias, $block);
    }
}