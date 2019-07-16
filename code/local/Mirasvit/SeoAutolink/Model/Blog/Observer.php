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



class Mirasvit_SeoAutolink_Model_Blog_Observer extends Varien_Object
{
    protected $_isBlogPage = false;
    protected $_isBlogRssPage = false;

    public function __construct()
    {
        $blogAction = array('blog_index_list', 'blog_cat_view', 'blog_post_view');
        $blogRssAction = array('blog_rss_index');
        if (Mage::app()->getFrontController()->getAction() && in_array(Mage::app()->getFrontController()->getAction()->getFullActionName(), $blogAction)) {
            $this->_isBlogPage = true;
        }
        if (Mage::app()->getFrontController()->getAction() && in_array(Mage::app()->getFrontController()->getAction()->getFullActionName(), $blogRssAction)) {
            $this->_isBlogRssPage = true;
        }
    }

    public function addAutoLinksToBlog($e)
    {
        if ($this->_isBlogPage
            && Mage::getSingleton('seoautolink/config')->getIsEnableLinksForBlog(Mage::app()->getStore()->getStoreId())) {
            if ($e->getData('block')->getNameInLayout() == 'content') {
                $html = $e->getData('transport')->getHtml();
                $callback = new Mirasvit_SeoAutolink_Model_Blog_Observer_Callback();
                $html = preg_replace_callback('/(<div class="postContent">(.*?)<div class="tags">)/ims',
                        array($callback, 'callback'),
                        $html
                    );
                $e->getData('transport')->setHtml($html);
            }
        }
        if ($this->_isBlogRssPage
            && Mage::getSingleton('seoautolink/config')->getIsEnableLinksForBlog(Mage::app()->getStore()->getStoreId())) {
            if ($e->getData('block')->getNameInLayout() == 'rss.blog.new') {
                $html = $e->getData('transport')->getHtml();
                $callback = new Mirasvit_SeoAutolink_Model_Blog_Observer_Callback();
                $html = preg_replace_callback('/(<description>(.*?)<\/description>)/ims',
                        array($callback, 'callback'),
                        $html
                    );
                $e->getData('transport')->setHtml($html);
            }
        }
    }
}

class Mirasvit_SeoAutolink_Model_Blog_Observer_Callback
{
    public function callback($matches)
    {
        return Mage::helper('seoautolink')->addLinks($matches[0]);
    }
}
